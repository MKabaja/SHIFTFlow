<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Validation\ValidationService;
use App\Services\Validation\Validators\AvailabilityValidator;
use App\Services\Validation\Validators\MaxHoursPerMonthValidator;
use App\Services\Validation\Validators\MaxHoursPerQuarterValidator;
use App\Services\Validation\Validators\MinimumBreakValidator;
use App\Services\Validation\Validators\PositionPermissionValidator;
use App\Services\Validation\Validators\PositionUniquenessValidator;
use App\Services\Validation\Validators\TimeConflictValidator;
use App\Services\Validation\Validators\UserActiveValidator;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ValidationService::class, function ($app) {
            return new ValidationService([
                $app->make(UserActiveValidator::class),
                $app->make(PositionPermissionValidator::class),
                $app->make(AvailabilityValidator::class),
                $app->make(PositionUniquenessValidator::class),
                $app->make(TimeConflictValidator::class),
                $app->make(MinimumBreakValidator::class),
                $app->make(MaxHoursPerMonthValidator::class),
                $app->make(MaxHoursPerQuarterValidator::class),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
