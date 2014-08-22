<?php

namespace Marvin\Users\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;

class InstallControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        // Install
        $controllers->get('/', function () use ($app) {
            $messages = $app['install_plugins']['users']();

            return $app['twig']->render('admin/install.twig', array(
                'messages' => $messages,
            ));
        });

        return $controllers;
    }
}
