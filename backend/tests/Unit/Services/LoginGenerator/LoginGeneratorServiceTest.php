<?php

declare(strict_types=1);

use App\Services\LoginGeneratorService;
use Illuminate\Support\Facades\Config;

uses(Tests\TestCase::class);

beforeEach(function () {
    // Ustawiamy sztywną długość loginu, aby testy były deterministyczne
    // Niezależnie od tego co masz w .env, tutaj testujemy logikę dla 5 znaków z nazwiska
    Config::set('employees.login_length', 5);
});

test('generates base login for simple name', function () {
    $service = new LoginGeneratorService;

    // Jan + Kowalski (5 znaków) -> jkowal
    $login = $service->generate('Jan Kowalski');

    expect($login)->toBe('jkowal');
});

test('handles polish characters correctly via transliteration', function () {
    $service = new LoginGeneratorService;

    // Łukasz (L) + Ślusarski (Slusa) -> lslusa
    $login = $service->generate('Łukasz Ślusarski');

    expect($login)->toBe('lslusa');
});

test('handles single name input', function () {
    $service = new LoginGeneratorService;

    // Dla jednego członu bierzemy do 10 znaków
    $login = $service->generate('Maksymilian');

    expect($login)->toBe('maksymilia');
});

test('handles short surnames', function () {
    $service = new LoginGeneratorService;

    // Jan + Kot -> jkot
    $login = $service->generate('Jan Kot');

    expect($login)->toBe('jkot');
});

test('increments login when collision exists', function () {
    $service = new LoginGeneratorService;
    $existingLogins = ['jkowal'];

    // Skoro jkowal jest zajęty, powinien dodać 1
    $login = $service->generate('Jan Kowalski', $existingLogins);

    expect($login)->toBe('jkowal1');
});

test('finds next available number in sequence', function () {
    $service = new LoginGeneratorService;
    $existingLogins = ['jkowal', 'jkowal1', 'jkowal2'];

    // Max to 2, więc następny to 3
    $login = $service->generate('Jan Kowalski', $existingLogins);

    expect($login)->toBe('jkowal3');
});

test('handles gaps in numbering correctly', function () {
    $service = new LoginGeneratorService;
    // Mamy jkowal i jkowal5 (ktoś usunął 1, 2, 3, 4)
    $existingLogins = ['jkowal', 'jkowal5'];

    // System powinien wziąć MAX + 1, czyli 6 (bezpieczne podejście)
    $login = $service->generate('Jan Kowalski', $existingLogins);

    expect($login)->toBe('jkowal6');
});

test('is case insensitive for input', function () {
    $service = new LoginGeneratorService;
    $login = $service->generate('JAN KOWALSKI');

    expect($login)->toBe('jkowal');
});
