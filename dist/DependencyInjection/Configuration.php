<?php

/*
 * This file is part of the CsaGuzzleBundle package
 *
 * (c) Charles Sarrazin <charles@sarraz.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace Csa\Bundle\GuzzleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class contains the configuration information for the bundle
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author Charles Sarrazin <charles@sarraz.in>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('csa_guzzle');

        $rootNode
            ->fixXmlConfig('subscriber')
            ->children()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('service')
                            ->info('Doctrine Cache service id')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('prefix')
                            ->info('Cache key prefix')
                            ->defaultValue('http_call_')
                        ->end()
                        ->booleanNode('enable')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('log')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('format')
                            ->info('LogSubscriber Formatter template, see \GuzzleHttp\Subscriber\Log\Formatter')
                            ->defaultValue('[{ts}] "{method} {resource} {protocol}/{version}" {code}')
                        ->end()
                        ->scalarNode('service')
                            ->info('PSR3 Logger service id')
                            ->defaultValue('logger')
                        ->end()
                        ->scalarNode('channel')
                            ->info('Logger channel')
                            ->defaultValue('http_call')
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('profiler')
                    ->info('Whether or not to enable the profiler')
                    ->example('%kernel.debug%')
                    ->defaultFalse()
                ->end()
                ->arrayNode('subscribers')
                    ->useAttributeAsKey('name')
                    ->prototype('boolean')->end()
                ->end()
                ->arrayNode('factory')
                    ->children()
                        ->scalarNode('class')->defaultValue('Csa\Bundle\GuzzleBundle\Factory\Client')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
