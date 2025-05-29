<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Resend API Key
    |--------------------------------------------------------------------------
    |
    | This is the API key for your Resend account. You can find this in your
    | Resend dashboard. Make sure to keep this secure and never expose it
    | in your code or version control system.
    |
    */
    'api_key' => env('RESEND_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Default From Address
    |--------------------------------------------------------------------------
    |
    | This is the default address that emails will be sent from. This should
    | be a verified domain in your Resend account. You can use the default
    | onboarding@resend.dev for testing.
    |
    */
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'onboarding@resend.dev'),
        'name' => env('MAIL_FROM_NAME', 'Your App Name')
    ],

    /*
    |--------------------------------------------------------------------------
    | Resend API Version
    |--------------------------------------------------------------------------
    |
    | The version of the Resend API to use. Currently, there is only one
    | version available.
    |
    */
    'api_version' => '2022-04-01',

    /*
    |--------------------------------------------------------------------------
    | Resend API Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout for API requests to Resend in seconds.
    |
    */
    'timeout' => 30,
]; 