<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\DiExtraBundle\DependencyInjection;

use JMS\DiExtraBundle\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class JMSDiExtraExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->mergeConfigs($configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('controller_injection.xml');

        $container->setParameter('jms_di_extra.all_bundles', $config['locations']['all_bundles']);
        $container->setParameter('jms_di_extra.bundles', $config['locations']['bundles']);
        $container->setParameter('jms_di_extra.directories', $config['locations']['directories']);

        $this->configureMetadata($config['metadata'], $container);
    }

    private function configureMetadata(array $config, $container)
    {
        if ('none' === $config['cache']) {
            return;
        }

        if ('file' === $config['cache']) {
            $cacheDir = $container->getParameterBag()->resolveValue($config['file_cache']['dir']);
            if (!file_exists($cacheDir)) {
                if (false === @mkdir($cacheDir, 0777, true)) {
                    throw new RuntimeException(sprintf('The cache dir "%s" could not be created.', $cacheDir));
                }
            }
            if (!is_writable($cacheDir)) {
                throw new RuntimeException(sprintf('The cache dir "%s" is not writable.', $cacheDir));
            }

            $container
                ->getDefinition('jms_di_extra.metadata.cache.file_cache')
                ->replaceArgument(0, $cacheDir)
            ;
        } else {
            $container->setAlias('jms_di_extra.metadata.cache', new Alias($config['cache'], false));
        }
    }

    private function mergeConfigs(array $configs)
    {
        $processor = new Processor();
        $configuration = new Configuration();

        return $processor->process($configuration->getConfigTreeBuilder()->buildTree(), $configs);
    }
}