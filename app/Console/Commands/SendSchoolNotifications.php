<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\School;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SendSchoolNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:school-notifications {--threshold= : Threshold in minutes (overrides env SCHOOL_NOTIFY_THRESHOLD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications to parents around school start and end times';

    public function handle(FirebaseNotificationService $firebase)
    {
    $thresholdOption = $this->option('threshold');
    $threshold = $thresholdOption !== null ? (int) $thresholdOption : (int) env('SCHOOL_NOTIFY_THRESHOLD', 30);
        $now = Carbon::now();

        $this->info('Running school notifications at ' . $now->toDateTimeString());

        $schools = School::all();
        foreach ($schools as $school) {
            // Build times for today
            try {
                $start = Carbon::createFromFormat('H:i', $school->start_time)->setDate($now->year, $now->month, $now->day);
                $end = Carbon::createFromFormat('H:i', $school->end_time)->setDate($now->year, $now->month, $now->day);
            } catch (\Exception $e) {
                continue;
            }

            // Target times: exactly threshold minutes before start and end
            $targetStart = $start->copy()->subMinutes($threshold);
            $targetEnd = $end->copy()->subMinutes($threshold);

            // Compare by minute to trigger only once (cron should run at least once per minute)
            if ($now->format('Y-m-d H:i') === $targetStart->format('Y-m-d H:i')) {
                $cacheKey = "school:{$school->id}:start:" . $targetStart->toDateString();
                // add returns true only if the key did not exist; keep it for 24 hours
                if (Cache::add($cacheKey, true, 60 * 60 * 24)) {
                    $this->sendToParents($school, $firebase, 'School is starting soon', "School {$school->name} is about to start at {$school->start_time}");
                } else {
                    $this->info("Start notification already sent for school {$school->id} today");
                }
            }

            if ($now->format('Y-m-d H:i') === $targetEnd->format('Y-m-d H:i')) {
                $cacheKey = "school:{$school->id}:end:" . $targetEnd->toDateString();
                if (Cache::add($cacheKey, true, 60 * 60 * 24)) {
                    $this->sendToParents($school, $firebase, 'School is ending soon', "School {$school->name} is about to end at {$school->end_time}");
                } else {
                    $this->info("End notification already sent for school {$school->id} today");
                }
            }
        }

        return 0;
    }

    protected function sendToParents(School $school, FirebaseNotificationService $firebase, string $title, string $body)
    {
        $parents = User::role('parent')->where('school_id', $school->id)->get();
        $tokens = $parents->pluck('device_token')->filter()->unique()->values()->all();
        if (empty($tokens)) {
            $this->info("No parent tokens for school {$school->id}");
            return;
        }

        $firebase->sendToTokens($tokens, $title, $body, ['school_id' => (string)$school->id]);
        $this->info('Sent to parents of school ' . $school->id . ': ' . count($tokens) . ' tokens');
    }
}
