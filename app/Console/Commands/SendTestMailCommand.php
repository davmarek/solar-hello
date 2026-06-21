<?php

namespace App\Console\Commands;

use App\Mail\TestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-test-mail {email=test@example.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sending an email';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $recipient = $this->argument('email');
        $this->info("Sending test email to $recipient...");

        Mail::to($recipient)->send(new TestMail);
        $this->info('Email sent! Check your Mailpit dashboard at http://localhost:8025');
    }
}
