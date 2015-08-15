<?php

namespace Bolt\Extension\Bolt\SimpleForms\Tests;

use Bolt\Extension\Bolt\SimpleForms\Extension;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

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

        $config = $this->getMock('\Bolt\Config', array('getWhichEnd'), array($app));
        $config->expects($this->any())
            ->method('getWhichEnd')
            ->will($this->returnValue('frontend'));
        $app['config'] = $config;

        $app['extensions']->register($extension);

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

        return $app['extensions.SimpleForms'];
    }
}
