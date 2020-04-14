<?php

namespace App\Emails\UserRolesUpdated;

use App\Emails\Email;

class NotifyUserEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.user_roles_updated.notify_user.email');
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
