<?php

namespace App\Model\Sales\DeliveryOrder;

use App\Model\Form;
use App\Model\Master\Customer;
use App\Model\Master\Warehouse;
use App\Model\Sales\DeliveryNote\DeliveryNote;
use App\Model\Sales\DeliveryNote\DeliveryNoteItem;
use App\Model\Sales\SalesOrder\SalesOrder;
use App\Model\TransactionModel;

class DeliveryOrder extends TransactionModel
{
    protected $connection = 'tenant';

    protected $table = 'delivery_orders';

    public $timestamps = false;

    protected $fillable = [
        'warehouse_id',
        'sales_order_id',
        'billing_address',
        'billing_phone',
        'billing_email',
        'shipping_address',
        'shipping_phone',
        'shipping_email',
    ];

    protected $defaultNumberPrefix = 'DO';

    public function form()
    {
        return $this->morphOne(Form::class, 'formable');
    }

    public function items()
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class)
            ->joinForm(DeliveryNote::class)
            ->active();
    }

    public function updateIfDone()
    {
        $deliveryOrderItems = $this->items;
        $deliveryOrderItemIds = $deliveryOrderItems->pluck('id');

        $tempArray = DeliveryNote::joinForm()
            ->join(DeliveryNoteItem::getTableName(), DeliveryNote::getTableName('id'), '=', DeliveryNoteItem::getTableName('delivery_note_id'))
            ->groupBy('delivery_order_item_id')
            ->select(DeliveryNoteItem::getTableName('delivery_order_item_id'))
            ->addSelect(\DB::raw('SUM(quantity) AS sum_delivered'))
            ->whereIn('delivery_order_item_id', $deliveryOrderItemIds)
            ->active()
            ->get();

        $quantityDeliveredItems = $tempArray->pluck('sum_delivered', 'delivery_order_item_id');

        // Make form done when all items delivered
        $done = true;
        foreach ($deliveryOrderItems as $deliveryOrderItem) {
            $quantityDelivered = $quantityDeliveredItems[$deliveryOrderItem->id] ?? 0;
            if ($deliveryOrderItem->quantity - $quantityDelivered > 0) {
                $done = false;
                break;
            }
        }

        if ($done == true) {
            $this->form->done = true;
            $this->form->save();
        }
    }

    public static function create($data)
    {
        $salesOrder = SalesOrder::findOrFail($data['sales_order_id']);
        // TODO add check if $salesOrder is canceled / rejected / archived

        $deliveryOrder = new self;
        $deliveryOrder->fill($data);
        $deliveryOrder->customer_id = $salesOrder->customer_id;
        $deliveryOrder->customer_name = $salesOrder->customer_name;
        $deliveryOrder->save();

        $form = new Form;
        $form->fillData($data, $deliveryOrder);

        // TODO items is required and must be array
        $array = [];
        $items = $data['items'];

        $salesOrderItems = $salesOrder->items->keyBy('id');

        foreach ($items as $item) {
            $salesOrderItem = $salesOrderItems[$item['sales_order_item_id']];

            $deliveryOrderItem = new DeliveryOrderItem;
            $deliveryOrderItem->fill($item);
            $deliveryOrderItem->item_name = $salesOrderItem->item_name;
            $deliveryOrderItem->price = $salesOrderItem->price;
            $deliveryOrderItem->discount_percent = $salesOrderItem->discount_percent;
            $deliveryOrderItem->discount_value = $salesOrderItem->discount_value;
            $deliveryOrderItem->taxable = $salesOrderItem->taxable;
            $deliveryOrderItem->allocation_id = $salesOrderItem->allocation_id;

            array_push($array, $deliveryOrderItem);
        }
        $deliveryOrder->items()->saveMany($array);

        $salesOrder->updateIfDone();

        return $deliveryOrder;
    }
}
