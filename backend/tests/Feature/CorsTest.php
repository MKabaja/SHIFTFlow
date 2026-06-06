<?php

declare(strict_types=1);

test('preflight request from allowed origin returns correct CORS headers', function () {
    /** @var \Tests\TestCase $this */
    $response = $this->call('OPTIONS', '/api/auth/login', [], [], [], [
        'HTTP_ORIGIN' => 'http://localhost:5173',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type',
    ]);

    $response->assertNoContent();
    expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('http://localhost:5173');
    expect($response->headers->get('Access-Control-Allow-Credentials'))->toBe('true');
    expect($response->headers->get('Access-Control-Allow-Methods'))->not->toBeEmpty();
});

test('request from disallowed origin does not get CORS allow header', function () {
    /** @var \Tests\TestCase $this */
    $response = $this->call('OPTIONS', '/api/auth/login', [], [], [], [
        'HTTP_ORIGIN' => 'http://evil-site.com',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
    ]);

    expect($response->headers->get('Access-Control-Allow-Origin'))->not->toBe('http://evil-site.com');
});
