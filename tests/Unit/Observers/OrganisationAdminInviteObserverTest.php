<?php

namespace Tests\Unit\Observers;

use App\Emails\OrganisationAdminInviteInitial\NotifyInviteeEmail;
use App\Generators\AdminUrlGenerator;
use App\Models\Organisation;
use App\Models\OrganisationAdminInvite;
use App\Observers\OrganisationAdminInviteObserver;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrganisationAdminInviteObserverTest extends TestCase
{
    public function test_created_sends_emails_to_invitee()
    {
        Queue::fake();

        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['name', 'Acme Org'],
                ['email', 'acme.org@example.com'],
                ['description', 'Lorem ipsum'],
            ]));

        $organisationAdminInviteMock = $this->createMock(OrganisationAdminInvite::class);
        $organisationAdminInviteMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['email', 'acme.org@example.com'],
                ['organisation', $organisationMock],
            ]));

        $adminUrlGeneratorMock = $this->createMock(AdminUrlGenerator::class);
        $adminUrlGeneratorMock->expects($this->once())
            ->method('generateOrganisationAdminInviteUrl')
            ->with($organisationAdminInviteMock)
            ->willReturn('test-invite-url');

        $observer = new OrganisationAdminInviteObserver($adminUrlGeneratorMock);
        $observer->created($organisationAdminInviteMock);

        Queue::assertPushedOn('notifications', NotifyInviteeEmail ::class);
        Queue::assertPushed(NotifyInviteeEmail ::class, function (NotifyInviteeEmail $email): bool {
            return $email->values == [
                    'ORGANISATION_NAME' => 'Acme Org',
                    'ORGANISATION_ADDRESS' => 'N/A',
                    'ORGANISATION_URL' => 'N/A',
                    'ORGANISATION_EMAIL' => 'acme.org@example.com',
                    'ORGANISATION_PHONE' => 'N/A',
                    'ORGANISATION_SOCIAL_MEDIA' => 'N/A',
                    'ORGANISATION_DESCRIPTION' => 'Lorem ipsum',
                    'INVITE_URL' => 'test-invite-url',
                ];
        });
    }

    public function test_created_sends_emails_to_invitee_with_all_fields()
    {
        Queue::fake();

        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['name', 'Acme Org'],
                ['email', 'acme.org@example.com'],
                ['description', 'Lorem ipsum'],
                ['url', 'http://acme.com'],
                ['phone', '011300000000'],
            ]));

        $organisationAdminInviteMock = $this->createMock(OrganisationAdminInvite::class);
        $organisationAdminInviteMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['email', 'acme.org@example.com'],
                ['organisation', $organisationMock],
            ]));

        $adminUrlGeneratorMock = $this->createMock(AdminUrlGenerator::class);
        $adminUrlGeneratorMock->expects($this->once())
            ->method('generateOrganisationAdminInviteUrl')
            ->with($organisationAdminInviteMock)
            ->willReturn('test-invite-url');

        $observer = new OrganisationAdminInviteObserver($adminUrlGeneratorMock);
        $observer->created($organisationAdminInviteMock);

        Queue::assertPushedOn('notifications', NotifyInviteeEmail ::class);
        Queue::assertPushed(NotifyInviteeEmail ::class, function (NotifyInviteeEmail $email): bool {
            return $email->values == [
                    'ORGANISATION_ADDRESS' => 'N/A', // TODO: Blocked until location work is finished.
                    'ORGANISATION_URL' => 'http://acme.com',
                    'ORGANISATION_EMAIL' => 'acme.org@example.com',
                    'ORGANISATION_PHONE' => '011300000000',
                    'ORGANISATION_SOCIAL_MEDIA' => 'N/A', // TODO: Blocked until social media work is finished.
                    'ORGANISATION_DESCRIPTION' => 'Lorem ipsum',
                    'INVITE_URL' => 'test-invite-url',
                ];
        });

        $this->markTestIncomplete('Need to merge in org data schema updates');
    }

    public function test_created_does_not_send_emails_to_invitee_when_email_is_null()
    {
        Queue::fake();

        $organisationAdminInviteMock = $this->createMock(OrganisationAdminInvite::class);
        $organisationAdminInviteMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['email', null],
            ]));

        $adminUrlGeneratorMock = $this->createMock(AdminUrlGenerator::class);
        $adminUrlGeneratorMock->expects($this->never())
            ->method('generateOrganisationAdminInviteUrl');

        $observer = new OrganisationAdminInviteObserver($adminUrlGeneratorMock);
        $observer->created($organisationAdminInviteMock);

        Queue::assertNotPushed(NotifyInviteeEmail ::class);
    }
}
