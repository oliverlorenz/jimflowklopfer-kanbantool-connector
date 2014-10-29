<?php

namespace system;

use InvalidArgumentException;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

class YamlFileLoader extends FileLoader
{
    /**
     * Loads a resource.
     *
     * @param mixed  $resource The resource
     * @param string $type     The resource type
     *
     * @return array
     */
    public function load($resource, $type = null)
    {
        $path = $this->locator->locate($resource);
        $currentFileContent = $this->loadFile($path);
        $content = array();
        $parameters = array();

        $imports = $this->parseImports($currentFileContent, $path);
        foreach ($imports['settings'] as $config) {
            $content[] = $config;
        }
        if (isset($imports['parameters'])) {
            $parameters = array_merge($parameters, $imports['parameters']);
        }
        unset($currentFileContent['imports']);

        if (isset($currentFileContent['parameters'])) {
            $parameters = array_merge($parameters, $currentFileContent['parameters']);
            unset($currentFileContent['parameters']);
        }
        $content[] = $currentFileContent;

        return array(
            'settings' => $content,
            'parameters' => $parameters,
        );
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return bool    true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'yml' === pathinfo(
            $resource,
            PATHINFO_EXTENSION
        );
    }

    /**
     * Parses all imports
     * Taken from \Symfony\Component\DependencyInjection\Loader\YamlFileLoader
     *
     * @param array  $content
     * @param string $file
     *
     * @return array
     */
    private function parseImports($content, $file)
    {
        if (!isset($content['imports'])) {
            return array(
                'settings' => array(),
                'parameters' => array(),
            );
        }

        $settings = array();
        $parameters = array();
        foreach ($content['imports'] as $import) {
            $this->setCurrentDir(dirname($file));
            $imports = $this->import(
                $import['resource'],
                null,
                isset($import['ignore_errors']) ? (bool) $import['ignore_errors'] : false,
                $file
            );

            foreach ($imports['settings'] as $config) {
                 $settings[] = $config;
            }

            if (isset($imports['parameters'])) {
                $parameters = array_merge($parameters, $imports['parameters']);
                unset($imports['parameters']);
            }
        }

        return compact('settings', 'parameters');
    }


    /**
     * Loads a YAML file.
     * Taken from \Symfony\Component\DependencyInjection\Loader\YamlFileLoader
     * and slightly modified
     *
     * @param string $file
     *
     * @return array The file content
     *
     * @throws InvalidArgumentException when the given file is not a local file or when it does not exist
     */
    protected function loadFile($file)
    {
        if (!stream_is_local($file)) {
            throw new InvalidArgumentException(sprintf('This is not a local file "%s".', $file));
        }

        if (!file_exists($file)) {
            throw new InvalidArgumentException(sprintf('The service file "%s" is not valid.', $file));
        }

        return Yaml::parse(file_get_contents($file));
    }
}
