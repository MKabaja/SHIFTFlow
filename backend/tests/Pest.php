<?php

/*
|--------------------------------------------------------------------------
| Test Case Configuration
|--------------------------------------------------------------------------
*/
/**
 * @method self actingAs(\App\Models\User $user, $driver = null)
 */
uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations & Helpers
|--------------------------------------------------------------------------
*/
