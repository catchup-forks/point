<?php

namespace App\Http\Controllers\Api\Purchase\PurchaseInvoice;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Http\Resources\ApiResource;
use App\Model\Master\Supplier;
use App\Model\Purchase\PurchaseInvoice\PurchaseInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return ApiCollection
     */
    public function index(Request $request)
    {
        $purchaseInvoices = PurchaseInvoice::eloquentFilter($request)
            ->join(Supplier::getTableName(), PurchaseInvoice::getTableName('supplier_id'), '=', Supplier::getTableName('id'))
            ->select(PurchaseInvoice::getTableName('*'))
            ->joinForm()
            ->notArchived()
            ->with('form');

        $purchaseInvoices = pagination($purchaseInvoices, $request->get('limit'));

        return new ApiCollection($purchaseInvoices);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @throws \Throwable
     * @return ApiResource
     */
    public function store(Request $request)
    {
        $result = DB::connection('tenant')->transaction(function () use ($request) {
            $purchaseInvoice = PurchaseInvoice::create($request->all());

            $purchaseInvoice
                ->load('form')
                ->load('supplier')
                ->load('items.item')
                ->load('items.allocation')
                ->load('services.service')
                ->load('services.allocation');

            return new ApiResource($purchaseInvoice);
        });

        return $result;
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param  int $id
     * @return ApiResource
     */
    public function show(Request $request, $id)
    {
        $purchaseInvoice = PurchaseInvoice::eloquentFilter($request)
            ->with('form')
            ->with('supplier')
            ->with('items.item')
            ->with('items.allocation')
            ->with('items.purchaseReceive.form')
            ->with('services.service')
            ->with('services.allocation')
            ->with('services.purchaseReceive.form')
            ->findOrFail($id);

        return new ApiResource($purchaseInvoice);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int  $id
     * @return ApiResource
     */
    public function update(Request $request, $id)
    {
        // TODO prevent delete if referenced by purchase payment
        $result = DB::connection('tenant')->transaction(function () use ($request, $id) {

            $purchaseInvoice = PurchaseInvoice::findOrFail($id);

            $newPurchaseInvoice = $purchaseInvoice->edit($request->all());

            return new ApiResource($newPurchaseInvoice);
        });

        return $result;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $purchaseInvoice = PurchaseInvoice::findOrFail($id);

        $purchaseInvoice->delete();

        return response()->json([], 204);
    }
}
