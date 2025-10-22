<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase service account JSON path
    |--------------------------------------------------------------------------
    |
    | Path to the Firebase service account JSON file. You can set this in
    | your .env file as FIREBASE_CREDENTIALS. Example: storage/app/firebase.json
    |
    */
    'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase/tjini-app-firebase-credentials.json')),
];
