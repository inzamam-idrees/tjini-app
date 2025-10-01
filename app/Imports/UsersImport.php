<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UsersImport implements ToCollection, WithHeadingRow, WithValidation, WithChunkReading, WithBatchInserts, SkipsOnFailure
{
    use SkipsFailures;
    protected $role;
    protected $school_id;
    protected $batch_id;
    protected $total_rows = 0;
    protected $processed_rows = 0;
    protected $success_count = 0;
    protected $skip_count = 0;
    protected $error_logs = [];

    public function __construct(string $role, int $school_id, string $batch_id)
    {
        $this->role = $role;
        $this->school_id = $school_id;
        $this->batch_id = $batch_id;
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function collection(Collection $rows)
    {
        // Add the number of rows in this chunk to the running total
        $this->total_rows += count($rows);
        
        foreach ($rows as $index => $row) {
            $this->processed_rows++;
            
            try {
                // Normalize and check for existing email (case-insensitive)
                $email = isset($row['email']) ? trim(strtolower($row['email'])) : null;
                if ($email && User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                    $this->skip_count++;
                    $this->logSkip($index + 2, 'Skipped - Email already exists: ' . $email);
                    continue;
                }

                $password = Str::random(10);
                
                $user = User::create([
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'email' => $email,
                    'password' => Hash::make($password),
                    'school_id' => $this->school_id,
                    'relation' => $this->role === 'parent' ? ($row['relation'] ?? null) : null,
                    'child_name' => $this->role === 'parent' ? ($row['child_name'] ?? null) : null,
                    'is_primary' => $this->role === 'parent' ? ($row['is_primary'] ?? false) : false,
                ]);

                // Assign role based on type
                if ($this->role === 'staff') {
                    $user->assignRole($row['staff_role'] ?? 'viewer');
                } else {
                    $user->assignRole($this->role);
                }

                $this->success_count++;
                $this->logSuccess($index + 2, $user);

            } catch (\Exception $e) {
                $msg = $e->getMessage();
                // Treat DB duplicate / unique constraint errors as skipped rows
                if (stripos($msg, 'duplicate') !== false || stripos($msg, '1062') !== false || stripos($msg, 'unique') !== false) {
                    $this->skip_count++;
                    $this->logSkip($index + 2, 'Skipped - duplicate entry for email: ' . ($email ?? '')); 
                } else {
                    $this->logError($index + 2, $msg);
                }
            }

            // Update progress after each row
            $this->updateProgress();
        }

        // Store final results
        $this->storeFinalResults();
    }

    /**
     * Handle row validation failures (SkipsOnFailure)
     * Count the failed row as part of the total and mark it as skipped with the failure messages.
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            // Count this failed row in totals and skipped, and mark it processed
            $this->total_rows += 1;
            $this->processed_rows += 1;
            $this->skip_count += 1;

            $errors = is_array($failure->errors()) ? implode('; ', $failure->errors()) : (string) $failure->errors();
            $rowNumber = $failure->row();

            $this->logSkip($rowNumber, 'Validation failed: ' . $errors);
        }

        // Update cache so UI can pick up progress for validation failures too
        $this->updateProgress();
    }

    protected function logError($row, $message)
    {
        $this->error_logs[] = [
            'row' => $row,
            'type' => 'error',
            'message' => $message,
            'timestamp' => now()
        ];
    }

    protected function logSkip($row, $message)
    {
        $this->error_logs[] = [
            'row' => $row,
            'type' => 'skipped',
            'message' => $message,
            'timestamp' => now()
        ];
    }

    protected function logSuccess($row, User $user)
    {
        $this->error_logs[] = [
            'row' => $row,
            'type' => 'success',
            'message' => "Created user: {$user->email}",
            'timestamp' => now()
        ];
    }

    protected function updateProgress()
    {
        $progress = [
            'total' => $this->total_rows,
            'processed' => $this->processed_rows,
            'success' => $this->success_count,
            'skipped' => $this->skip_count,
            'percentage' => $this->total_rows > 0 ? ($this->processed_rows / $this->total_rows) * 100 : 0
        ];

        Cache::put("import_progress_{$this->batch_id}", $progress, now()->addHours(1));
    }

    protected function storeFinalResults()
    {
        $results = [
            'total' => $this->total_rows,
            'success' => $this->success_count,
            'skipped' => $this->skip_count,
            'logs' => $this->error_logs,
            'completed_at' => now()
        ];

        Cache::put("import_results_{$this->batch_id}", $results, now()->addDays(1));
    }

    public function rules(): array
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
        ];

        if ($this->role === 'parent') {
            $rules['child_name'] = 'required|string|max:255';
            $rules['relation'] = 'nullable|string|max:255';
            $rules['is_primary'] = 'nullable|boolean';
        }

        if ($this->role === 'staff') {
            $rules['staff_role'] = 'required|in:viewer,dispatcher';
        }

        return $rules;
    }
}