<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\Attendance;
use App\Models\Salary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use League\Csv\Writer;

class BulkUploadTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;
    protected $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
        $this->organization = Organization::factory()->create();
    }

    public function test_can_upload_employees()
    {
        $csv = Writer::createFromString('');
        $csv->insertOne([
            'organization_id',
            'name',
            'email',
            'phone',
            'address',
            'position',
            'department',
            'joining_date',
            'salary'
        ]);

        $csv->insertOne([
            $this->organization->id,
            'John Doe',
            'john@example.com',
            '1234567890',
            '123 Street',
            'Developer',
            'IT',
            '2024-01-01',
            '50000'
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'employees.csv',
            $csv->toString()
        );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/bulk-upload/employees', [
            'file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'total',
                'success',
                'failed',
                'errors'
            ]);

        $this->assertDatabaseHas('employees', [
            'email' => 'john@example.com',
            'organization_id' => $this->organization->id
        ]);
    }

    public function test_can_upload_loans()
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        $csv = Writer::createFromString('');
        $csv->insertOne([
            'organization_id',
            'employee_id',
            'amount',
            'interest_rate',
            'term_months',
            'start_date',
            'purpose',
            'status'
        ]);

        $csv->insertOne([
            $this->organization->id,
            $employee->id,
            '10000',
            '12.5',
            '12',
            '2024-01-01',
            'Personal Loan',
            'pending'
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'loans.csv',
            $csv->toString()
        );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/bulk-upload/loans', [
            'file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'total',
                'success',
                'failed',
                'errors'
            ]);

        $this->assertDatabaseHas('loans', [
            'employee_id' => $employee->id,
            'organization_id' => $this->organization->id,
            'amount' => 10000
        ]);
    }

    public function test_can_upload_attendance()
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        $csv = Writer::createFromString('');
        $csv->insertOne([
            'employee_id',
            'organization_id',
            'date',
            'check_in',
            'check_out',
            'status',
            'notes'
        ]);

        $csv->insertOne([
            $employee->id,
            $this->organization->id,
            '2024-01-01',
            '09:00:00',
            '18:00:00',
            'present',
            'Regular attendance'
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'attendance.csv',
            $csv->toString()
        );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/bulk-upload/attendance', [
            'file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'total',
                'success',
                'failed',
                'errors'
            ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'organization_id' => $this->organization->id,
            'date' => '2024-01-01'
        ]);
    }

    public function test_can_upload_salaries()
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        $csv = Writer::createFromString('');
        $csv->insertOne([
            'employee_id',
            'organization_id',
            'month',
            'year',
            'basic_salary',
            'allowances',
            'deductions',
            'payment_date',
            'payment_method',
            'reference_number',
            'status',
            'notes'
        ]);

        $csv->insertOne([
            $employee->id,
            $this->organization->id,
            '1',
            '2024',
            '50000',
            '5000',
            '2000',
            '2024-01-31',
            'Bank Transfer',
            'REF123',
            'paid',
            'Monthly salary'
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'salaries.csv',
            $csv->toString()
        );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/bulk-upload/salaries', [
            'file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'total',
                'success',
                'failed',
                'errors'
            ]);

        $this->assertDatabaseHas('salaries', [
            'employee_id' => $employee->id,
            'organization_id' => $this->organization->id,
            'month' => 1,
            'year' => 2024
        ]);
    }

    public function test_can_download_template()
    {
        $types = ['employees', 'loans', 'attendance', 'salaries'];

        foreach ($types as $type) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->getJson("/api/bulk-upload/template/{$type}");

            $response->assertStatus(200)
                ->assertHeader('Content-Type', 'text/csv')
                ->assertHeader('Content-Disposition', "attachment; filename=\"{$type}_template.csv\"");
        }
    }

    public function test_validates_file_type()
    {
        $file = UploadedFile::fake()->create('test.txt', 100);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/bulk-upload/employees', [
            'file' => $file
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_validates_file_size()
    {
        $file = UploadedFile::fake()->create('test.csv', 10241); // 10MB + 1KB

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/bulk-upload/employees', [
            'file' => $file
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_handles_invalid_data()
    {
        $csv = Writer::createFromString('');
        $csv->insertOne([
            'organization_id',
            'name',
            'email',
            'phone',
            'address',
            'position',
            'department',
            'joining_date',
            'salary'
        ]);

        $csv->insertOne([
            'invalid_id', // Invalid organization ID
            'John Doe',
            'invalid_email', // Invalid email
            '1234567890',
            '123 Street',
            'Developer',
            'IT',
            'invalid_date', // Invalid date
            'invalid_salary' // Invalid salary
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'employees.csv',
            $csv->toString()
        );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/bulk-upload/employees', [
            'file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'total' => 1,
                'success' => 0,
                'failed' => 1
            ])
            ->assertJsonStructure([
                'errors'
            ]);
    }
} 