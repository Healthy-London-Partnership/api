<?php

namespace App\Emails\ReferralIncompleted;

use App\Emails\Email;

class NotifyClientEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_incompleted.notify_client.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return 'Pending to be sent. Content will be filled once sent.';
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        // TODO: Implement getSubject() method.
    }
}
