<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    public function test_guest_is_redirected_from_sensitive_pages(): void
    {
        foreach ([
            route('employees.index'),
            route('payroll.index'),
            route('kpi.index'),
            route('coaching.index'),
            route('reports.executive'),
            route('audit.index'),
            route('users.index'),
            route('notifications.index'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_employee_cannot_open_reports_or_payroll_exports(): void
    {
        $pegawai = $this->user('pegawai.demo');

        $this->actingAs($pegawai)->get(route('reports.executive'))->assertForbidden();
        $this->actingAs($pegawai)->get(route('reports.attendance'))->assertForbidden();
        $this->actingAs($pegawai)->get(route('reports.payroll'))->assertForbidden();
        $this->actingAs($pegawai)->get(route('payroll.index'))->assertForbidden();
    }

    public function test_employee_cannot_access_other_employee_profile(): void
    {
        $pegawai = $this->user('pegawai.demo');
        $other = Employee::query()->whereKeyNot($pegawai->employee_id)->firstOrFail();

        $this->actingAs($pegawai)->get(route('employees.show', $other))->assertForbidden();
    }

    public function test_head_of_unit_cannot_export_payroll(): void
    {
        $kepala = $this->user('kepala.demo');

        $this->actingAs($kepala)->get(route('payroll.index'))->assertForbidden();
        $this->actingAs($kepala)->get(route('reports.payroll'))->assertForbidden();
    }

    public function test_hrd_cannot_manage_system_users(): void
    {
        $hrd = $this->user('hrd.demo');

        $this->actingAs($hrd)->get(route('users.index'))->assertForbidden();
        $this->actingAs($hrd)->get(route('roles.index'))->assertForbidden();
    }

    public function test_notification_of_other_user_cannot_be_marked_read(): void
    {
        $pegawai = $this->user('pegawai.demo');
        $hrd = $this->user('hrd.demo');

        $notification = \App\Models\Notification::query()->create([
            'user_id' => $hrd->id,
            'type' => 'approval',
            'title' => 'Uji',
            'message' => 'Pesan uji',
        ]);

        $this->actingAs($pegawai)
            ->post(route('notifications.read', $notification))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }
}
