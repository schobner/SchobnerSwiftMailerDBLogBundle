<?php

namespace Schobner\SwiftMailerDBLogBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SchobnerSwiftMailerDBLogExtension extends Extension
{

    /**
     * {@inheritdoc}
     *
     * @param array $configs
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
