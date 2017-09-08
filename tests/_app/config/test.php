<?php

return [
    'id' => 'yii2-mdpages-tests',
    'basePath' => dirname(__DIR__),
    'language' => 'en-US',
    'aliases' => [
        '@jacmoe/mdpages' => dirname(dirname(dirname(__DIR__))),
        '@tests' => dirname(dirname(__DIR__)),
        '@vendor' => VENDOR_DIR,
        '@bower' => VENDOR_DIR . '/bower',
    ],
    'modules' => [
        'mdpages' => [
            'class' => 'jacmoe\mdpages\Module',
            'repository_url' => 'https://github.com/jacmoe/jacmoes-content.git',
            'github_token' => '104b4836c4a8545972d32990b5b06fa894f738f9',
            'github_owner' => 'jacmoe',
            'github_repo' => 'jacmoes-content',
            'github_branch' => 'master',
        ],
    ],
    'components' => [
        'assetManager' => [
            'basePath' => __DIR__ . '/../assets',
        ],
        'urlManager' => [
            'showScriptName' => true,
        ],
        'request' => [
            'cookieValidationKey' => 'test',
            'enableCsrfValidation' => false,
        ],
    ],
    'params' => [],
];
