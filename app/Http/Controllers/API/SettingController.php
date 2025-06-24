<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MakeOrderSetting;
use App\Models\OrderSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    //
    public  function getMakeOrderSettingsByExternalId($externalId)
    {
        if (!$externalId) {
            return response()->json(['message' => 'External ID is required.', 'success' => false]);
        }
        try {
            $store = MakeOrderSetting::where('external_id', $externalId)
                ->get();
            return response()->json(['data' => $store, 'success' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching stores.', 'success' => false]);
        }
    }

    public  function getOrderSettingsByExternalId($externalId)
    {
        if (!$externalId) {
            return response()->json(['message' => 'External ID is required.', 'success' => false]);
        }
        try {
            $store = OrderSetting::where('external_id', $externalId)
                ->get();
            return response()->json(['data' => $store, 'success' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching stores.', 'success' => false]);
        }
    }
}
