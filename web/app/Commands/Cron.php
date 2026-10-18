<?php

namespace App\Commands;

use App\Libraries\Mailer;
use App\Modules\Core\Models\AppSettingModel;
use App\Modules\Lms\Services\LearningReminders;
use App\Modules\Shop\Services\Membership;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * The scheduled job. Run every 5 minutes from cPanel → Cron Jobs:
 *
 *   php /home/USER/prayer-journal/web/spark app:cron
 *
 * (deploy.sh adds this for you.) Each run:
 *   1. checks memberships: renewal reminders, missed payments, final notices
 *      and automatic suspension after the grace period
 *   2. once a day, after the configured hour, sends personal learning reminders
 *   3. delivers queued emails
 */
class Cron extends BaseCommand
{
    protected $group       = 'Prayer Journal';
    protected $name        = 'app:cron';
    protected $description = 'Membership billing checks, learning reminders and email delivery (run every 5 minutes).';
    protected $usage       = 'app:cron [--reminders-now]';
    protected $options     = ['--reminders-now' => 'Send learning reminders now instead of waiting for the daily run.'];

    public function run(array $params)
    {
        $lock = WRITEPATH . 'cron.lock';
        $fh   = fopen($lock, 'c');
        if (! $fh || ! flock($fh, LOCK_EX | LOCK_NB)) {
            CLI::write('Another cron run is still working; skipping.', 'yellow');

            return;
        }

        try {
            $m = Membership::checkAll();
            CLI::write('Memberships: ' . http_build_query($m, '', ', '));

            // Student status: warn two weeks ahead, then move lapsed students to the standard price.
            $s = \App\Modules\Shop\Services\StudentVerification::checkAll();
            CLI::write('Student status: ' . http_build_query($s, '', ', '));

            $today = date('Y-m-d');
            $hour  = (int) site_setting('reminder_hour', '9');
            if (CLI::getOption('reminders-now') || (site_setting('reminders_last_run') !== $today && (int) date('G') >= $hour)) {
                (new AppSettingModel())->put('reminders_last_run', $today);
                site_setting('__flush__');
                $r = LearningReminders::run();
                CLI::write('Learning reminders: ' . $r['learners'] . ' learners, ' . $r['starters'] . ' new members');
            }

            Mailer::markCronRun();
            [$sent, $failed] = Mailer::flush(150);
            CLI::write('Emails: ' . $sent . ' sent' . ($failed ? ', ' . $failed . ' failed (will retry)' : ''));

            // Keep the queue tidy: sent mail older than 90 days.
            db_connect()->table('email_queue')->where('status', 'sent')->where('created_at <', date('Y-m-d H:i:s', strtotime('-90 days')))->delete();
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }
}
