<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('health_check');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('path')
                    ->defaultValue('/health')
                    ->info('URL path for the health check endpoint.')
                ->end()
                ->scalarNode('secret')
                    ->defaultNull()
                    ->info('Secret token required to expose detailed check results. If null, detailed results are never exposed.')
                ->end()
                ->scalarNode('header')
                    ->defaultValue('Authorization')
                    ->info('Name of the HTTP header used to send the secret token.')
                ->end()
                ->integerNode('timeout')
                    ->defaultValue(5)
                    ->min(1)
                    ->info('Maximum execution time in seconds for each individual check.')
                ->end()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                            ->info('Enable caching of health check results.')
                        ->end()
                        ->integerNode('ttl')
                            ->defaultValue(300)
                            ->min(0)
                            ->info('Cache TTL in seconds.')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('checks')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('doctrine')
                            ->defaultTrue()
                            ->info('Enable built-in Doctrine DBAL checks (one per connection, auto-detected).')
                        ->end()
                        ->booleanNode('asset_mapper')
                            ->defaultTrue()
                            ->info('Enable built-in AssetMapper check (verifies manifest.json exists, auto-detected).')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
