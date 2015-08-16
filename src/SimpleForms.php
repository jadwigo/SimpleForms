<?php

namespace Bolt\Extension\Bolt\SimpleForms;

use Bolt\Application;

/**
 * SimpleForms functionality class
 */
class SimpleForms
{
    /** @var Application */
    private $app;
    /** @var array */
    private $config;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $this->app[Extension::CONTAINER]->config;
    }

    /**
     * Create a simple Form.
     *
     * @param string $formname
     *
     * @return \Twig_Markup
     */
    public function simpleForm($formname = '', $with = array())
    {
    }
}
