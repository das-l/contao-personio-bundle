<?php

declare(strict_types=1);

/*
 * This file is part of contao-personio-bundle.
 *
 * (c) Das L – Alex Wuttke Software & Media
 *
 * @license LGPL-3.0-or-later
 */

namespace LumturoNet\ContaoPersonioBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('contao_personio');
        $treeBuilder
            ->getRootNode()
            ->children()
                ->append($this->addRecruitingFormNode())
                ->scalarNode('recruiting_company_id')->defaultNull()->end()
                ->scalarNode('recruiting_api_token')->defaultNull()->end()
                ->scalarNode('recruiting_init_phase')->defaultNull()->end()
            ->end()
        ;

        return $treeBuilder;
    }

    private function addRecruitingFormNode(): NodeDefinition
    {
        return (new TreeBuilder('recruiting_form'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('system_fields')
                    ->scalarPrototype()->end()
                    ->defaultValue([
                        'location',
                        'phone',
                    ])
                ->end()
                ->arrayNode('custom_fields')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('attribute_id')->end()
                            ->arrayNode('field_config')
                                ->variablePrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('file_fields')
                    ->scalarPrototype()->end()
                    ->defaultValue([
                        'cv'
                    ])
                ->end()
                ->arrayNode('field_order')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
            ->end()
        ;
    }
}
