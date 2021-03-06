<?php

namespace App\Sms\ReferralIncompleted;

use App\Sms\Sms;

class NotifyRefereeSms extends Sms
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_incompleted.notify_referee.sms');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Connect: Hi ((REFEREE_NAME)),

Your referral (ID: ((REFERRAL_ID))) has been marked as incomplete. This means the support listing tried to contact the client but couldn't.

For details: hlp.admin.connect@nhs.net

NHS Connect Team
EOT;
    }
}
