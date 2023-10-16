<?php

declare(strict_types=1);

/*
 * This file is part of contao-personio-bundle.
 *
 * (c) rolf.staege@lumturo.net
 *
 * @license LGPL-3.0-or-later
 */

namespace LumturoNet\ContaoPersonioBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ContaoPersonioExtension extends Extension
{
    public function load(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $mergedConfig);

        foreach (['recruiting_form', 'recruiting_company_id', 'recruiting_api_token', 'recruiting_init_phase'] as $parameter) {
            $container->setParameter('contao_personio.'.$parameter, $config[$parameter]);
        }
    }
}
