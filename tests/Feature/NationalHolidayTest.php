<?php

namespace Tests\Feature;

use App\Models\NationalHoliday;
use App\Models\Role;
use App\Models\User;
use App\Services\NationalHolidayService;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NationalHolidayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    protected function createAdminUser(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);

        return $user;
    }

    public function test_fetch_and_store_persists_holidays_from_the_api(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'tanggalmerah.upset.dev/api/holidays*' => Http::response([
                'success' => true,
                'data' => [
                    ['date' => '2026-01-01', 'day' => 'Kamis', 'name' => 'Tahun Baru 2026 Masehi', 'type' => 'holiday'],
                    ['date' => '2026-01-19', 'day' => 'Senin', 'name' => 'Cuti Bersama Imlek', 'type' => 'leave'],
                ],
                'meta' => ['year' => 2026, 'count' => 2],
            ]),
        ]);

        $result = NationalHolidayService::fetchAndStore([2026]);

        $this->assertSame(2, $result['stored']);
        $this->assertSame([], $result['failed']);

        $this->assertDatabaseHas('national_holidays', [
            'date' => '2026-01-01',
            'name' => 'Tahun Baru 2026 Masehi',
            'type' => 'holiday',
            'year' => 2026,
        ]);
        $this->assertDatabaseHas('national_holidays', [
            'date' => '2026-01-19',
            'type' => 'leave',
        ]);
    }

    public function test_fetch_and_store_is_idempotent(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'tanggalmerah.upset.dev/api/holidays*' => Http::response([
                'success' => true,
                'data' => [
                    ['date' => '2026-08-17', 'day' => 'Senin', 'name' => 'Hari Kemerdekaan RI', 'type' => 'holiday'],
                ],
            ]),
        ]);

        NationalHolidayService::fetchAndStore([2026]);
        NationalHolidayService::fetchAndStore([2026]);

        $this->assertSame(1, NationalHoliday::count());
    }

    public function test_missing_upstream_year_is_skipped_not_failed(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'tanggalmerah.upset.dev/api/holidays*' => Http::response([
                'success' => false,
                'error' => 'No data for year 2099.',
            ], 404),
        ]);

        $result = NationalHolidayService::fetchAndStore([2099]);

        $this->assertSame(0, $result['stored']);
        $this->assertSame([2099], $result['skipped']);
        $this->assertSame([], $result['failed']);
        $this->assertSame(0, NationalHoliday::count());
    }

    public function test_malformed_entries_are_dropped(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'tanggalmerah.upset.dev/api/holidays*' => Http::response([
                'success' => true,
                'data' => [
                    ['date' => 'not-a-date', 'name' => 'Broken'],
                    ['date' => '2026-05-01', 'name' => ''],
                    ['date' => '2026-05-01', 'name' => 'Hari Buruh', 'type' => 'nonsense'],
                ],
            ]),
        ]);

        $result = NationalHolidayService::fetchAndStore([2026]);

        $this->assertSame(1, $result['stored']);
        // Unknown type falls back to 'holiday' rather than being persisted raw.
        $this->assertDatabaseHas('national_holidays', [
            'date' => '2026-05-01',
            'name' => 'Hari Buruh',
            'type' => 'holiday',
        ]);
    }

    public function test_for_month_returns_holidays_keyed_by_date_preferring_holiday_over_leave(): void
    {
        NationalHoliday::create([
            'date' => '2026-03-19', 'name' => 'Nyepi', 'type' => 'holiday', 'year' => 2026,
        ]);
        NationalHoliday::create([
            'date' => '2026-03-19', 'name' => 'Cuti Bersama Nyepi', 'type' => 'leave', 'year' => 2026,
        ]);
        NationalHoliday::create([
            'date' => '2026-04-02', 'name' => 'Wafat Isa Almasih', 'type' => 'holiday', 'year' => 2026,
        ]);

        $holidays = NationalHolidayService::forMonth(2026, 3);

        $this->assertCount(1, $holidays);
        $this->assertSame('Nyepi', $holidays['2026-03-19']->name);
    }

    public function test_dashboard_shows_the_holiday_calendar_without_calling_the_api(): void
    {
        Http::preventStrayRequests();

        NationalHoliday::create([
            'date' => now()->startOfMonth()->addDays(4)->toDateString(),
            'name' => 'Hari Libur Uji',
            'type' => 'holiday',
            'year' => (int) now()->year,
        ]);

        $response = $this->actingAs($this->createAdminUser())->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('holidays');
        $response->assertViewHas('holidayMonth');
        $response->assertViewHas('upcomingHolidays');
        $response->assertSee('Hari Libur Uji');
    }

    public function test_dashboard_accepts_a_holiday_month_query_and_ignores_garbage(): void
    {
        $user = $this->createAdminUser();

        NationalHoliday::create([
            'date' => '2026-12-25', 'name' => 'Natal Uji', 'type' => 'holiday', 'year' => 2026,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard', ['holiday_month' => '2026-12']))
            ->assertOk()
            ->assertSee('Natal Uji');

        $this->actingAs($user)
            ->get(route('dashboard', ['holiday_month' => 'not-a-month']))
            ->assertOk()
            ->assertViewHas('holidayMonth', fn ($month) => $month->format('Y-m') === now()->format('Y-m'));
    }

    public function test_command_fetches_holidays_for_the_requested_years(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'tanggalmerah.upset.dev/api/holidays*' => Http::response([
                'success' => true,
                'data' => [
                    ['date' => '2026-12-25', 'day' => 'Jumat', 'name' => 'Hari Raya Natal', 'type' => 'holiday'],
                ],
            ]),
        ]);

        $this->artisan('holidays:fetch', ['--year' => ['2026']])
            ->assertExitCode(0);

        $this->assertDatabaseHas('national_holidays', ['name' => 'Hari Raya Natal']);
    }
}
