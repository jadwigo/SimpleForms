<?php

namespace Bolt\Extension\Bolt\SimpleForms;

/**
 * Simple forms Extension for Bolt
 */
class Extension extends \Bolt\BaseExtension
{
    /** @var string Extension name */
    const NAME = 'SimpleForms';
    /** @var string Extension's service container */
    const CONTAINER = 'extensions.SimpleForms';

    /**
     * Provide default Extension Name
     */
    public function getName()
    {
        return Extension::NAME;
    }

    /**
     * Allow users to place {{ simpleforms() }} tags into content, if
     * `allowtwig: true` is set in the contenttype.
     *
     * @return boolean
     */
    public function isSafe()
    {
        return true;
    }

    /**
     * Let Bolt know this extension sends e-mails. The user will see a
     * notification on the dashboard if mail is not set up correctly.
     */
    public function sendsMail()
    {
        return true;
    }

    public function initialize()
    {
        if ($this->app['config']->getWhichEnd() === 'frontend') {
            // Insert the CSS as requried
            if (!empty($this->config['stylesheet'])) {
                $this->addCSS($this->config['stylesheet']);
            }

            // Add Twig functions
            $this->addTwigFunction('simpleform', 'simpleForm');
        }
    }

    /**
     * Create a simple Form.
     *
     * @param string $formname
     * @param array  $with
     *
     * @return \Twig_Markup
     */
    public function simpleForm($formname = '', $with = array())
    {
        $this->app['twig.loader.filesystem']->addPath(__DIR__);

        return (new SimpleForms($this->app))->simpleForm($formname, $with);
    }

    /**
     * Set the defaults for configuration parameters
     *
     * @return array
     */
    protected function getDefaultConfig()
    {
        return array(
            'stylesheet'              => '',
            'template'                => 'assets/simpleforms_form.twig',
            'mail_template'           => 'assets/simpleforms_mail.twig',
            'message_ok'              => 'Thanks! Your message has been sent.',
            'message_error'           => 'There was an error in the form. Please check the form, and try again.',
            'message_technical'       => 'There were some technical difficulties, so your message was not sent. We apologize for the inconvenience.',
            'button_text'             => 'Send',
            'recaptcha_enabled'       => false,
            'recaptcha_public_key'    => '',
            'recaptcha_private_key'   => '',
            'recaptcha_error_message' => "The CAPTCHA wasn't entered correctly. Please try again.",
            'recaptcha_theme'         => 'clean',
            'csrf'                    => true,
            'from_email'              => null,
            'from_name'               => null,
            'recipient_cc_email'      => '',
            'recipient_cc_name'       => '',
            'recipient_bcc_email'     => '',
            'recipient_bcc_name'      => '',
            'testmode '               => true,
            'testmode_recipient'      => 'info@example.com'
        );
    }
}
