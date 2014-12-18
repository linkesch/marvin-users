<?php

namespace Marvin\Users\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\User;

class LoginControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        // Login
        $controllers->get('/', function (Request $request) use ($app) {
            return $app['twig']->render('admin/login.twig', array(
                'error'         => $app['security.last_error']($request),
                'last_username' => $app['session']->get('_security.last_username'),
            ));
        });

        $controllers->match('/forgotten-password', function (Request $request) use ($app) {
            if ($request->isMethod('post')) {

                $find = $app['db']->fetchAssoc("SELECT username, password, COUNT(*) AS count FROM user WHERE username = ?", array($request->request->get('username')));
                if ($find['count']) {

                    $encoder = $app['security.encoder_factory']->getEncoder(new User($find['username'], $find['password']));
                    $password = substr(md5(rand()), 0, 7);
                    $app['db']->executeUpdate("UPDATE user SET password = ? WHERE username = ?", array(
                        $encoder->encodePassword($password, null),
                        $find['username'],
                    ));

                    $body = $app['translator']->trans('You requested a new password for a website') ." ". $app['config']['website']['url'] ."\n\n".
                        $app['translator']->trans('Your new password is') .": ". $password;

                    $message = \Swift_Message::newInstance()
                        ->setSubject($app['config']['website']['name'] .' - '. $app['translator']->trans('Your new password'))
                        ->setFrom(array($app['config']['website']['email']))
                        ->setTo(array($find['username']))
                        ->setBody($body);

                    $app['mailer']->send($message);

                    $app['session']->getFlashBag()->add('message', $app['translator']->trans('A new password was sent to your e-mail.'));

                    return $app->redirect('/login/forgotten-password');

                } else {
                    $app['session']->getFlashBag()->add('error', $app['translator']->trans("A user with this e-mail doesn't exists."));
                }

            }

            return $app['twig']->render('admin/forgottenPassword.twig');
        })
        ->bind('admin_forgotten_password');

        return $controllers;
    }
}
