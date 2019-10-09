<?php

namespace Schobner\SwiftMailerDBLogBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNote = $treeBuilder->root('schobner_swift_mailer_db_log');

        $rootNote
            ->children()
                ->scalarNode('email_log_entity')->end()
            ->end()
        ;

        return $treeBuilder;
    }

    // TODO: Test configuration import?!
}
