<?php

namespace App\Console\Commands\Hlp\Notify\OrganisationAdminInvitee;

use Illuminate\Console\Command;

class SecondFollowUpsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hlp:notify:organisation-admin-invitee:second-follow-ups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends notifications out to the organisation admin invitees with the second follow ups';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // TODO
    }
}
