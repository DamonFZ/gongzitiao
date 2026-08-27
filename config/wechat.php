<?php

return [
    'official_account' => [
        'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APP_ID'),
        'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
    ],
    'gateway' => env('WECHAT_OAUTH_GATEWAY', 'http://oauth.damon.com'),
];
