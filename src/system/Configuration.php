<?php

namespace system;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration
{
    /* @var string[] $settings */
    private $settings;

    /**
     * @param string[] $configPaths a list of directories where to look for config files
     * @param string   $filename    the config file to load
     */
    public function __construct($configPaths, $filename)
    {
        $this->settings = new Settings($configPaths, $filename, $this->buildConfigTree());
        $this->settings->load();
    }

    /**
     * @param $name
     * @return string
     */
    public function getValue($name)
    {
        return $this->settings[$name];
    }

    /**
     * @return \Symfony\Component\Config\Definition\NodeInterface
     */
    protected function buildConfigTree()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('allSettings');
        $rootNode
            ->children()
                ->arrayNode('jimFlowKlopfer')
                    ->children()
                        ->booleanNode('run')->defaultTrue()->end()
                        ->scalarNode('command')->isRequired()->end()
                        ->scalarNode('photoDirectory')->isRequired()->end()
                        ->scalarNode('jsonDirectory')->isRequired()->end()
                    ->end()
                ->end()
                ->arrayNode('board')
                    ->children()
                        ->scalarNode('provider_name')->end()
                        ->scalarNode('domain')->isRequired()->end()
                        ->scalarNode('apiToken')->isRequired()->end()
                        ->scalarNode('boardId')->isRequired()->end()
                        ->arrayNode('commands')
                            ->children()
                                ->scalarNode('move')->isRequired()->end()
                        //        ->scalarNode('config')->isRequired()->end()
                            ->end()
                        ->end()
                        ->scalarNode('ticketRegex')->isRequired()->end()
                        ->arrayNode('columns')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
        return $treeBuilder->buildTree();
    }
}
