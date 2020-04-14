<?php

namespace App\Emails\PasswordReset;

use App\Emails\Email;

class UserEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.password_reset.email');
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
