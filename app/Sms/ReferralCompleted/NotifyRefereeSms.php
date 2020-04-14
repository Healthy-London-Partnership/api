<?php

namespace App\Sms\ReferralCompleted;

use App\Sms\Sms;

class NotifyRefereeSms extends Sms
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_completed.notify_referee.sms');
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

The referral you made to ((SERVICE_NAME)) has been marked as complete. ID: ((REFERRAL_ID))

Your client should have been contacted by now, but if they haven't then please contact them on ((SERVICE_PHONE)) or by email at ((SERVICE_EMAIL)).

Regards,
Connected Together.
EOT;
    }
}
