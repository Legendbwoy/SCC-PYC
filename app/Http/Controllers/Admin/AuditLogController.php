<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Audit\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AuditLog::class);

        $category = $request->string('category')->trim()->toString();
        $search = $request->string('q')->trim()->toString();

        $query = AuditLog::query()->with('actor')->latest('created_at');

        if ($category !== '') {
            $query->where('category', $category);
        }

        if ($search !== '') {
            $query->where(static function ($builder) use ($search): void {
                $builder->where('event', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return view('admin.audit-logs.index', [
            'logs' => $query->paginate(20)->withQueryString(),
            'selectedCategory' => $category,
            'search' => $search,
            'categories' => ['security', 'users', 'settings', 'membership', 'finance', 'notifications'],
        ]);
    }
}
