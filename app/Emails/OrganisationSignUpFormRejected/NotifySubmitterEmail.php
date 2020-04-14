<?php

namespace App\Emails\OrganisationSignUpFormRejected;

use App\Emails\Email;

class NotifySubmitterEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.organisation_sign_up_form_rejected.notify_submitter.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hi ((SUBMITTER_NAME)),

Thank you for submitting your request to have ((ORGANISATION_NAME)) listed on Connected Kingston.

Unfortunately, your request to list ((ORGANISATION_NAME)) on Connected Kingston on ((REQUEST_DATE)) has been rejected. This is due to the organisation/service not meeting the terms and conditions of being listed on Connected Kingston.

You can read more about our terms and conditions: https://www.connectedkingston.uk/terms-and-conditions

If you have any questions, please contact us at info@connectedkingston.uk.

Many thanks,

The Connected Kingston team
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Connected Kingston – New Organisation not approved';
    }
}
