<?php

namespace Marvin\Users\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class InstallServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app->extend('install_plugins', function ($plugins) use ($app) {

            $plugins['users'] = function () use ($app) {

                $sm = $app['db']->getSchemaManager();
                $schema = new \Doctrine\DBAL\Schema\Schema();

                if ($sm->tablesExist(array('user')) == false) {
                    // Create table user
                    $userTable = $schema->createTable('user');
                    $userTable->addColumn('id', 'integer', array("autoincrement" => true));
                    $userTable->addColumn('username', 'string');
                    $userTable->addColumn('password', 'string');
                    $userTable->addColumn('created_at', 'datetime');
                    $userTable->addColumn('updated_at', 'datetime');
                    $userTable->setPrimaryKey(array("id"));
                    $userTable->addUniqueIndex(array("username"));
                    $sm->createTable($userTable);

                    $messages[] = $app['install_status'](
                        $sm->tablesExist(array('user')),
                        'User table was created.',
                        'Problem creating user table.'
                    );

                    // Create admin user
                    $app['db']->executeUpdate("INSERT INTO user (username, password, created_at, updated_at) VALUES (?, ?, ?, ?)", array(
                        'admin',
                        '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg==', // foo
                        date('Y-m-d H:i:s'),
                        date('Y-m-d H:i:s'),
                    ));

                    $admin = $app['db']->fetchAssoc("SELECT COUNT(*) AS count FROM user WHERE username = 'admin'");
                    $messages[] = $app['install_status'](
                        $admin['count'],
                        'User "admin" was created.',
                        'Problem creating user "admin".'
                    );

                    if (file_exists(__DIR__ ."/../Themes")) {
                        \Marvin\Marvin\Install::copy(__DIR__ ."/../Themes", $app['config']['themes_dir']);
                        $messages[] = $app['install_status'](
                            true,
                            'Users plugin\'s theme files were installed',
                            null
                        );
                    }
                } else {
                    $messages[] = $app['install_status'](
                        true,
                        'Article table already exists.',
                        null
                    );
                }

                return $messages;
            };

            return $plugins;
        });
    }

    public function boot(Application $app)
    {
    }
}
