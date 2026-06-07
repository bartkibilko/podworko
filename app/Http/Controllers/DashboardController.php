<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $neighbourhoods = $user->memberships()
            ->with('neighbourhood')
            ->get()
            ->pluck('neighbourhood');

        return view('dashboard', ['neighbourhoods' => $neighbourhoods]);
    }
}
