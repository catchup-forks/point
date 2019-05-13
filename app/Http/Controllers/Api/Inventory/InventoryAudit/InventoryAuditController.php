<?php

namespace App\Http\Controllers\Api\Inventory\InventoryAudit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ApiResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Model\Inventory\InventoryAudit\InventoryAudit;

class InventoryAuditController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return ApiCollection
     */
    public function index(Request $request)
    {
        $inventoryAudits = InventoryAudit::eloquentFilter($request);

        $inventoryAudits = pagination($inventoryAudits, $request->get('limit'));

        return new ApiCollection($inventoryAudits);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        $result = DB::connection('tenant')->transaction(function () use ($request) {
            $inventoryAudit = InventoryAudit::create($request->all());
            $inventoryAudit->load('form', 'warehouse', 'items.item');

            return new ApiResource($inventoryAudit);
        });

        return $result;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return ApiResource
     */
    public function show(Request $request, $id)
    {
        $inventoryAudits = InventoryAudit::eloquentFilter($request)
            ->with('form')
            ->with('warehouse')
            ->with('items.item')
            ->findOrFail($id);

        return new ApiResource($inventoryAudits);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}