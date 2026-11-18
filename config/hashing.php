<?php

/*
|--------------------------------------------------------------------------
| Hashing Configuration
|--------------------------------------------------------------------------
|
| Shared-Hosting / cPanel / Hostinger optimised hashing configuration.
|
| Driver selection strategy:
|   1. Honour an explicit HASH_DRIVER value in .env (override everything).
|   2. Auto-detect: use "argon2id" when PASSWORD_ARGON2ID is compiled in.
|   3. Fall back silently to "bcrypt" so the app never 500 on hosts that
|      were not built with --with-password-argon2.
|
| Argon2id parameters follow OWASP's low-resource profile:
|   memory  16 384 KB (16 MB)  — stays well inside typical 128 MB PHP limit
|   time    2 iterations       — OWASP minimum for 16 MB memory cost
|   threads 1                  — safe on single-thread shared-hosting PHP-FPM
|
| Reference:
|   https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html
|
*/

$argon2idSupported = defined('PASSWORD_ARGON2ID');

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "bcrypt", "argon", "argon2id"
    |
    | The env variable HASH_DRIVER lets you force a specific driver (useful
    | when Argon2 is compiled in but shared host memory limits still cause
    | OOM errors). When HASH_DRIVER is not set, the driver is chosen at
    | runtime based on whether PASSWORD_ARGON2ID is available.
    |
    */

    'driver' => env('HASH_DRIVER', $argon2idSupported ? 'argon2id' : 'bcrypt'),

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | Cost factor 12 is the OWASP recommended minimum. Raise to 13–14 on
    | dedicated servers that can spare the extra CPU time.
    | BCRYPT_ROUNDS in .env overrides this value.
    |
    */

    'bcrypt' => [
        'rounds' => (int) env('BCRYPT_ROUNDS', 12),
        'verify' => env('HASH_VERIFY', false),
        'limit' => env('BCRYPT_LIMIT', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon2i Options
    |--------------------------------------------------------------------------
    */

    'argon' => [
        'memory' => (int) env('ARGON_MEMORY', 65536),
        'threads' => (int) env('ARGON_THREADS', 1),
        'time' => (int) env('ARGON_TIME', 4),
        'verify' => env('HASH_VERIFY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon2id Options — OWASP low-resource / shared-hosting profile
    |--------------------------------------------------------------------------
    |
    | memory  16 384 KB  Set via ARGON2ID_MEMORY in .env.
    | time    2           Set via ARGON2ID_TIME in .env.
    | threads 1           Set via ARGON2ID_THREADS in .env.
    |
    | Increasing memory beyond your PHP memory_limit will cause OOM.
    | For hosts with >= 256 MB available you can raise memory to 65536 and
    | time to 3 for stronger security.
    |
    */

    'argon2id' => [
        'memory' => (int) env('ARGON2ID_MEMORY', 16384),
        'threads' => (int) env('ARGON2ID_THREADS', 1),
        'time' => (int) env('ARGON2ID_TIME', 2),
        'verify' => env('HASH_VERIFY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rehash On Login
    |--------------------------------------------------------------------------
    |
    | When true, Laravel automatically rehashes stored passwords on successful
    | login whenever the algorithm or cost factors have changed. This enables
    | seamless, zero-downtime upgrades from bcrypt → argon2id for all existing
    | users — no batch migration script required.
    |
    */

    'rehash_on_login' => true,

];
