<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMPP / SMS Gateway
    |--------------------------------------------------------------------------
    */
    'smpp' => [
        'host'                 => env('SMPP_HOST', '10.82.11.30'),
        'port'                 => (int) env('SMPP_PORT', 2775),
        'system_id'            => env('SMPP_SYSTEM_ID'),
        'password'             => env('SMPP_PASSWORD'),
        // Primary sender. If sending under this sender fails, SmppService
        // automatically falls back to 'fallback_source_addr'.
        'source_addr'          => env('SMPP_SOURCE_ADDR', 'MoovApps'),
        'fallback_source_addr' => env('SMPP_FALLBACK_SOURCE_ADDR', '999901'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'transactions_import' => [
        // Chemin distant sur le disque SFTP/FTP configuré dans config/filesystems.php
        'disk' => env('TRANSACTIONS_DISK', 'sftp'),
        'sftp_path' => env('SFTP_TRANSACTIONS_PATH', 'transactions'),
    ],

    'outlook' => [
        // Microsoft Entra ID / OAuth2 Configuration
        'tenant_id' => env('AZURE_TENANT_ID'),
        'client_id' => env('AZURE_CLIENT_ID'),
        'client_secret' => env('AZURE_CLIENT_SECRET'),
        'redirect_uri' => env('APP_URL') . '/api/oauth/callback', // Must be under /api/ for Nginx routing

        // Mailbox Configuration
        'mailbox' => env('OUTLOOK_MAILBOX_EMAIL'),
        'mail_folder' => env('OUTLOOK_MAIL_FOLDER', 'Inbox'),
        'subject_filter' => env('OUTLOOK_SUBJECT_FILTER'),
        'filename_pattern' => env('OUTLOOK_FILENAME_PATTERN'),
        'allowed_extensions' => env('OUTLOOK_ALLOWED_EXTENSIONS', 'xlsx,xls'),
        'max_file_mb' => (int) env('OUTLOOK_MAX_FILE_MB', 500),
        'mark_as_read' => (bool) env('OUTLOOK_MARK_AS_READ', true),
        'move_processed_to' => env('OUTLOOK_MOVE_PROCESSED_TO'),
        'move_failed_to' => env('OUTLOOK_MOVE_FAILED_TO'),

        // Scheduling
        'import_enabled' => (bool) env('OUTLOOK_IMPORT_ENABLED', false),
        'import_time' => env('OUTLOOK_IMPORT_TIME', '08:30'),
        'import_timezone' => env('OUTLOOK_IMPORT_TIMEZONE', 'UTC'),
    ],

];
