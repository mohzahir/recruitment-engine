<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\LeadCampaign;
use App\Mail\RecruitmentPitch;
use Exception;

class ProcessCampaignsCommand extends Command
{
    protected $signature = 'campaigns:process';
    protected $description = 'Find scheduled emails and send them via SendGrid.';

    public function handle()
    {
        $this->info("Waking up the Email Dispatcher...");

        // Find all emails scheduled for right now (or earlier) that haven't been sent yet
        $pendingEmails = LeadCampaign::with(['lead', 'campaign'])
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        $count = $pendingEmails->count();

        if ($count === 0) {
            $this->info("Inbox zero! No emails scheduled to go out right now.");
            return;
        }

        $this->info("Found {$count} emails ready to send. Dispatching...");

        foreach ($pendingEmails as $dispatch) {
            $lead = $dispatch->lead;
            $campaign = $dispatch->campaign;

            // Safety check: Ensure the lead actually has an email
            if (empty($lead->email)) {
                $dispatch->update(['status' => 'failed']);
                $this->error("Failed: Lead ID {$lead->id} has no primary email.");
                continue;
            }

            try {
                $this->output->write("Sending to {$lead->email} at {$lead->company}... ");

                // Prepare the mailer targeting the Primary decision maker
                $mailer = Mail::to($lead->email);

                // If we found CCs from Apollo, securely attach them to the email
                if (!empty($lead->cc_emails) && is_array($lead->cc_emails)) {
                    $mailer->cc($lead->cc_emails);
                }

                // Send the beautifully parsed template
                $mailer->send(new RecruitmentPitch($lead, $campaign));

                // Mark as successfully sent and record the exact timestamp
                $dispatch->update([
                    'status' => 'sent',
                    'sent_at' => now()
                ]);

                $this->info("Success!");

            } catch (Exception $e) {
                // If SendGrid rejects it (e.g., bounced email), catch the error so the script doesn't crash
                $dispatch->update(['status' => 'failed']);
                $this->error("Failed! Error: " . $e->getMessage());
            }

            // Pause for 1 second between emails to maintain a healthy sender reputation with SendGrid
            sleep(1); 
        }

        $this->info("Dispatch complete!");
    }
}