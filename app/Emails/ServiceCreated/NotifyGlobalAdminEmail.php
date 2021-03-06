<?php

namespace App\Emails\ServiceCreated;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.service_created.notify_global_admin.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello,

A new support listing has been created by an organisation admin and requires a Global Administrator to review:

((SERVICE_NAME))
((ORGANISATION_NAME))
((SERVICE_INTRO))
You will need to:

Check the content entered is acceptable, descriptive, plain English, and doesn’t have any typos
Add taxonomies to the support listing, based on the content
Enable the support listing if it is acceptable
If the support listing is not ready to go live, please contact the user that made the request to rectify the problems.

The user that made the request was ((ORGANISATION_ADMIN_NAME)), and you can contact them via ((ORGANISATION_ADMIN_EMAIL))

To review the support listing, follow this link: ((SERVICE_URL))

Many thanks,

NHS Connect Team
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Support listing Created (((SERVICE_NAME))) – Ready to review';
    }
}
