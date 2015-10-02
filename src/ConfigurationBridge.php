<?php

namespace Bolt\Extension\Bolt\SimpleForms;

/**
 * SimpleForms configration bridge
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ConfigurationBridge
{
    /** @var array */
    private $fields;

    /**
     * Constructor.
     *
     * @param string $formName
     * @param array  $config
     */
    public function __construct($formName, array $config)
    {
        $this->convertFormConfig($formName, $config);
    }

    /**
     * Get the fields configuation.
     *
     * @return array
     */
    public function get()
    {
        return $this->fields;
    }

    /**
     * Convert SimpleForms field configuration to Symfony/BoltForms style.
     *
     * @param string $formName
     *
     * @return array
     */
    protected function convertFormConfig($formName, array $config)
    {
        $fields = $config[$formName];
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
            'debug'         => $config['testmode'],
            'subject'       => isset($fields['mail_subject']) ? $fields['mail_subject'] : 'Your message was submitted',
            'from_name'     => isset($fields['from_name']) ? $fields['from_name'] : $config['from_name'],
            'from_email'    => isset($fields['from_email']) ? $fields['from_email'] : $config['from_email'],
            'replyto_name'  => isset($fields['recipient_name']) ? $fields['recipient_name'] : null,
            'replyto_email' => isset($fields['replyto_email']) ? $fields['replyto_email'] : null,
            'to_name'       => isset($fields['recipient_name']) ? $fields['recipient_name'] : null,
            'to_email'      => isset($fields['recipient_email']) ? $fields['recipient_email'] : null,
            'cc_name'       => isset($fields['recipient_cc_name']) ? $fields['recipient_cc_name'] : null,
            'cc_email'      => isset($fields['recipient_cc_email']) ? $fields['recipient_cc_email'] : null,
            'bcc_name'      => isset($fields['recipient_bcc_name']) ? $fields['recipient_bcc_name'] : null,
            'bcc_email'     => isset($fields['recipient_bcc_email']) ? $fields['recipient_bcc_email'] : null,
        );

        $newFields['feedback'] = array(
            'success'  => $config['message_ok'],
            'error'    => $config['message_error'],
            'redirect' => array(
                'target' => isset($fields['redirect_on_ok']) ? $fields['redirect_on_ok'] : null,
            ),
        );

        $newFields['templates'] = array(
            'form'  => $config['template'],
            'email' => $config['mail_template'],
        );

        $events = array('ip', 'remotehost', 'useragent', 'timestamp');
        foreach ($fields['fields'] as $field => $values) {
            // Default to text field if nothing set
            $newFields['fields'][$field]['type'] = isset($values['type']) ? $values['type'] : 'text';

            // Handle event driven fields
            if (in_array($fields['fields'][$field]['type'], $events)) {
                $newFields['fields'][$field] = $this->getEventFieldValues($values);
                continue;
            }

            // Compile base options
            $newFields['fields'][$field]['options'] = array(
                'required'    => isset($values['required']) ? $values['required'] : false,
                'label'       => isset($values['label']) ? $values['label'] : null,
                'attr'        => $this->getAttributeKeys($values),
                'constraints' => $this->getContraints($values),
            );
            $newFields['fields'][$field]['options']['attr']['type'] = $newFields['fields'][$field]['type']; // Compatibility

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

        $this->fields = $newFields;
    }

    /**
     * Get event data for a BoltForms event field value.
     *
     * @param array $values
     *
     * @return array
     */
    protected function getEventFieldValues($values)
    {
        $nameMap = array(
            'ip'         => 'server_value',
            'remotehost' => 'server_value',
            'useragent'  => 'server_value',
            'timestamp'  => 'timestamp',
        );
        $paramsMap = array(
            'ip'         => array('key'    => 'REMOTE_ADDR'),
            'remotehost' => array('key'    => 'REMOTE_HOST'),
            'useragent'  => array('key'    => 'HTTP_USER_AGENT'),
            'timestamp'  => array('format' => '%F %T'),
        );
        $event = array(
            'type'    => 'hidden',
            'options' => array(
                'label' => false
            ),
            'event' => array(
                'name'   => $nameMap[$values['type']],
                'params' => $paramsMap[$values['type']],
            ),
        );

        return $event;
    }

    /**
     * Map the Symfony forms attributes.
     *
     * @param array $values
     *
     * @return array
     */
    protected function getAttributeKeys(array $values)
    {
        $validAttrs = array(
            'autocomplete', 'autofocus', 'class', 'hint', 'maxlength', 'minlength',
            'pattern', 'placeholder', 'postfix', 'prefix', 'value',
        );
        $attr = array();

        foreach ($validAttrs as $validAttr) {
            if (isset($values[$validAttr])) {
                $attr[$validAttr] = $values[$validAttr];
            }
        }

        return $attr;
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

        return $constraints;
    }
}
