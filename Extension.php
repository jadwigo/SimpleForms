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
            if ($this->config['legacy']) {
                $class = new SimpleFormsLegacy($this->app);
            } else {
                $class = new SimpleForms($this->app);
            }

            $this->initializeTwig();
            $this->twigExtension->addTwigFunction(new \Twig_SimpleFunction('simpleform', array($class, 'simpleForm')));
        }
    }

    /**
     * Set the defaults for configuration parameters
     *
     * @return array
     */
    protected function getDefaultConfig()
    {
        return array(
            'legacy'     => true,
            'stylesheet' => '',
        );
    }
}
