<?php

namespace App\Emails\ReferralCreated;

use App\Emails\Email;

class NotifyServiceEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_created.notify_service.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello,

You’ve received a referral to your service!

Referral ID: ((REFERRAL_ID))
Service: ((REFERRAL_SERVICE_NAME))
Client initials: ((REFERRAL_INITIALS))
Contact via: ((CONTACT_INFO))

This is a ((REFERRAL_TYPE))

Please contact the client via ((REFERRAL_CONTACT_METHOD)) within the next 10 working days.

You can see further details of the referral, and mark as completed:
http://admin.connect.nhs.uk/referrals

If you have any questions, please contact us at hlp.admin.connect@nhs.net.

Many thanks,

NHS Connect Team 
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'New Referral Received';
    }
}
