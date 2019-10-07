<?php

namespace Schobner\SwiftMailerDBLogBundle\FCApiClientBundle;

use Schobner\SwiftMailerDBLogBundle\DependencyInjection\SchobnerSwiftMailerDBLogExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SchobnerSwiftMailerDBLogBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new SchobnerSwiftMailerDBLogExtension();
    }
}
