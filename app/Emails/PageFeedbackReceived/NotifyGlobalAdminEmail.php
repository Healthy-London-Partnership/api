<?php

namespace App\Emails\PageFeedbackReceived;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.page_feedback_received.notify_global_admin.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello,

A site feedback form has been submitted for the page:
((FEEDBACK_URL))

Here are the details:

”((FEEDBACK_CONTENT))”
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Feedback received on the site';
    }
}
