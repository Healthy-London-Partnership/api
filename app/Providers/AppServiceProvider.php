<?php

namespace App\Providers;

use App\Generators\AdminUrlGenerator;
use App\Repositories\NhsConditions\NhsConditionsRepository;
use App\RoleManagement\RoleAuthorizer;
use App\RoleManagement\RoleAuthorizerInterface;
use App\RoleManagement\RoleChecker;
use App\RoleManagement\RoleCheckerInterface;
use App\RoleManagement\RoleManager;
use App\RoleManagement\RoleManagerInterface;
use App\VariableSubstitution\DoubleParenthesisVariableSubstituter;
use Carbon\CarbonImmutable;
use GuzzleHttp\Client;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Use CarbonImmutable instead of Carbon.
        Date::use(CarbonImmutable::class);

        // Geocode.
        switch (config('hlp.geocode_driver')) {
            case 'google':
                $this->app->singleton(\App\Contracts\Geocoder::class, \App\Geocode\GoogleGeocoder::class);
                break;
            case 'nominatim':
                $this->app->singleton(\App\Contracts\Geocoder::class, \App\Geocode\NominatimGeocoder::class);
                break;
            case 'stub':
            default:
                $this->app->singleton(\App\Contracts\Geocoder::class, \App\Geocode\StubGeocoder::class);
                break;
        }

        // Email Sender.
        switch (config('hlp.email_driver')) {
            case 'gov':
                $this->app->singleton(\App\Contracts\EmailSender::class, \App\EmailSenders\GovNotifyEmailSender::class);
                break;
            case 'mailgun':
                $this->app->singleton(\App\Contracts\EmailSender::class, \App\EmailSenders\MailgunEmailSender::class);
                break;
            case 'null':
                $this->app->singleton(\App\Contracts\EmailSender::class, \App\EmailSenders\NullEmailSender::class);
                break;
            case 'log':
            default:
                $this->app->singleton(\App\Contracts\EmailSender::class, \App\EmailSenders\LogEmailSender::class);
                break;
        }

        // SMS Sender.
        switch (config('hlp.sms_driver')) {
            case 'gov':
                $this->app->singleton(\App\Contracts\SmsSender::class, \App\SmsSenders\GovNotifySmsSender::class);
                break;
            case 'twilio':
                $this->app->singleton(\App\Contracts\SmsSender::class, \App\SmsSenders\TwilioSmsSender::class);
                break;
            case 'null':
                $this->app->singleton(\App\Contracts\SmsSender::class, \App\SmsSenders\NullSmsSender::class);
                break;
            case 'log':
            default:
                $this->app->singleton(\App\Contracts\SmsSender::class, \App\SmsSenders\LogSmsSender::class);
                break;
        }

        // Variable substitution.
        $this->app->bind(\App\Contracts\VariableSubstituter::class, DoubleParenthesisVariableSubstituter::class);

        // Admin URL generator.
        $this->app->singleton(AdminUrlGenerator::class, function () {
            return new AdminUrlGenerator(config('hlp.backend_uri'));
        });

        $this->app->bind(RoleAuthorizerInterface::class, RoleAuthorizer::class);
        $this->app->bind(RoleCheckerInterface::class, RoleChecker::class);
        $this->app->bind(RoleManagerInterface::class, RoleManager::class);
        $this->app->bind(NhsConditionsRepository::class, function (Container $app) {
            return new NhsConditionsRepository(
                $app->make(Client::class),
                config('hlp.nhs.domain'),
                config('hlp.nhs.subscription_key'),
                3
            );
        });
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        //
    }
}
