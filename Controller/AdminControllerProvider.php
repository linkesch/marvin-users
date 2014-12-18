<?php

namespace Marvin\Users\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Validator\Constraints as Assert;

class AdminControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function () use ($app) {
            $users = $app['db']->fetchAll("SELECT * FROM user ORDER BY username ASC");

            return $app['twig']->render('admin/users/list.twig', array(
                'users' => $users,
            ));
        })
        ->bind('admin_users');

        $controllers->match('/form/{id}', function (Request $request, $id) use ($app) {
            $userData = array();

            if ($id > 0) {
                $userData = $app['db']->fetchAssoc("SELECT * FROM user WHERE id = ?", array($id));
                $userData['password'] = '';
            }

            $form = $app['form.factory']->createBuilder('form', $userData)
                ->add('id', 'hidden')
                ->add('username', 'text', array(
                    'label' => 'E-mail',
                    'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 4))),
                ))
                ->add('password', 'text', array(
                    'constraints' => array(new Assert\Length(array('min' => 5))),
                    'required' => $id ? false : true,
                ))
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $encoder = $app['security.encoder_factory']->getEncoder(new User($data['username'], $data['password']));

                if ($data['id'] == 0) {
                    $find = $app['db']->fetchAssoc("SELECT COUNT(*) AS count FROM user WHERE username = ?", array($data['username']));
                    if ($find['count']) {
                        $app['session']->getFlashBag()->add('error', $app['translator']->trans('A user with this username already exists. Please try another one.'));
                    } else {
                        $app['db']->executeUpdate("INSERT INTO user (username, password, created_at, updated_at) VALUES (?, ?, ?, ?)", array(
                            $data['username'],
                            $encoder->encodePassword($data['password'], null),
                            date('Y-m-d H:i:s'),
                            date('Y-m-d H:i:s'),
                        ));

                        $app['session']->getFlashBag()->add('message', $app['translator']->trans('The new user was added'));

                        return $app->redirect('/admin/users');
                    }
                } else {
                    $find = $app['db']->fetchAssoc("SELECT COUNT(*) AS count FROM user WHERE username = ? AND id != ?", array($data['username'], $data['id']));
                    if ($find['count']) {
                        $app['session']->getFlashBag()->add('error', $app['translator']->trans('A user with this username already exists. Please try another one.'));
                    } else {
                        $originalUserData = $app['db']->fetchAssoc("SELECT * FROM user WHERE id = ?", array($data['id']));

                        $app['db']->executeUpdate("UPDATE user SET username = ?, password = ?, updated_at = ? WHERE id = ?", array(
                            $data['username'],
                            isset($data['password']) ? $encoder->encodePassword($data['password'], null) : $originalUserData['password'],
                            date('Y-m-d H:i:s'),
                            $data['id'],
                        ));

                        $app['session']->getFlashBag()->add('message', $app['translator']->trans('The user was changed'));

                        return $app->redirect('/admin/users');
                    }
                }
            }

            return $app['twig']->render('admin/users/form.twig', array(
                'form' => $form->createView(),
            ));
        })
        ->value('id', 0);

        $controllers->get('/delete/{id}', function ($id) use ($app) {
            $app['db']->delete('user', array('id' => $id));

            $app['session']->getFlashBag()->add('message', $app['translator']->trans('The user was deleted'));

            return $app->redirect('/admin/users');
        });

        return $controllers;
    }
}
