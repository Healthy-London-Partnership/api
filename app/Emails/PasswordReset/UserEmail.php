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
        return <<<'EOT'
Hello,

We have received a request to reset your password. Please follow this link:
((PASSWORD_RESET_LINK))

If this is not you, please ignore this message.

If you need any further help please contact info@connectedtogether.org.uk
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Reset forgotten password';
    }
}
