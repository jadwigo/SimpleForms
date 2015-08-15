<?php

use Bolt\Extension\Bolt\SimpleForms\Extension;

if (isset($app)) {
    $app['extensions']->register(new Extension($app));
}
