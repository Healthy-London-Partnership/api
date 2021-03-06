<?php

namespace App\Emails\ReferralStillUnactioned;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_still_unactioned.notify_global_admin.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
((REFERRAL_SERVICE_NAME)) has a referral about to expire. The details are as follows:

Referral made: ((REFERRAL_CREATED_AT))
((REFERRAL_TYPE))
Client initials: ((REFERRAL_INITIALS))
Referral ID: ((REFERRAL_ID))
Referral email address: ((SERVICE_REFERRAL_EMAIL))
Users attached to this support listing are as follows:

Support listing Worker(s):
((SERVICE_WORKERS))

Support listing Admin(s):
((SERVICE_ADMINS))

Organisation Admin(s):
((ORGANISATION_ADMINS))
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return '((REFERRAL_SERVICE_NAME)) has a referral about to expire';
    }
}
