<?php

namespace App\Emails\UpdateRequestRejected;

use App\Emails\Email;

class NotifySubmitterEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.update_request_rejected.notify_submitter.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hi ((SUBMITTER_NAME)),

Your update request for the ((RESOURCE_NAME)) (((RESOURCE_TYPE))) on ((REQUEST_DATE)) has been rejected.

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
        return 'Update Request Rejected';
    }
}
