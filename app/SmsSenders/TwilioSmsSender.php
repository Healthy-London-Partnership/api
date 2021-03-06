<?php

namespace App\SmsSenders;

use App\Contracts\SmsSender;
use App\Contracts\VariableSubstituter;
use App\Sms\Sms;
use Twilio\Rest\Client;

class TwilioSmsSender implements SmsSender
{
    /**
     * @inheritDoc
     */
    public function send(Sms $sms)
    {
        /** @var \App\Contracts\VariableSubstituter $variableSubstituter */
        $variableSubstituter = resolve(VariableSubstituter::class);

        $content = $variableSubstituter->substitute(
            $sms->getContent(),
            $sms->values
        );

        /** @var \Twilio\Rest\Client $client */
        $client = resolve(Client::class);

        $message = $client->messages->create(
            $this->parsePhoneNumber($sms->to),
            [
                'from' => config('hlp.twilio.from'),
                'body' => $content,
            ]
        );

        $sms->notification->update(['message' => $content]);

        if (config('app.debug')) {
            logger()->debug('SMS sent', $message->toArray());
        }
    }

    /**
     * @param string $to
     * @return string
     */
    protected function parsePhoneNumber(string $to): string
    {
        return '+44' . mb_substr($to, 1);
    }
}
