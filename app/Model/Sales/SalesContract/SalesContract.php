<?php

namespace App\Model\Sales\SalesContract;

use App\Model\Form;
use App\Model\Master\Customer;
use App\Model\Master\Group;
use App\Model\Master\Item;
use App\Model\Sales\SalesOrder\SalesOrder;
use App\Model\TransactionModel;
use App\Model\Sales\SalesOrder\SalesOrderItem;

class SalesContract extends TransactionModel
{
    protected $connection = 'tenant';

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'customer_name',
        'cash_only',
        'need_down_payment',
        'discount_percent',
        'discount_value',
        'type_of_tax',
        'tax',
    ];

    protected $casts = [
        'amount' => 'double',
        'discount_percent' => 'double',
        'discount_value' => 'double',
        'tax' => 'double',
    ];

    public $defaultNumberPrefix = 'CONTRACT';

    public function form()
    {
        return $this->morphOne(Form::class, 'formable');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function groupItems()
    {
        return $this->hasMany(SalesContractGroupItem::class);
    }

    public function items()
    {
        return $this->hasMany(SalesContractItem::class);
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function updateIfDone()
    {
        // Make form done when all items / group items ordered
        $done = true;

        if (! empty($this->items)) {
            $salesContractItems = $this->items;
            $salesContractItemIds = $salesContractItems->pluck('id');
            
            $quantityOrderedItems = SalesOrder::joinForm()
                ->join(SalesOrderItem::getTableName(), SalesOrder::getTableName('id'), '=', SalesOrderItem::getTableName('sales_order_id'))
                ->groupBy('sales_contract_item_id')
                ->select(SalesOrderItem::getTableName('sales_contract_item_id'))
                ->addSelect(\DB::raw('SUM(quantity) AS sum_ordered'))
                ->whereIn('sales_contract_item_id', $salesContractItemIds)
                ->active()
                ->get()
                ->pluck('sum_ordered', 'sales_contract_item_id');
            
            foreach ($salesContractItems as $salesContractItem) {
                $quantityOrdered = $quantityOrderedItems[$salesContractItem->id] ?? 0;
                if ($salesContractItem->quantity - $quantityOrdered > 0) {
                    $done = false;
                    break;
                }
            }
        } else if (! empty($this->groupItems)) {
            $salesContractGroupItems = $this->groupItems;
            $salesContractGroupItemIds = $salesContractGroupItems->pluck('id');

            $quantityOrderedGroupItems = SalesOrder::joinForm()
                ->join(SalesOrderItem::getTableName(), SalesOrder::getTableName('id'), '=', SalesOrderItem::getTableName('sales_order_id'))
                ->groupBy('sales_contract_group_item_id')
                ->select(SalesOrderItem::getTableName('sales_contract_group_item_id'))
                ->addSelect(\DB::raw('SUM(quantity) AS sum_ordered'))
                ->whereIn('sales_contract_group_item_id', $salesContractGroupItemIds)
                ->active()
                ->get()
                ->pluck('sum_ordered', 'sales_contract_group_item_id');
            
            foreach ($salesContractGroupItems as $salesContractGroupItem) {
                $quantityOrdered = $quantityOrderedGroupItems[$salesContractGroupItem->id] ?? 0;
                if ($salesContractGroupItem->quantity - $quantityOrdered > 0) {
                    $done = false;
                    break;
                }
            }
        } else {
            // TODO throw error if sales contract doesn't have items and groupItems
            $done = false;
        }

        if ($done === true) {
            $this->form->done = true;
            $this->form->save();
        }
    }

    public static function create($data)
    {
        $salesContract = new self;
        if (empty($data['customer_name'])) {
            $data['customer_name'] = Customer::find($data['customer_id'], ['name']);
        }
        $salesContract->fill($data);

        $items = [];
        $groupItems = [];
        $amount = 0;

        if (!empty($data['items'])) {
            $itemIds = array_column($data['items'], 'item_id');
            $dbItems = Item::select('id', 'name')->whereIn('id', $itemIds)->get()->keyBy('id');

            foreach ($data['items'] as $item) {
                $contractItem = new SalesContractItem;
                $contractItem->fill($item);
                $contractItem->item_name = $dbItems[$item['item_id']]->name;

                $amount += $item['quantity'] * $item['price'];

                array_push($items, $contractItem);
            }
        } else if (!empty($data['groups'])) {
            $groupIds = array_column($data['groups'], 'group_id');
            $dbGroups = Group::select('id', 'name')->whereIn('id', $groupIds)->get()->keyBy('id');

            foreach ($data['groups'] as $groupItem) {
                $contractGroup = new SalesContractGroupItem;
                $contractGroup->fill($groupItem);
                $contractGroup->group_name = $dbGroups[$groupItem['group_id']]->name;

                $amount += $groupItem['quantity'] * $groupItem['price'];

                array_push($groupItems, $contractGroup);
            }
        }

        $salesContract->amount = $amount;
        $salesContract->save();

        if (!empty($items)) {
            $salesContract->items()->saveMany($items);
        } else if (!empty($groupItems)) {
            $salesContract->groupItems()->saveMany($groupItems);
        }

        $form = new Form;
        $form->fillData($data, $salesContract);

        return $salesContract;
    }
}
