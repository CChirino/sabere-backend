<?php

namespace App\Jobs;

use App\Mail\CircularMail;
use App\Models\Circular;
use App\Models\CircularRecipient;
use App\Models\User;
use App\Notifications\CircularNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCircularNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Circular $circular,
    ) {}

    public function handle(): void
    {
        $audience = $this->circular->audience;

        $query = User::query();
        if ($audience !== 'all') {
            $query->role($audience);
        }

        $users = $query->get();

        foreach ($users as $user) {
            $recipient = CircularRecipient::firstOrCreate(
                ['circular_id' => $this->circular->id, 'user_id' => $user->id],
                ['email_sent' => false, 'push_sent' => false]
            );

            if ($this->circular->send_email && ! $recipient->email_sent) {
                try {
                    Mail::to($user->email)->send(new CircularMail($this->circular, $user));
                    $recipient->update(['email_sent' => true]);
                } catch (\Exception $e) {
                    Log::error("Failed to send circular email to {$user->email}: ".$e->getMessage());
                }
            }

            if ($this->circular->send_push && ! $recipient->push_sent) {
                try {
                    $user->notify(new CircularNotification($this->circular));
                    $recipient->update(['push_sent' => true]);
                } catch (\Exception $e) {
                    Log::error("Failed to send circular push to user {$user->id}: ".$e->getMessage());
                }
            }
        }

        $this->circular->markAsSent();
    }
}
