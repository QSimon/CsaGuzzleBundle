<?php

/*
 * This file is part of the CsaGuzzleBundle package
 *
 * (c) Charles Sarrazin <charles@sarraz.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace Csa\Bundle\GuzzleBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Csa Guzzle client compiler pass
 *
 * @author Charles Sarrazin <charles@sarraz.in>
 */
class SubscriberPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // log
        $definition = $container->findDefinition('csa_guzzle.subscriber.log');
        $logServiceId = $container->getParameter('csa_guzzle.subscriber.log.service');
        if ($container->hasDefinition($logServiceId)) {
            $definition->replaceArgument(0, new Reference($logServiceId));
        }

        $channel = $container->getParameter('csa_guzzle.subscriber.log.channel');
        if (!empty($channel)) {
            $definition->clearTag('monolog.logger');
                $definition->addTag('monolog.logger', array(
                    'channel' => $channel,
            ));
        }

        // Cache
        $cacheServiceId = $container->getParameter('csa_guzzle.subscriber.cache.service');
        if ($container->hasDefinition($cacheServiceId)) {
            $container->setDefinition('csa_guzzle.cache_storage', new Definition('%csa_guzzle.cache_storage.class%', array(
                new Reference($cacheServiceId),
                $container->getParameter('csa_guzzle.subscriber.cache.prefix'),
            )));

            $cacheSubscriber = new Definition('%csa_guzzle.subscriber.cache.class%', array(
                new Reference('csa_guzzle.cache_storage'),
                $container->getParameter('csa_guzzle.subscriber.cache.enable'),
            ));

            $cacheSubscriber->addTag('csa_guzzle.subscriber');

            $container->setDefinition('csa_guzzle.subscriber.cache', $cacheSubscriber);
        }


        // get all Guzzle subscribers
        $subscribers = $container->findTaggedServiceIds('csa_guzzle.subscriber');

        if (!count($subscribers)) {
            return;
        }

        // Factory
        $factory = $container->findDefinition('csa_guzzle.client_factory');
        $arg = [];
        foreach ($subscribers as $subscriber => $options) {
            $arg[] = new Reference($subscriber);
        }
        $factory->replaceArgument(1, $arg);
    }
}
