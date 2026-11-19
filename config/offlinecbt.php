<?php

// Offline CBT desktop app. Set these in .env when you publish a new installer;
// the app and the Offline CBT page then show "update available".
return [
    'latest_version' => env('OFFLINE_CBT_LATEST_VERSION', '1.0.0'),
    'download_url'   => env('OFFLINE_CBT_DOWNLOAD_URL', ''),
];
