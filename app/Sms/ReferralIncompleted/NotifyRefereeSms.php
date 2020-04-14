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
     * @return string|null
     */
    protected function getReference(): ?string
    {
        return null;
    }

    /**
     * @return string|null
     */
    protected function getSenderId(): ?string
    {
        return null;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Connected Together: Hi ((REFEREE_NAME)),

Your referral (ID: ((REFERRAL_ID))) has been marked as incomplete. This means the service tried to contact the client but couldn't.

For details: info@connectedtogether.org.uk

The Connected Together team
EOT;
    }
}
