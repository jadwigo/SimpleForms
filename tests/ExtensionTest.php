<?php

namespace Bolt\Extension\Bolt\SimpleForms\Tests;

use Symfony\Component\HttpFoundation\Request;

/**
 * Ensure that SimpleForms loads correctly.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionTest extends AbstractSimpleFormsUnitTest
{
    public function testExtensionRegister()
    {
        $app = $this->getApp();
        $extension = $this->getExtension($app);

        // Check getName() returns the correct value
        $name = $extension->getName();
        $this->assertSame($name, 'SimpleForms');

        // Check that we're giving warnings for mail
        $this->assertTrue($extension->sendsMail());

        // Check that we're setting safe Twig mode
        $this->assertTrue($extension->isSafe());
    }

    public function testSimpleFormDefault()
    {
        $app = $this->getApp();
        $extension = $this->getExtension($app);

        $app['request'] = Request::create('/');

        $html = $extension->simpleForm('test_simple_form');
        $this->assertInstanceOf('\Twig_Markup', $html);

        $html = (string) $html;
        $this->assertRegExp('#<form action="\#" method="post" class="simpleform simpleform-test_simple_form" enctype="multipart/form-data">#', $html);
        $this->assertRegExp('#<div\W+id="test_simple_form"><div class="simpleform-row simpleform-text\W+">#', $html);
        $this->assertRegExp('#<label for="test_simple_form_name" class="required">Name</label>#', $html);
        $this->assertRegExp('#<label for="test_simple_form_name" class="required">Name</label>#', $html);
        $this->assertRegExp('#<input type="text"\W+id="test_simple_form_name" name="test_simple_form\[name\]" required="required" placeholder="Name of the game is" />#', $html);
        $this->assertRegExp('#<div class="simpleform-row simpleform-email\W+">#', $html);
        $this->assertRegExp('#<label for="test_simple_form_email" class="required">Your email address</label>#', $html);
        $this->assertRegExp('#<input type="email"\W+id="test_simple_form_email" name="test_simple_form\[email\]" required="required" placeholder="you@example.com" />#', $html);
        $this->assertRegExp('#<div class="simpleform-row simpleform-text\W+wide ">#', $html);
        $this->assertRegExp('#<label for="test_simple_form_subject">Other test subject</label>#', $html);
        $this->assertRegExp('#(<input type="text"\W+id="test_simple_form_subject" name="test_simple_form\[subject\]" )(maxlength="30" |)(placeholder="You rang" />)#', $html);
        $this->assertRegExp('#<div class="simpleform-row simpleform-textarea\W+">#', $html);
        $this->assertRegExp('#<label for="test_simple_form_message" class="required">Message</label>#', $html);
        $this->assertRegExp('#<textarea\W+id="test_simple_form_message" name="test_simple_form\[message\]" required="required" placeholder="Once upon a time"></textarea></div><div class="simpleform-row simpleform-choice\W+">#', $html);
        $this->assertRegExp('#<label for="test_simple_form_pets" class="required">What is your favourite type of pet</label>#', $html);
        $this->assertRegExp('#<select\W+id="test_simple_form_pets" name="test_simple_form\[pets\]" required="required"#', $html); //>#', $html);
        $this->assertRegExp('#<option\W+disabled="disabled" selected="selected">My favorite animals are</option>#', $html);
        $this->assertRegExp('#<option value="0">Kittens</option>#', $html);
        $this->assertRegExp('#<option value="1">Puppies</option>#', $html);
        $this->assertRegExp('#<option value="2">Penguins</option>#', $html);
        $this->assertRegExp('#<option value="3">Koala bears</option>#', $html);
        $this->assertRegExp('#<option value="4">I do not like animals</option></select>#', $html);
        $this->assertRegExp('#<div class="simpleform-row simpleform-file\W+">#', $html);
        $this->assertRegExp('#<label for="test_simple_form_upload">Upload your picture</label>#', $html);
        $this->assertRegExp('#<input type="file"\W+id="test_simple_form_upload" name="test_simple_form\[upload\]"#', $html); //>#', $html);
        $this->assertRegExp('#<div class="simpleform-row simpleform-checkbox\W+">#', $html);
        $this->assertRegExp('#<label for="test_simple_form_newsletter">Newsletter</label>#', $html);
        $this->assertRegExp('#<input type="checkbox"\W+id="test_simple_form_newsletter" name="test_simple_form\[newsletter\]" placeholder="Send me the newsletters" value="1" />#', $html);
        $this->assertRegExp('#<label for="test_simple_form_newsletter" class="checkbox-placeholder">Send me the newsletters</label>#', $html);
        $this->assertRegExp('#<div class="simpleform-row simpleform-checkbox\W+">#', $html);
        $this->assertRegExp('#<label for="test_simple_form_signup">Agree to this</label>#', $html);
        $this->assertRegExp('#<input type="checkbox"\W+id="test_simple_form_signup" name="test_simple_form\[signup\]" placeholder="Yes, of course I agree." value="1" />#', $html);
        $this->assertRegExp('#<label for="test_simple_form_signup" class="checkbox-placeholder">Yes, of course I agree.</label>#', $html);
        $this->assertRegExp('#<input type="submit" name="submit" value="Send me away" class="simpleform-submit" />#', $html);
    }

    public function testSimpleFormPostDefault()
    {
        $app = $this->getApp();
        $extension = $this->getExtension($app);
        $parameters = $this->getPostParameters();

        $app['request'] = Request::create('/', 'POST', $parameters);
        $html = $extension->simpleForm('test_simple_form');

        $this->assertInstanceOf('\Twig_Markup', $html);
        $this->assertRegExp('#<p class="simpleform-message">Thanks! Your message has been sent.</p>#', (string) $html);
    }

    public function testSimpleFormPostMailSendDebugOn()
    {
        $app = $this->getApp(false);
        $extension = $this->getExtension($app);
        $extension->config['testmode'] = true;
        $parameters = $this->getPostParameters();

        $mailer = $this->getMock('\Swift_Mailer', array('send'), array($app['swiftmailer.transport']));
        $mailer->expects($this->any())
            ->method('send')
            ->will($this->returnCallback(function ($message) {
                    $message = $message->toString();
                    \PHPUnit_Framework_Assert::assertRegExp('#Subject: Testing Email Subject Line#', $message);
                    // v1.x handled this incorrectly
                    \PHPUnit_Framework_Assert::assertRegExp('#From: Road Runner <road@runner.com>#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#To: Gawain Lynch <info@example.com>#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#Somebody used the form on#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#The posted data is as follows#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#name: Road Runner#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#email: road@runner.com#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#subject: Beep beep#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#message: Catch me if you can#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#pets: Puppies#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#newsletter: yes#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#signup: yes#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#Sent by SimpleForms#', $message);
                }
            ))
        ;
        $app['mailer'] = $mailer;

        $app['request'] = Request::create('/', 'POST', $parameters);
        $app->boot();
        $extension->simpleForm('test_simple_form');
    }

    public function testSimpleFormPostMailSendDebugOff()
    {
        $app = $this->getApp();
        $extension = $this->getExtension($app);
        $extension->config['testmode'] = false;
        $parameters = $this->getPostParameters();

        $mailer = $this->getMock('\Swift_Mailer', array('send'), array($app['swiftmailer.transport']));
        $mailer->expects($this->any())
            ->method('send')
            ->will($this->returnCallback(function ($message) {
                    $message = $message->toString();
                    \PHPUnit_Framework_Assert::assertRegExp('#Subject: Testing Email Subject Line#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#From: Road Runner <road@runner.com>#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#To: Gawain Lynch <gawain@example.org>#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#(Cc: )(Xiao-Hu Tai <|)(xiao@example.org)(>|)#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#(Bcc: )(Bob den Otter <|)(bob@example.org)(>|)#', $message);
                }
            ))
        ;
        $app['mailer'] = $mailer;

        $app['request'] = Request::create('/', 'POST', $parameters);
        $extension->simpleForm('test_simple_form');
    }

    public function testSimpleFormPostMailSendWithCallbacks()
    {
        $app = $this->getApp(false);
        $extension = $this->getExtension($app);
        $app['extensions.SimpleForms']->config['test_simple_form']['fields']['ip']['type'] = 'ip';
        $app['extensions.SimpleForms']->config['test_simple_form']['fields']['host']['type'] = 'remotehost';
        $app['extensions.SimpleForms']->config['test_simple_form']['fields']['ua']['type'] = 'useragent';
        $app['extensions.SimpleForms']->config['test_simple_form']['fields']['now']['type'] = 'timestamp';
        $parameters = $this->getPostParameters();

        $mailer = $this->getMock('\Swift_Mailer', array('send'), array($app['swiftmailer.transport']));
        $mailer->expects($this->any())
            ->method('send')
            ->will($this->returnCallback(function ($message) {
                    $message = $message->toString();
                    \PHPUnit_Framework_Assert::assertRegExp('#ip: 8.8.8.8#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#host: simpleforms.bolt.cm#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#ua: SimpleForms/2.X#', $message);
                    \PHPUnit_Framework_Assert::assertRegExp('#now: [0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}#', $message);
                }
            ))
        ;
        $app['mailer'] = $mailer;

        $server = array(
            'SERVER_NAME'          => 'localhost',
            'SERVER_PORT'          => 80,
            'HTTP_HOST'            => 'unittest.bolt.cm',
            'HTTP_USER_AGENT'      => 'SimpleForms/2.X',
            'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET'  => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR'          => '8.8.8.8',
            'REMOTE_HOST'          => 'simpleforms.bolt.cm',
            'SCRIPT_NAME'          => 'SimpleForms.php',
            'SCRIPT_FILENAME'      => __FILE__,
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'REQUEST_TIME'         => 42,
        );

        $app['request'] = Request::create('/', 'POST', $parameters, array(), array(), $server);
        $app->boot();
        $extension->simpleForm('test_simple_form');
    }

    public function testInvalidFormName()
    {
        $app = $this->getApp();
        $extension = $this->getExtension($app);

        $html = $extension->simpleForm('the_koala_ate_it');

        $this->assertInstanceOf('\Twig_Markup', $html);
        $this->assertSame("<p><strong>SimpleForms is missing the configuration for the form named 'the_koala_ate_it'!</strong></p>", (string) $html);
    }

    public function testSetupOverrides()
    {
        $app = $this->getApp(false);
        $extension = $this->getExtension($app);
        $app['extensions.SimpleForms']->config['recaptcha_enabled'] = 'Yes, why not.';
        $app['extensions.SimpleForms']->config['recaptcha_private_key'] = 'ninja_koala';
        $app['extensions.SimpleForms']->config['recaptcha_public_key'] = 'koalas_r_us';
        $app['extensions.SimpleForms']->config['recaptcha_error_message'] = 'Drop Bear Attack!';
        $app['extensions.SimpleForms']->config['recaptcha_theme'] = 'the_land_downunder';
        $app['extensions.SimpleForms']->config['testmode'] = 'Sure thing';
        $app['extensions.SimpleForms']->config['testmode_recipient'] = 'testaddress@example.com';
        $app['extensions.SimpleForms']->config['csrf'] = 'Make it so';

        $app['extensions.SimpleForms']->config['template'] = 'assets/simpleforms_form.twig';
        $app['extensions.SimpleForms']->config['mail_template'] = 'assets/simpleforms_mail.twig';

        $parameters = $this->getPostParameters();
        $app['request'] = Request::create('/', 'POST', $parameters);
        $app->boot();

        $extension->simpleForm('test_simple_form');

        $this->assertSame('Yes, why not.', $app['extensions.BoltForms']->config['recaptcha']['enabled']);
        $this->assertSame('koalas_r_us', $app['extensions.BoltForms']->config['recaptcha']['public_key']);
        $this->assertSame('ninja_koala', $app['extensions.BoltForms']->config['recaptcha']['private_key']);
        $this->assertSame('Drop Bear Attack!', $app['extensions.BoltForms']->config['recaptcha']['error_message']);
        $this->assertSame('the_land_downunder', $app['extensions.BoltForms']->config['recaptcha']['theme']);
        $this->assertSame('Sure thing', $app['extensions.BoltForms']->config['debug']['enabled']);
        $this->assertSame('testaddress@example.com', $app['extensions.BoltForms']->config['debug']['address']);
        $this->assertSame('Make it so', $app['extensions.BoltForms']->config['csrf']);

        $this->assertSame('assets/simpleforms_form.twig', $app['extensions.BoltForms']->config['templates']['form']);
        $this->assertSame('assets/simpleforms_mail.twig', $app['extensions.BoltForms']->config['templates']['email']);

        $map = array(
            'config'  => 'config',
            'data'    => 'form',
            'fields'  => 'fields',
            'subject' => 'subject',
        );
        $this->assertSame($map, $app['extensions.BoltForms']->config['fieldmap']['email']);
    }

    public function testFormValidationException()
    {
        $app = $this->getApp(false);
        $extension = $this->getExtension($app);

        // Keep an eye on the logger
        $logger = $this->getMock('\Monolog\Logger', array('debug'), array('testlogger'));
        $logger->expects($this->atLeastOnce())
            ->method('debug')
            ->will($this->returnCallback(function ($message) {
                    \PHPUnit_Framework_Assert::assertSame('[SimpleForms] Form validation exception: There was an error in the form. Please check the form, and try again.', $message);
                }
            ))
        ;
        $app['logger.system'] = $logger;

        $parameters = $this->getPostParameters();
        unset($parameters['test_simple_form']['name']);

        $app['request'] = Request::create('/', 'POST', $parameters);
        $app->boot();

        $extension->simpleForm('test_simple_form');
    }

    public function testRecaptchaRenderAddition()
    {
        $app = $this->getApp(false);
        $extension = $this->getExtension($app);
        $app['extensions.SimpleForms']->config['recaptcha_enabled'] = true;
        $app['extensions.SimpleForms']->config['recaptcha_private_key'] = 'abc123';
        $app['extensions.SimpleForms']->config['recaptcha_public_key'] = 'cde456';

        $app['request'] = Request::create('/');
        $app->boot();

        $html = $extension->simpleForm('test_simple_form');
        $this->assertInstanceOf('\Twig_Markup', $html);

        $html = (string) $html;
        $this->assertRegExp('#<script src="https://www.google.com/recaptcha/api.js\?hl=en-GB" async defer></script>#', $html);
        $this->assertRegExp('#<div class="g-recaptcha" data-sitekey="cde456"></div>#', $html);
    }
}
