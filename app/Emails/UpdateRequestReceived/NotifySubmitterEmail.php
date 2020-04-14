<?php

namespace App\Emails\UpdateRequestReceived;

use App\Emails\Email;

class NotifySubmitterEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.update_request_received.notify_submitter.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hi ((SUBMITTER_NAME)),

Your update to ((RESOURCE_NAME)) (((RESOURCE_TYPE))) has been submitted and received. A member of the admin team will review it shortly.

If you have any questions, please get in touch at info@connectedkingston.uk.

Many thanks,

The Connected Kingston team
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Update Request Submitted';
    }
}
