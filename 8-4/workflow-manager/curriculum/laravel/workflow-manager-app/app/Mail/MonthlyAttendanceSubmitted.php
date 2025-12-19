<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MonthlyAttendanceSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Client $client;
    public int $year;
    public int $month;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Client $client, int $year, int $month)
    {
        $this->user  = $user;
        $this->client = $client;
        $this->year  = $year;
        $this->month = $month;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this
            ->subject("【月次申請】{$this->year}年{$this->month}月分")
            ->view('emails.monthly_attendance_submitted');
    }
}
