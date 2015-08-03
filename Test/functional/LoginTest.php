<?php

use Marvin\Marvin\Test\FunctionalTestCase;

class LoginTest extends FunctionalTestCase
{
    public function testForgottenPassword()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login/forgotten-password');

        $this->assertTrue($client->getResponse()->isOk());

        $form = $crawler->selectButton('Send')->form();
        $crawler = $client->submit($form, array(
            'username' => 'admin@test.com',
        ));

        $emails = $this->app['mailer.logger']->getMessages();
        $to = $emails[0]->getTo();

        $this->assertEquals("Marvin - Your new password", $emails[0]->getSubject());
        $this->assertEquals(1, count($to));
        $this->assertEquals(null, $to["admin@test.com"]);
        $this->assertContains('Your new password is:', $emails[0]->getBody());

        $this->assertTrue($client->getResponse()->isOk());
    }
}
