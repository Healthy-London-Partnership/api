<?php

namespace Tests\Unit\Console\Commands\Hlp\Notify\OrganisationAdminInvitee;

use App\Console\Commands\Hlp\Notify\OrganisationAdminInvitee\SecondFollowUpsCommand;
use App\Emails\OrganisationAdminInviteSecondFollowUps\NotifyInviteeEmail;
use App\Models\Organisation;
use App\Models\OrganisationAdminInvite;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SecondFollowUpsCommandTest extends TestCase
{
    /**
     * @param int $week
     * @dataProvider weeksSixToTenDataProvider
     */
    public function test_emails_sent_for_weeks_six_to_ten(int $week)
    {
        Queue::fake();

        $organisation = factory(Organisation::class)->create([
            'name' => 'Acme Org',
            'email' => 'acme.org@example.com',
            'description' => 'Lorem ipsum',
            'url' => 'http://acme.com',
            'phone' => '011300000000',
        ]);

        factory(OrganisationAdminInvite::class)->create([
            'id' => 'test-id',
            'organisation_id' => $organisation->id,
            'email' => 'foo.org@example.com',
            'created_at' => Date::today()->subWeeks($week),
        ]);

        Artisan::call(SecondFollowUpsCommand::class);

        Queue::assertPushedOn('notifications', NotifyInviteeEmail::class);
        Queue::assertPushed(NotifyInviteeEmail::class, function (NotifyInviteeEmail $email) {
            $expectedValues = [
                'ORGANISATION_NAME' => 'Acme Org',
                'ORGANISATION_ADDRESS' => 'N/A', // TODO: Blocked until location work is finished.
                'ORGANISATION_URL' => 'http://acme.com',
                'ORGANISATION_EMAIL' => 'acme.org@example.com',
                'ORGANISATION_PHONE' => '011300000000',
                'ORGANISATION_SOCIAL_MEDIA' => 'N/A', // TODO: Blocked until social media work is finished.
                'ORGANISATION_DESCRIPTION' => 'Lorem ipsum',
                'INVITE_URL' => config('hlp.backend_uri') . '/organisation-admin-invites/test-id',
            ];

            return ($email->to === 'foo.org@example.com') && ($email->values == $expectedValues);
        });

        $this->markTestIncomplete('Need to merge in org data schema updates');
    }

    /**
     * @param int $week
     * @dataProvider weeksBeforeAndAfterSixToTenDataProvider
     */
    public function test_emails_not_sent_for_weeks_before_and_after_six_to_ten(int $week)
    {
        Queue::fake();

        factory(OrganisationAdminInvite::class)->create([
            'created_at' => Date::today()->subWeeks($week),
        ]);

        Artisan::call(SecondFollowUpsCommand::class);

        Queue::assertNotPushed(NotifyInviteeEmail::class);
    }

    /**
     * @return \int[][]
     */
    public function weeksSixToTenDataProvider(): array
    {
        return [
            [5],
            [6],
            [7],
            [8],
            [9],
        ];
    }

    /**
     * @return \int[][]
     */
    public function weeksBeforeAndAfterSixToTenDataProvider(): array
    {
        return [
            [-1],
            [3],
            [4],
            [10],
            [11],
        ];
    }
}
