<?php

namespace Schobner\SwiftMailerDBLogBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('schobner_swift_mailer_db_log');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('email_log_entity')->end()
            ->end()
        ;

        return $treeBuilder;
    }

    // TODO: Test configuration import?!
}
