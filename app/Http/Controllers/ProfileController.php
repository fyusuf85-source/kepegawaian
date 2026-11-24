<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $employee = $request->user()->employee;

        abort_unless($employee, 404, 'Akun Anda belum terhubung dengan data pegawai.');

        $employee->load([
            'orgUnit', 'position', 'workLocation', 'supervisor',
            'families', 'educations.education', 'bankAccounts', 'documents',
        ]);

        return view('profile.show', compact('employee'));
    }
}
