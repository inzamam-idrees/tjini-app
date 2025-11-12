<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\School;
use App\Models\User;
use App\Models\Notification;
use App\Models\Dispatchee;
use App\Services\FirebaseNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        // Use the application timezone (set in config/app.php) so Carbon::now() reflects UTC+1 (or DST-adjusted)
        $timezone = config('app.timezone');
        $now = Carbon::now($timezone);

        $this->info('Running school notifications at ' . $now->toDateTimeString());
        // Log::info('Running school notifications at ' . $now->toDateTimeString());

        $schools = School::all();
        foreach ($schools as $school) {
            // Build times for today
            try {
                $this->info('Raw start_time: ' . var_export($school->start_time, true));
                $this->info('Raw end_time: ' . var_export($school->end_time, true));
                
                // Strip any seconds from the time
                $startTime = substr($school->start_time, 0, 5);
                $endTime = substr($school->end_time, 0, 5);
                
                $this->info('Formatted start_time: ' . $startTime);
                $this->info('Formatted end_time: ' . $endTime);
                
                // Create times using the app timezone so comparisons are consistent
                $start = Carbon::createFromFormat('H:i', $startTime, $timezone)->setDate($now->year, $now->month, $now->day);
                $this->info('Start DateTime: ' . $start);
                $end = Carbon::createFromFormat('H:i', $endTime, $timezone)->setDate($now->year, $now->month, $now->day);
                $this->info('End DateTime: ' . $end);
            } catch (\Exception $e) {
                $this->error("Invalid time format for school {$school->id}: " . $e->getMessage());
                continue;
            }

            // Target times: exactly threshold minutes before start and end
            $targetStart = $start->copy()->subMinutes($threshold);
            $targetEnd = $end->copy()->subMinutes($threshold);
            $this->info("Now Time {$now->format('Y-m-d H:i')}, School {$school->id} Start Target {$targetStart->format('Y-m-d H:i')}, End Target {$targetEnd->format('Y-m-d H:i')}");

            // Compare by minute to trigger only once (cron should run at least once per minute)
            if ($now->format('Y-m-d H:i') === $targetStart->format('Y-m-d H:i')) {
                $cacheKey = "school:{$school->id}:start:" . $targetStart->toDateString();
                // add returns true only if the key did not exist; keep it for 24 hours
                // if (Cache::add($cacheKey, true, 60 * 60 * 24)) {
                    $type = 'school-start';
                    $message = "School {$school->name} is about to start at {$school->start_time}";
                    $this->sendToParents($school, $firebase, $type, $message);
                // } else {
                //     $this->info("Start notification already sent for school {$school->id} today");
                // }
            }

            if ($now->format('Y-m-d H:i') === $targetEnd->format('Y-m-d H:i')) {
                $cacheKey = "school:{$school->id}:end:" . $targetEnd->toDateString();
                // if (Cache::add($cacheKey, true, 60 * 60 * 24)) {
                    $type = 'school-end';
                    $message = "School {$school->name} is about to end at {$school->end_time}";
                    $this->sendToParents($school, $firebase, $type, $message);
                // } else {
                //     $this->info("End notification already sent for school {$school->id} today");
                // }
            }
        }

        $this->info("No Notifications to send at this time.");
        return 0;
    }

    protected function sendToParents(School $school, FirebaseNotificationService $firebase, string $title, string $body)
    {
        $parents = User::role('parent')->where('school_id', $school->id)->where('is_primary', true)->get();
        $tokens = $parents->pluck('device_token')->filter()->unique()->values()->all();
        if (empty($tokens)) {
            $this->info("No primary parent tokens for school {$school->id}");
            return;
        }

        $firebase->sendToTokens($tokens, $title, $body, ['school_id' => (string)$school->id, 'cron' => 'school_notifications']);
        $this->info('Sent to primary parents of school ' . $school->id . ': ' . count($tokens) . ' tokens');
        // Delete any existing notifications of this type for the school to avoid clutter
        Notification::where('school_id', $school->id)->delete();
        Dispatchee::where('school_id', $school->id)->delete();
        // Save new initial notification record
        $fromId = User::role('admin')->where('school_id', $school->id)->first()->id ?? null;
        foreach ($parents as $parent) {
            Notification::create([
                'from_user_id' => $fromId,
                'type' => $title,
                'message' => $body,
                'value' => $title == 'school-start' ? $school->start_time : $school->end_time,
                'school_id' => $school->id,
                'to_user_id' => $parent->id,
                'sender_role' => 'admin'
            ]);
        }
    }
}
