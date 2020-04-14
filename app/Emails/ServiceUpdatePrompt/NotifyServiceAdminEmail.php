<?php

namespace App\Emails\ServiceUpdatePrompt;

use App\Emails\Email;

class NotifyServiceAdminEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.service_update_prompt.notify_service_admin.email');
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
