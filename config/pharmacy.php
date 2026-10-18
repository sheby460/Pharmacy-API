<?php

return [
    'initial_admin' => [
        'username' => env('PHARMACY_ADMIN_USERNAME'),
        'password' => env('PHARMACY_ADMIN_PASSWORD'),
        'fname' => env('PHARMACY_ADMIN_FNAME', 'System'),
        'mname' => env('PHARMACY_ADMIN_MNAME'),
        'lname' => env('PHARMACY_ADMIN_LNAME', 'Administrator'),
        'email' => env('PHARMACY_ADMIN_EMAIL'),
        'phone' => env('PHARMACY_ADMIN_PHONE'),
    ],
];
