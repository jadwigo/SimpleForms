<?php

namespace Bolt\Extension\Bolt\SimpleForms;

use Bolt\Application;
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

        // Set up SimpleForms and BoltForms differences
        $formDefinition = $this->convertFormConfig($this->config[$formName]);
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
            } catch (FileUploadException $e) {
                $error = $e->getMessage();
                $this->app['logger.system']->debug('[SimpleForms] File upload exception: ' . $error, array('event' => 'extensions'));
            } catch (FormValidationException $e) {
                $error = $e->getMessage();
                $this->app['logger.system']->debug('[SimpleForms] Form validation exception: ' . $error, array('event' => 'extensions'));
            }
        }

        // Get our values to be passed to Twig
        $use_ssl = $this->app['request']->isSecure();
        $fields = $this->app['boltforms']->getForm($formName)->all();
        $twigvalues = array(
            'submit'          => 'Send',
            'form'            => $this->app['boltforms']->getForm($formName)->createView(),
            'message'         => $message,
            'error'           => $error,
            'sent'            => $sent,
            'formname'        => $formName,
            'recaptcha_html'  => ($this->config['recaptcha_enabled'] ? recaptcha_get_html($this->config['recaptcha_public_key'], null, $use_ssl) : ''),
            'recaptcha_theme' => ($this->config['recaptcha_enabled'] ? $this->config['recaptcha_theme'] : ''),
            'button_text'     => $this->config['button_text']
        );

        // Render the Twig_Markup
        return $this->app['boltforms']->renderForm($formName, $this->config['template'], $twigvalues);
    }

    /**
     * Override BoltForms settings.
     */
    protected function setupOverrides()
    {
        // Override the private key in BoltForms for reCaptcha.
        if (!empty($this->config['recaptcha_private_key'])) {
            $this->boltFormsExt->config['recaptcha']['private_key'] = $this->config['recaptcha_private_key'];
        }

        // Override email debug settings in BoltForms.
        $this->boltFormsExt->config['debug']['enabled'] = $this->config['testmode'];
        $this->boltFormsExt->config['debug']['address'] = $this->config['testmode_recipient'];

        // Override CSRF setting
        $this->boltFormsExt->config['csrf'] = $this->config['csrf'];

        // Override templates
        $this->boltFormsExt->config['templates']['form'] = $this->config['template'];
        $this->boltFormsExt->config['templates']['email'] = $this->config['mail_template'];
    }

    /**
     * Convert SimpleForms field configuration to Symfony/BoltForms style.
     *
     * @param array $fields
     *
     * @return array
     */
    protected function convertFormConfig(array $fields)
    {
        $newFields = array();
        $useMap = array(
            'from_email'          => 'from_email',
            'recipient_cc_email'  => 'cc_email',
            'recipient_bcc_email' => 'bcc_email',
        );
        $useMapMatch = array(
            'from_email'          => 'from_name',
            'recipient_cc_email'  => 'cc_name',
            'recipient_bcc_email' => 'bcc_name',
        );

        $newFields['notification'] = array(
            'enabled'       => 'true',
            'subject'       => isset($fields['mail_subject']) ? $fields['mail_subject'] : 'Your message was submitted',
            'from_name'     => isset($fields['from_name']) ? $fields['from_name'] : null,
            'from_email'    => isset($fields['from_email']) ? $fields['from_email'] : null,
            'replyto_name'  => isset($fields['recipient_name']) ? $fields['recipient_name'] : null,
            'replyto_email' => isset($fields['replyto_email']) ? $fields['replyto_email'] : null,
            'to_name'       => isset($fields['recipient_name']) ? $fields['recipient_name'] : null,
            'to_email'      => isset($fields['recipient_email']) ? $fields['recipient_email'] : null,
            'cc_name'       => isset($fields['recipient_cc_name']) ? $fields['recipient_cc_name'] : null,
            'cc_email'      => isset($fields['recipient_cc_email']) ? $fields['recipient_cc_email'] : null,
            'bcc_name'      => isset($fields['recipient_bcc_name']) ? $fields['recipient_bcc_name'] : null,
            'bcc_email'     => isset($fields['recipient_bcc_email']) ? $fields['recipient_bcc_email'] : null,
            'attach_files'  => isset($fields['attach_files']) ? $fields['attach_files'] : false,
        );

        $newFields['feedback'] = array(
            'success' => $this->config['message_ok'],
            'error'   => $this->config['message_error'],
        );

        $newFields['templates'] = array(
            'form'  => $this->config['template'],
            'email' => $this->config['mail_template'],
        );

        if (isset($fields['insert_into_table'])) {
            $newFields['database']['table'] = $fields['insert_into_table'];
        }

        foreach ($fields['fields'] as $field => $values) {
            $newFields['fields'][$field]['type'] = isset($values['type']) ? $values['type'] : 'submit';
            if ($newFields['fields'][$field]['type'] === 'submit') {
                continue;
            }

            $newFields['fields'][$field]['options'] = array(
                'required' => isset($values['required']) ? $values['required'] : false,
                'label'    => isset($values['label']) ? $values['label'] : null,
                'attr'     => array(
                    'placeholder' => isset($values['placeholder']) ? $values['placeholder'] : null,
                    'class'       => isset($values['class']) ? $values['class'] : null,
                    'type'        => $newFields['fields'][$field]['type'], // Compatibility
                ),
                'constraints' => $this->getContraints($values),
            );

            // Translate the use_as & use_with values
            if (isset($values['use_as']) && $use = $values['use_as']) {
                $newFields['notification'][$useMap[$use]] = $field;
                $newFields['notification'][$useMapMatch[$use]] = $values['use_with'];
            }

            // Set last
            if ($newFields['fields'][$field]['type'] === 'choice') {
                $newFields['fields'][$field]['options']['choices'] = $values['choices'];
                $newFields['fields'][$field]['options']['empty_value'] = isset($values['empty_value']) ? $values['empty_value'] : '';
                $newFields['fields'][$field]['options']['multiple'] = isset($values['multiple']) ? $values['multiple'] : false;
            }
        }

        return $newFields;
    }

    /**
     * Get a set of validation constraints.
     *
     * @param array|string $fieldValues
     *
     * @return array|null
     */
    protected function getContraints($fieldValues)
    {
        if (!is_array($fieldValues)) {
            return;
        }

        $constraints = array();

        // Set NotBlank validator for 'required' fields
        if (isset($fieldValues['required']) && $fieldValues['required']) {
            $constraints[] = 'NotBlank';
        }

        // Set Length validator for minlength or maxlength options
        if (isset($fieldValues['minlength']) || isset($fieldValues['maxlength'])) {
            $constraints[] = array('Length' => array(
                'min' => isset($fieldValues['minlength']) ? $fieldValues['minlength'] : null,
                'max' => isset($fieldValues['maxlength']) ? $fieldValues['maxlength'] : null,
            ));
        }

        // Set Email validator for email field types
        if (isset($fieldValues['type']) && $fieldValues['type'] === 'email') {
            $constraints[] = 'Email';
        }

        // Set File validator for file field types
        if (isset($fieldValues['type']) && $fieldValues['type'] === 'file') {
            if (!isset($fieldValues['filetype']) || empty($fieldValues['filetype'])) {
                $fieldValues['filetype'] = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc', 'docx');
            }

            $constraints[] = array('File' => array(
                'mimeTypes'        => $fieldValues['mimetype'],
                'mimeTypesMessage' => $fieldValues['mime_types_message'] . ' ' . implode(', ', $fieldValues['filetype']),
            ));
        }

        return $constraints;
    }
}
