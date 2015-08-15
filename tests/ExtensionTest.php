<?php

namespace Bolt\Extension\Bolt\SimpleForms\Tests;

use Bolt\Extension\Bolt\SimpleForms\Extension;
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
}
