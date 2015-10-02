<?php

namespace Bolt\Extension\Bolt\SimpleForms;

use Bolt\Application;
use Bolt\Extension\Bolt\BoltForms\Event\BoltFormsEvents;
use Bolt\Extension\Bolt\BoltForms\Event\BoltFormsProcessorEvent;
use Bolt\Extension\Bolt\BoltForms\Exception\FileUploadException;
use Bolt\Extension\Bolt\BoltForms\Exception\FormValidationException;

/**
 * SimpleForms functionality class
 */
class SimpleForms
{
    /** @var Application */
    private $app;
    /** @var array */
    private $config;
    /** @var array */
    private $boltFormsExt;
    /** @var string */
    private $listeningFormName;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $this->app[Extension::CONTAINER]->config;

        $bfContainer = \Bolt\Extension\Bolt\BoltForms\Extension::CONTAINER;
        $this->boltFormsExt = $this->app[$bfContainer];
    }

    /**
     * Create a simple Form.
     *
     * @param string $formName
     * @param array  $with
     *
     * @return \Twig_Markup
     */
    public function simpleForm($formName = '', $with = array())
    {
        if (!isset($this->config[$formName])) {
            return new \Twig_Markup("<p><strong>SimpleForms is missing the configuration for the form named '$formName'!</strong></p>", 'UTF-8');
        }

        // sanity check if there is a sender address for this form
        if (!isset($this->config[$formName]['from_email']) && !isset($this->config['from_email'])) {
            return new \Twig_Markup("<p><strong>SimpleForms is missing a sender email address for the form named '$formName'!</strong></p>", 'UTF-8');
        }

        // setup a default sender address
        if(empty($this->config['from_name']) && !empty($this->config['from_email'])) {
            $this->config['from_name'] = $this->config['from_email'];
        }

        // Handle submitted form value transformation.
        $this->app['dispatcher']->addListener(BoltFormsEvents::SUBMISSION_PROCESSOR,  array($this, 'submissionProcessor'));
        $this->listeningFormName = $formName;

        // Set up SimpleForms and BoltForms differences
        $formDefinition = (new ConfigurationBridge($formName, $this->config))->get();

        // Set configuration overrides for BoltForms
        $this->setupOverrides();

        $data = array();
        $options = array();
        $sent = false;
        $message = '';
        $error = '';
        $recaptchaResponse = array(
            'success'    => true,
            'errorCodes' => null
        );

        $this->app['boltforms']->makeForm($formName, 'form', $data, $options);

        $fields = $formDefinition['fields'];

        // Add our fields all at once
        $this->app['boltforms']->addFieldArray($formName, $fields);

        // Handle the POST
        if ($this->app['request']->isMethod('POST')) {
            // Check reCaptcha, if enabled.
            $recaptchaResponse = $this->app['boltforms.processor']->reCaptchaResponse($this->app['request']);

            try {
                $sent = $this->app['boltforms.processor']->process($formName, $formDefinition, $recaptchaResponse);
                $message = isset($this->config['message_ok']) ? $this->config['message_ok'] : 'Thanks! Your message has been sent.';
            } catch (FormValidationException $e) {
                $error = $e->getMessage();
                $this->app['logger.system']->debug('[SimpleForms] Form validation exception: ' . $error, array('event' => 'extensions'));
            }
        }

        // Get our values to be passed to Twig
        $fields = $this->app['boltforms']->getForm($formName)->all();
        $twigvalues = array(
            'submit'          => 'Send',
            'form'            => $this->app['boltforms']->getForm($formName)->createView(),
            'message'         => $message,
            'error'           => $error,
            'sent'            => $sent,
            'formname'        => $formName,
            'recaptcha_html'  => $this->getRecaptchaHtml(),
            'recaptcha_theme' => $this->config['recaptcha_enabled'] ? $this->config['recaptcha_theme'] : '',
            'button_text'     => isset($this->config[$formName]['button_text']) ? $this->config[$formName]['button_text'] : $this->config['button_text']
        );

        // Render the Twig_Markup
        return $this->app['boltforms']->renderForm($formName, $this->config['template'], $twigvalues);
    }

    /**
     * Handle submitted form value transformation.
     *
     * @param BoltFormsProcessorEvent $event
     */
    public function submissionProcessor(BoltFormsProcessorEvent $event)
    {
        if ($event->getFormName() === $this->listeningFormName) {
            if (!isset($this->config[$this->listeningFormName]['fields'])) {
                return;
            }

            // Get the data from the event
            $data = $event->getData();

            foreach ($this->config[$this->listeningFormName]['fields'] as $name => $values) {
                if (isset($values['type']) && $values['type'] === 'choice') {
                    $data[$name] = $values['choices'][$data[$name]];
                } elseif (isset($values['type']) && $values['type'] === 'checkbox') {
                    if (gettype($data[$name]) === 'boolean') {
                        $data[$name] = $data[$name] ? 'yes' : 'no';
                    }
                }
            }

            // Save the data back
            $event->setData($data);
        }
    }

    /**
     * Override BoltForms settings.
     */
    protected function setupOverrides()
    {
        // Override the private key in BoltForms for reCaptcha.
        if (!empty($this->config['recaptcha_private_key'])) {
            $this->boltFormsExt->config['recaptcha']['private_key'] = $this->config['recaptcha_private_key'];
            $this->boltFormsExt->config['recaptcha']['public_key'] = $this->config['recaptcha_public_key'];
        }
        $this->boltFormsExt->config['recaptcha']['enabled'] = $this->config['recaptcha_enabled'];
        $this->boltFormsExt->config['recaptcha']['error_message'] = $this->config['recaptcha_error_message'];
        $this->boltFormsExt->config['recaptcha']['theme'] = $this->config['recaptcha_theme'];

        // Override email debug settings in BoltForms.
        $this->boltFormsExt->config['debug']['enabled'] = $this->config['testmode'];
        $this->boltFormsExt->config['debug']['address'] = $this->config['testmode_recipient'];

        // Override CSRF setting
        $this->boltFormsExt->config['csrf'] = $this->config['csrf'];

        // Override templates
        $this->boltFormsExt->config['templates']['form'] = $this->config['template'];
        $this->boltFormsExt->config['templates']['email'] = $this->config['mail_template'];

        // Override the field name mappings
        $this->boltFormsExt->config['fieldmap']['email'] = array(
            'config'  => 'config',
            'data'    => 'form',
            'fields'  => 'fields',
            'subject' => 'subject',
        );
    }

    /**
     * Generate the HTML to add the reCaptcha widget.
     *
     * @return \Twig_Markup
     */
    protected function getRecaptchaHtml()
    {
        if ($this->config['recaptcha_enabled'] !== true) {
            return '';
        }

        $context = array(
            'recaptcha' => array(
                'label'      => 'Recaptcha',
                'public_key' => $this->config['recaptcha_public_key'],
            )
        );

        return $this->app['twig']->render('assets/simpleforms_recaptcha.twig', $context);
    }
}
