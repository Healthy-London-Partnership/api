<?php

namespace App\Emails\OrganisationAdminInviteInitial;

use App\Emails\Email;

class NotifyInviteeEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.organisation_admin_invite_initial.notify_invitee.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
TODO
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'TODO';
    }
}
