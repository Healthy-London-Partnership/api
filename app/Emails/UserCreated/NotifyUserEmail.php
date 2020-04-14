<?php

namespace App\Emails\UserCreated;

use App\Emails\Email;

class NotifyUserEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.user_created.notify_user.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hi ((NAME)),

An account has been created for you using this email address. You can log in to the Connected Kingston admin portal at:
http://admin.connectedkingston.uk

Permissions:
((PERMISSIONS))

If you have any questions, you can email us at info@connectedkingston.uk

Many thanks,
The Connected Kingston team
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Account Created';
    }
}
