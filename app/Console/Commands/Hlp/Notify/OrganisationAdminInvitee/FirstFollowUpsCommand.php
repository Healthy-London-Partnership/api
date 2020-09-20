<?php

namespace App\Console\Commands\Hlp\Notify\OrganisationAdminInvitee;

use App\Emails\OrganisationAdminInviteFirstFollowUps\NotifyInviteeEmail;
use App\Generators\AdminUrlGenerator;
use App\Models\Location;
use App\Models\Notification;
use App\Models\OrganisationAdminInvite;
use App\Models\SocialMedia;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class FirstFollowUpsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hlp:notify:organisation-admin-invitee:first-follow-ups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends notifications out to the organisation admin invitees with the first follow ups';

    /**
     * Execute the console command.
     *
     * @param \App\Generators\AdminUrlGenerator $adminUrlGenerator
     */
    public function handle(AdminUrlGenerator $adminUrlGenerator): void
    {
        $dates = array_map(function (int $week): string {
            return Date::today()->subWeeks($week)->toDateString();
        }, range(1, 4));

        $organisationAdminInvites = OrganisationAdminInvite::query()
            ->with('organisation', 'organisation.location', 'organisation.socialMedias')
            ->whereNotNull('email')
            ->whereIn(DB::raw('cast(`created_at` as date)'), $dates)
            ->get();

        foreach ($organisationAdminInvites as $organisationAdminInvite) {
            Notification::sendEmail(
                new NotifyInviteeEmail(
                    $organisationAdminInvite->email,
                    [
                        'ORGANISATION_NAME' => $organisationAdminInvite->organisation->name,
                        'ORGANISATION_ADDRESS' => $this->transformAddress(
                            $organisationAdminInvite->organisation->location
                        ) ?: 'N/A',
                        'ORGANISATION_URL' => $organisationAdminInvite->organisation->url ?: 'N/A',
                        'ORGANISATION_EMAIL' => $organisationAdminInvite->organisation->email ?: 'N/A',
                        'ORGANISATION_PHONE' => $organisationAdminInvite->organisation->phone ?: 'N/A',
                        'ORGANISATION_SOCIAL_MEDIA' => $this->transformSocialMedias(
                            $organisationAdminInvite->organisation->socialMedias
                        ) ?: 'N/A',
                        'ORGANISATION_DESCRIPTION' => $organisationAdminInvite->organisation->description,
                        'INVITE_URL' => $adminUrlGenerator->generateOrganisationAdminInviteUrl(
                            $organisationAdminInvite
                        ),
                    ]
                )
            );
        }
    }

    /**
     * @param \App\Models\Location|null $location
     * @return string|null
     */
    protected function transformAddress(?Location $location): ?string
    {
        if ($location === null) {
            return null;
        }

        $addressParts = [
            $location->address_line_1,
            $location->address_line_2,
            $location->address_line_3,
            $location->city,
            $location->county,
            $location->postcode,
            $location->country,
        ];

        $addressParts = array_filter($addressParts);

        return implode(', ', $addressParts);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection|null $socialMedias
     * @return string|null
     */
    protected function transformSocialMedias(?Collection $socialMedias): ?string
    {
        if ($socialMedias === null) {
            return null;
        }

        return $socialMedias
            ->map(function (SocialMedia $socialMedia): string {
                return $this->transformSocialMedia($socialMedia);
            })
            ->implode(', ');
    }

    /**
     * @param \App\Models\SocialMedia $socialMedia
     * @return string
     */
    protected function transformSocialMedia(SocialMedia $socialMedia): string
    {
        $type = $socialMedia->type;

        switch ($type) {
            case SocialMedia::TYPE_TWITTER:
                $type = 'Twitter';
                break;
            case SocialMedia::TYPE_FACEBOOK:
                $type = 'Facebook';
                break;
            case SocialMedia::TYPE_INSTAGRAM:
                $type = 'Instagram';
                break;
            case SocialMedia::TYPE_YOUTUBE:
                $type = 'YouTube';
                break;
            case SocialMedia::TYPE_OTHER:
                $type = 'Other';
                break;
        }

        return "{$type}: {$socialMedia->url}";
    }
}
