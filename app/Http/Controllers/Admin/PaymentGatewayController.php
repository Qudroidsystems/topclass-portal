<?php
// app/Http/Controllers/Admin/PaymentGatewayController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Yajra\DataTables\Facades\DataTables;

class PaymentGatewayController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Manage payment gateways');
    }

    /**
     * List payment gateways with DataTable.
     */
    public function index(Request $request)
    {
        $pagetitle = 'Payment Gateway Configuration';

        if ($request->ajax()) {
            $gateways = PaymentGateway::select('*');

            return DataTables::of($gateways)
                ->addIndexColumn()
                ->addColumn('status_badge', function($gateway) {
                    if ($gateway->is_active) {
                        return '<span class="badge bg-success"><i class="ri-check-line"></i> Active</span>';
                    }
                    return '<span class="badge bg-danger"><i class="ri-close-line"></i> Inactive</span>';
                })
                ->addColumn('mode_badge', function($gateway) {
                    if ($gateway->mode === 'live') {
                        return '<span class="badge bg-primary">Live</span>';
                    }
                    return '<span class="badge bg-warning">Sandbox/Test</span>';
                })
                ->addColumn('action', function($gateway) {
                    $buttons = '<button class="btn btn-sm btn-primary edit-gateway me-1" data-id="'.$gateway->id.'"><i class="ri-settings-line"></i></button>';
                    $buttons .= '<button class="btn btn-sm btn-info test-gateway me-1" data-id="'.$gateway->id.'"><i class="ri-flask-line"></i> Test</button>';
                    $buttons .= '<button class="btn btn-sm btn-warning toggle-gateway" data-id="'.$gateway->id.'" data-active="'.$gateway->is_active.'">';
                    $buttons .= $gateway->is_active ? '<i class="ri-pause-line"></i> Disable' : '<i class="ri-play-line"></i> Enable';
                    $buttons .= '</button>';
                    return $buttons;
                })
                ->rawColumns(['status_badge', 'mode_badge', 'action'])
                ->make(true);
        }

        return view('admin.payment-gateways.index', compact('pagetitle'));
    }

    /**
     * Toggle gateway status (AJAX).
     */
    public function toggleGateway(Request $request, $gatewayId)
    {
        $gateway = PaymentGateway::findOrFail($gatewayId);

        $gateway->update([
            'is_active' => !$gateway->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => $gateway->name . ' is now ' . ($gateway->is_active ? 'active' : 'inactive'),
            'is_active' => $gateway->is_active
        ]);
    }

    /**
     * Update gateway configuration (AJAX).
     */
    public function updateConfig(Request $request, $gatewayId)
    {
        $gateway = PaymentGateway::findOrFail($gatewayId);

        $validator = Validator::make($request->all(), [
            'mode' => 'required|in:sandbox,live',
            'secret_key' => 'nullable|string',
            'public_key' => 'nullable|string',
            'config' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = [
            'mode' => $request->mode,
        ];

        if ($request->secret_key) {
            $updateData['secret_key'] = $request->secret_key;
        }
        if ($request->public_key) {
            $updateData['public_key'] = $request->public_key;
        }
        if ($request->config) {
            $existingConfig = $gateway->config ?? [];
            $updateData['config'] = array_merge($existingConfig, $request->config);
        }

        $gateway->update($updateData);

        return response()->json([
            'success' => true,
            'message' => $gateway->name . ' configuration updated successfully!'
        ]);
    }

    /**
     * Test gateway connection (AJAX).
     */
    public function testGateway(Request $request, $gatewayId)
    {
        $gateway = PaymentGateway::findOrFail($gatewayId);

        try {
            switch ($gateway->provider_key) {
                case 'paystack':
                    $result = $this->testPaystack($gateway);
                    break;
                case 'remita':
                    $result = $this->testRemita($gateway);
                    break;
                case 'flutterwave':
                    $result = $this->testFlutterwave($gateway);
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'No test available for this gateway'
                    ]);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Test Paystack connection.
     */
    private function testPaystack($gateway)
    {
        $secretKey = $gateway->mode === 'live' ? $gateway->secret_key : ($gateway->config['test_secret_key'] ?? '');

        if (!$secretKey) {
            return ['success' => false, 'message' => 'Secret key not configured'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
        ])->get('https://api.paystack.co/transaction/initialize');

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Paystack connection successful!'];
        }

        return ['success' => false, 'message' => 'Paystack connection failed: ' . $response->body()];
    }

    /**
     * Test Remita connection.
     */
    private function testRemita($gateway)
    {
        $merchantId = $gateway->mode === 'live'
            ? ($gateway->config['live_merchant_id'] ?? '')
            : ($gateway->config['test_merchant_id'] ?? '2547916');

        if (!$merchantId) {
            return ['success' => false, 'message' => 'Merchant ID not configured'];
        }

        return ['success' => true, 'message' => 'Remita configuration looks valid (manual test required for full verification)'];
    }

    /**
     * Test Flutterwave connection.
     */
    private function testFlutterwave($gateway)
    {
        $secretKey = $gateway->mode === 'live' ? $gateway->secret_key : ($gateway->config['test_secret_key'] ?? '');

        if (!$secretKey) {
            return ['success' => false, 'message' => 'Secret key not configured'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
        ])->get('https://api.flutterwave.com/v3/banks/NG');

        if ($response->successful() && $response->json('status') === 'success') {
            return ['success' => true, 'message' => 'Flutterwave connection successful!'];
        }

        return ['success' => false, 'message' => 'Flutterwave connection failed'];
    }
}
