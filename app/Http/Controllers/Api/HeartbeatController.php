<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HeartbeatController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
            'ip_address' => 'nullable|string',
            'version' => 'nullable|string',
        ]);

        $deviceId = $validated['device_id'];

        // Upsert the device record
        // Relying on search_path = pool_schema
        $device = DB::table('pool_schema.rfid_devices')->updateOrInsert(
            ['device_id' => $deviceId],
            [
                'ip_address' => $validated['ip_address'] ?? null,
                'version' => $validated['version'] ?? null,
                'status' => 'online',
                'last_heartbeat' => Carbon::now(),
                'updated_at' => Carbon::now(),
                // 'created_at' handled by updateOrInsert only if not exists? 
                // DB::table updateOrInsert defaults don't set created_at automatically for new inserts unless specified
            ]
        );
        
        // If it was an insert, we might want to ensure created_at is set, 
        // but updateOrInsert is limited. 
        // Let's stick to simple logic or use a Model if we had one. Query Builder is fine.

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat received'
        ]);
    }
}
