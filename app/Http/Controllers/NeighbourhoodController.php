<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\NeighbourhoodAccessCode;
use App\Enums\Role;
use App\Models\Membership;
use App\Models\Neighbourhood;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class NeighbourhoodController extends Controller
{
    /** Step 1: the name form. */
    public function create(): View
    {
        return view('neighbourhoods.create');
    }

    /** Step 2: preview a generated code (the "regenerate" button re-posts here). */
    public function preview(Request $request): View
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $name = (string) $validated['name'];

        return view('neighbourhoods.preview', [
            'name' => $name,
            'accessCode' => NeighbourhoodAccessCode::generate($name),
        ]);
    }

    /** Save the neighbourhood with the shown code and make the creator its Founder. */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'access_code' => ['required', 'string', 'regex:/^[A-Z0-9]{6}$/', 'unique:neighbourhoods,access_code'],
        ]);

        DB::transaction(function () use ($validated): void {
            $neighbourhood = Neighbourhood::create([
                'name' => $validated['name'],
                'access_code' => $validated['access_code'],
            ]);

            Membership::create([
                'user_id' => Auth::id(),
                'neighbourhood_id' => $neighbourhood->id,
                'household_id' => null,
                'role' => Role::Founder,
                'requested_household_name' => null,
            ]);
        });

        return redirect()->route('dashboard')->with('status', 'Osiedle zostało utworzone.');
    }
}
