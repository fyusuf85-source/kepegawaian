<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\User;
use App\Services\Pph21Calculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Pph21CalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_ter_category_a_applies_rate_from_imported_excel_table(): void
    {
        $employee = Employee::query()->firstOrFail();
        $profile = $employee->payrollProfile;
        $profile->update(['tax_status' => 'TK/0', 'tax_borne_by_employer' => false]);
        $components = PayrollComponent::query()->get()->keyBy('code');

        $result = app(Pph21Calculator::class)->calculate($employee, $profile, 6000000, $components);

        $this->assertNotNull($result);
        $this->assertSame('potongan', $result['type']);
        $this->assertEquals(45000, $result['amount']);
        $this->assertStringContainsString('Kategori A', $result['formula_note']);
    }

    public function test_tax_borne_by_employer_becomes_employer_contribution(): void
    {
        $employee = Employee::query()->firstOrFail();
        $profile = $employee->payrollProfile;
        $profile->update(['tax_status' => 'K/3', 'tax_borne_by_employer' => true]);
        $components = PayrollComponent::query()->get()->keyBy('code');

        $result = app(Pph21Calculator::class)->calculate($employee, $profile, 8000000, $components);

        $this->assertNotNull($result);
        $this->assertSame('iuran_pemberi', $result['type']);
        $this->assertEquals(80000, $result['amount']);
        $this->assertStringContainsString('Kategori C', $result['formula_note']);
    }
}
