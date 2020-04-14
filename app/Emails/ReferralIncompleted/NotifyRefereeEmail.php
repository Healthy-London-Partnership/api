<?php

namespace App\Emails\ReferralIncompleted;

use App\Emails\Email;

class NotifyRefereeEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_incompleted.notify_referee.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hi ((REFEREE_NAME)),

The referral you made to ((SERVICE_NAME)) has been marked as incomplete with the following message:

“((REFERRAL_STATUS))“.

If you believe the service did not try to contact the client, or you have any other feedback regarding the connection, please contact us at info@connectedkingston.uk.

Alternatively, you can complete our feedback form:
https://docs.google.com/forms/d/e/1FAIpQLSe38Oe0vsqLRQbcBjYrGzMooBJKkYqFWAlHy4dcgwJnMFg9dQ/viewform?usp=pp_url&entry.400427747=((REFERRAL_ID))

Many thanks,

The Connected Kingston team.
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Referral Incompleted';
    }
}
