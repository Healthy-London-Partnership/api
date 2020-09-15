<?php

namespace App\Providers;

use App\Models\Collection;
use App\Models\CollectionTaxonomy;
use App\Models\File;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\OrganisationAdminInvite;
use App\Models\Referral;
use App\Models\Report;
use App\Models\Service;
use App\Models\ServiceLocation;
use App\Models\ServiceTaxonomy;
use App\Models\Taxonomy;
use App\Models\UpdateRequest;
use App\Models\User;
use App\Observers\CollectionObserver;
use App\Observers\CollectionTaxonomyObserver;
use App\Observers\FileObserver;
use App\Observers\LocationObserver;
use App\Observers\OrganisationAdminInviteObserver;
use App\Observers\OrganisationObserver;
use App\Observers\ReferralObserver;
use App\Observers\ReportObserver;
use App\Observers\ServiceLocationObserver;
use App\Observers\ServiceObserver;
use App\Observers\ServiceTaxonomyObserver;
use App\Observers\TaxonomyObserver;
use App\Observers\UpdateRequestObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class ModelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot()
    {
        Collection::observe(CollectionObserver::class);
        CollectionTaxonomy::observe(CollectionTaxonomyObserver::class);
        File::observe(FileObserver::class);
        Location::observe(LocationObserver::class);
        OrganisationAdminInvite::observe(OrganisationAdminInviteObserver::class);
        Organisation::observe(OrganisationObserver::class);
        Referral::observe(ReferralObserver::class);
        Report::observe(ReportObserver::class);
        ServiceLocation::observe(ServiceLocationObserver::class);
        Service::observe(ServiceObserver::class);
        ServiceTaxonomy::observe(ServiceTaxonomyObserver::class);
        Taxonomy::observe(TaxonomyObserver::class);
        UpdateRequest::observe(UpdateRequestObserver::class);

        Relation::morphMap([
            UpdateRequest::EXISTING_TYPE_LOCATION => Location::class,
            UpdateRequest::EXISTING_TYPE_REFERRAL => Referral::class,
            UpdateRequest::EXISTING_TYPE_SERVICE => Service::class,
            UpdateRequest::EXISTING_TYPE_SERVICE_LOCATION => ServiceLocation::class,
            UpdateRequest::EXISTING_TYPE_ORGANISATION => Organisation::class,
            UpdateRequest::EXISTING_TYPE_USER => User::class,
        ]);
    }

    /**
     * Register services.
     */
    public function register()
    {
        //
    }
}
