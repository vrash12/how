<?php
// app/Http/Controllers/Admin/UserAuditController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserAuditTrail;
use Illuminate\Http\Request;

class UserAuditController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(Request $request)
    {
        $query = UserAuditTrail::with(['user', 'patient']);

        // Filters
        if ($request->filled('user_search')) {
            $search = $request->user_search;
            $query->where('username', 'LIKE', "%{$search}%");
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('patient_search')) {
            $search = $request->patient_search;
            $query->where('patient_name', 'LIKE', "%{$search}%");
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Add option to filter specifically for OTC sales
        if ($request->filled('filter_otc') && $request->filter_otc == 1) {
            $query->where('action', 'otc_sale');
        }

        // Get the page size from request or use default
        $pageSize = $request->input('page_size', 15);
        // Ensure valid page size values
        if (!in_array($pageSize, [10, 25, 50, 100])) {
            $pageSize = 25;
        }

        // Statistics
        $totalActions = UserAuditTrail::count(); // Get total count without filters
        $filteredActions = $query->count(); // Get count with applied filters
        $totalAmount = $query->sum('amount_involved');
        
        $actionStats = UserAuditTrail::selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        $moduleStats = UserAuditTrail::selectRaw('module, COUNT(*) as count, SUM(amount_involved) as total_amount')
            ->groupBy('module')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Pagination with selected page size
        $auditLogs = $query->orderByDesc('created_at')->paginate($pageSize);

        return view('admin.audit.index', compact(
            'auditLogs',
            'totalActions',
            'filteredActions',
            'totalAmount',
            'actionStats',
            'moduleStats',
            'pageSize'
        ));
    }
}