<?php

use Marvin\Users\Provider\UserProvider;

// Register service provider
$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.firewalls' => array(
        'admin' => array(
            'pattern' => '^/admin',
            'form' => array(
                'login_path' => '/login',
                'check_path' => '/admin/login_check',
            ),
            'logout' => array(
                'logout_path' => '/admin/logout',
                'target_url' => '/admin',
            ),
            'users' => $app->share(function () use ($app) {
                return new UserProvider($app['db']);
            }),
        ),
    ),
));

$app->register(new Silex\Provider\SwiftmailerServiceProvider(), array(
    'swiftmailer.options' => isset($app['config']['swiftmailer.options']) ? $app['config']['swiftmailer.options'] : array(),
));

$app->register(new Marvin\Users\Provider\InstallServiceProvider());


// Mount plugin controller provider
$app->mount('/login', new Marvin\Users\Controller\LoginControllerProvider());
$app->mount('/admin/users', new Marvin\Users\Controller\AdminControllerProvider());
if ($app['debug']) {
    $app->mount('/install/users', new Marvin\Users\Controller\InstallControllerProvider());
}
