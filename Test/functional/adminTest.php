<?php

use Marvin\Marvin\Test\FunctionalTestCase;

class adminTest extends FunctionalTestCase
{
    public function testUsersList()
    {
        $client = $this->createClient();
        $this->logIn($client);
        $crawler = $client->request('GET', '/admin/users');

        $this->assertTrue($client->getResponse()->isOk());
    }

    public function testNewUser()
    {
        $client = $this->createClient();
        $this->logIn($client);
        $crawler = $client->request('GET', '/admin/users/form');

        $this->assertTrue($client->getResponse()->isOk());

        $form = $crawler->selectButton('Save')->form();
        $crawler = $client->submit($form, array(
            'form[username]' => 'test user',
        ));

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(2, $crawler->filter('#users tbody tr'));
        $this->assertEquals('test user', $crawler->filter('table#users tbody tr:last-child td:first-child')->text());
    }

    public function testEditUser()
    {
        $this->app['db']->executeUpdate("INSERT INTO user (username, password, created_at, updated_at) VALUES (?, ?, ?, ?)", array(
            "test user",
            "password",
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
        ));

        $client = $this->createClient();
        $this->logIn($client);
        $crawler = $client->request('GET', '/admin/users/form/2');

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('h1:contains("test user")'));

        $form = $crawler->selectButton('save')->form();
        $crawler = $client->submit($form, array(
            'form[username]' => 'test user 2',
        ));

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(2, $crawler->filter('#users tbody tr'));
        $this->assertEquals('test user 2', $crawler->filter('table#users tbody tr:last-child td:first-child')->text());
    }

    public function testDeleteUser()
    {
        $this->app['db']->executeUpdate("INSERT INTO user (username, password, created_at, updated_at) VALUES (?, ?, ?, ?)", array(
            "test user",
            "password",
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
        ));

        $client = $this->createClient();
        $this->logIn($client);
        $crawler = $client->request('GET', '/admin/users/delete/2');

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('#users tbody tr'));
    }
}
