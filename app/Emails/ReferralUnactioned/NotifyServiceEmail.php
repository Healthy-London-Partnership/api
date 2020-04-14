<?php

namespace App\Emails\ReferralUnactioned;

use App\Emails\Email;

class NotifyServiceEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_unactioned.notify_service.email');
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
