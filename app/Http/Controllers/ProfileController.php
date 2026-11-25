<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $employee = $request->user()->employee;

        if ($employee) {
            $employee->load([
                'orgUnit', 'position', 'workLocation', 'supervisor',
                'families', 'educations.education', 'bankAccounts', 'documents',
            ]);
        }

        return view('profile.show', [
            'employee' => $employee,
            'user' => $request->user(),
        ]);
    }
}
