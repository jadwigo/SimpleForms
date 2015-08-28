<?php

namespace Bolt\Extension\Bolt\SimpleForms\Tests;

use Bolt\Extension\Bolt\BoltForms\Extension as BoltFormsExtension;
use Bolt\Extension\Bolt\BoltForms\Provider\BoltFormsServiceProvider;
use Bolt\Extension\Bolt\SimpleForms\Extension;
use Bolt\Tests\BoltUnitTest;

/**
 * Base class for SimpleForms testing.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class AbstractSimpleFormsUnitTest extends BoltUnitTest
{
    public function getApp($boot = true)
    {
        $app = parent::getApp($boot);
        $extension = new Extension($app);
        $boltforms = new BoltFormsExtension($app);
        $boltformsSp = new BoltFormsServiceProvider();
        $boltformsSp->register($app);

        $config = $this->getMock('\Bolt\Config', array('getWhichEnd'), array($app));
        $config->expects($this->any())
            ->method('getWhichEnd')
            ->will($this->returnValue('frontend'));
        $app['config'] = $config;

        $app['extensions']->register($boltforms);
        $app['extensions']->register($extension);

        $app->boot();
        return $app;
    }

    /**
     * @param \Bolt\Application $app
     *
     * @return \Bolt\Extension\Bolt\SimpleForms\Extension
     */
    public function getExtension($app = null)
    {
        if ($app === null) {
            $app = $this->getApp();
        }

        $this->setDefaultConfig($app['extensions.SimpleForms']);

        return $app['extensions.SimpleForms'];
    }

    /**
     * @param Extension $extension
     */
    protected function setDefaultConfig(Extension $extension)
    {
        unset($extension->config['demo']);
        unset($extension->config['contact']);
        $extension->config['csrf'] = false;
        $extension->config['test_simple_form'] = array(
            'mail_subject'        => 'Testing Email Subject Line',
            'from_name'           => 'Lodewijk Evers',
            'from_email'          => 'jadwigo@example.org',
            'recipient_name'      => 'Gawain Lynch',
            'recipient_email'     => 'gawain@example.org',
            'recipient_cc_name'   => 'Xiao-Hu Tai',
            'recipient_cc_email'  => 'xiao@example.org',
            'recipient_bcc_name'  => 'Bob den Otter',
            'recipient_bcc_email' => 'bob@example.org',
            'insert_into_table'   => 'bolt_simple_test_form',
            'storage_location'    => 'test_uploads',
            'attach_files'        => true,
            'button_text'         => 'Send me away',
            'fields'              => array(
                'name' => array(
                    'type'        => 'text',
                    'placeholder' => 'Name of the game is',
                    'required'    => true,
                ),
                'email' => array(
                    'type'        => 'email',
                    'label'       => 'Your email address',
                    'placeholder' => 'you@example.com',
                    'required'    => true,
                    'use_as'      => 'from_email',
                    'use_with'    => 'name',
                ),
                'subject' => array(
                    'type'        => 'text',
                    'label'       => 'Other test subject',
                    'placeholder' => 'You rang',
                    'required'    => false,
                    'class'       => 'wide',
                    'maxlength'   => 30,
                ),
                'message' => array(
                    'type'        => 'textarea',
                    'required'    => true,
                    'placeholder' => 'Once upon a time',
                ),
                'pets' => array(
                    'type'        => 'choice',
                    'label'       => 'What is your favourite type of pet',
                    'required'    => true,
                    'empty_value' => 'My favorite animals are',
                    'choices'     => array(
                        'Kittens',
                        'Puppies',
                        'Penguins',
                        'Koala bears',
                        "I do not like animals"
                    ),
                ),
                'upload' => array(
                    'type'     => 'file',
                    'label'    => 'Upload your picture',
                    'required' => false,
                    'filetype' => array(
                        'jpg',
                        'gif',
                        'tiff',
                        'png',
                        'pdf',
                    ),
                    'mimetype' => array(
                        'application/pdf',
                        'application/x-pdf',
                        'image/tiff',
                        'image/x-tiff',
                        'image/png',
                        'image/jpeg',
                        'image/pjpeg',
                        'image/gif'
                    ),
                ),
                'newsletter' => array(
                    'type'        => 'checkbox',
                    'label'       => 'Newsletter',
                    'placeholder' => 'Send me the newsletters',
                    'required'    => false,
                ),
                'signup' => array(
                    'type'        => 'checkbox',
                    'label'       => 'Agree to this',
                    'placeholder' => 'Yes, of course I agree.',
                    'required'    => false,
                ),
            ),
        );
    }

    protected function getPostParameters()
    {
        return array(
            'test_simple_form' => array(
                'name'       => 'Road Runner',
                'email'      => 'road@runner.com',
                'subject'    => 'Beep beep',
                'message'    => 'Catch me if you can',
                'pets'       => 1,
                'upload'     => null,
                'newsletter' => 1,
                'signup'     => 1,
            )
        );
    }
}
