<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\AuditService;
use Illuminate\Http\Request;

class WhitelabelController extends Controller
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function index()
    {
        // Get current tenant based on domain or first one for management
        // For superadmin, maybe list all tenants
        $tenants = Tenant::all();
        return view('superadmin.whitelabel.index', compact('tenants'));
    }

    public function update(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'primary_color' => 'required|string|max:7', // Hex
            'logo_url' => 'nullable|url',
            'footer_content' => 'nullable|string',
        ]);

        $old = $tenant->toArray();
        $tenant->update($validated);
        $this->auditService->log('UPDATE', $tenant, $old, $tenant->toArray());

        // Clear cache if tenant settings are cached
        // Cache::forget("tenant_{$tenant->domain}");

        return redirect()->back()->with('success', 'Whitelabel settings updated successfully.');
    }
}
