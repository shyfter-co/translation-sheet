<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google application name
    |--------------------------------------------------------------------------
    */
    'googleApplicationName' => env('TS_GOOGLE_APP_NAME', 'Laravel Translation Sheet'),

    /*
    |--------------------------------------------------------------------------
    | Google service account email
    |--------------------------------------------------------------------------
    | This is the service account email that you need to create.
    | https://console.developers.google.com/apis/credentials
    */
    'serviceAccountEmail' => env('TS_SERVICE_ACCOUNT_EMAIL', '***@***.iam.gserviceaccount.com'),

    /*
    |--------------------------------------------------------------------------
    | Google service account credentials file path
    |--------------------------------------------------------------------------
    */
    'serviceAccountCredentialsFile' => env('TS_SERVICE_ACCOUNT_CREDENTIALS_FILE', base_path('storage/google/service-account-crendentials.json')),

    /*
    |--------------------------------------------------------------------------
    | Spreadsheet
    |--------------------------------------------------------------------------
    | The spreadsheet that will be used for translations.
    | You need to create a new empty spreadsheet manually and fill its ID here.
    | You can find the ID in the spreadsheet URL.
    | (https://docs.google.com/spreadsheets/d/{spreadsheetId}/edit#gid=0)
    */
    'spreadsheetId' => env('TS_SPREADSHEET_ID', '***'),

    /*
    |--------------------------------------------------------------------------
    | Available locales
    |--------------------------------------------------------------------------
    | List here the app locales
    */
    'locales' => env('TS_LOCALES', ['en']),

    /*
    |--------------------------------------------------------------------------
    | Exclude lang files or namespaces
    |--------------------------------------------------------------------------
    | List here files or namespaces that the package will exclude
    | from all the operations. (prepare, push & pull).
    |
    | You can use wild card pattern that can be used with the Str::is()
    | Laravel helper. (https://laravel.com/docs/5.8/helpers#method-str-is)
    |
    | Example:
    |   'validation*'
    |   'foo::*',
    |   'foo::bar.*',
    */
    'exclude' => [],

    /**
     * Primary sheet (tab) used for the translations.
     *
     */
    'primary_sheet' => [
        'name' => 'ShyfterMain',
    ],

    'base_path' => 'translation-sources',

    /*
    |--------------------------------------------------------------------------
    | Extra Sheets
    |--------------------------------------------------------------------------
    | This config area give you the possibility to other sheets (tabs) to your spreadsheet.
    | they can be used to translate sperately other sections of your application.

    | ie. if you handle your web app or mobile app translation in laravel app. you can instruct
    | translations-sheet to add them as sheets.
    | Files for these sheets must not live under resources/lang folder. But, resources/web-app-lang for instance.
    |
    */
    'extra_sheets' => [
        [
            "name" => "frontend",
            "repo" => "git@github.com:shyfter-co/frontend.git",
            "path" => "public/locales",
            "tabColor" => "#00ff00"
        ],
        [
            "name" => "shyfter-staff",
            "repo" => "git@github.com:shyfter-co/shyfter-staff.git",
            "path" => "locales",
            "tabColor" => "#6600cc"
        ],
        [
            "name" => "try-shyfter-app",
            "repo" => "git@github.com:shyfter-co/try-shyfter-app.git",
            "path" => "public/locales",
            "tabColor" => "#000999"
        ],
        [
            "name" => "shyfter-pos",
            "repo" => "git@github.com:shyfter-co/shyfter-pos.git",
            "path" => "locales",
            "tabColor" => "#66ffcc"
        ],
    ],
];
