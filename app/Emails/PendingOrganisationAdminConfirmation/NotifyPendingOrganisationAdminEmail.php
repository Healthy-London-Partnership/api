<?php

namespace App\Emails\PendingOrganisationAdminConfirmation;

use App\Emails\Email;

class NotifyPendingOrganisationAdminEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.pending_organisation_admin_confirmation.notify_pending_organisation_admin.email');
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
