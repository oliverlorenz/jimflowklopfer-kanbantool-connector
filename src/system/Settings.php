<?php

namespace system;

use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;

class Settings implements \ArrayAccess
{
    /* @var string $configPaths */
    private $configPaths;
    /* @var string $filename */
    private $filename;
    /* @var mixed[] $configSettings */
    private $settings;
    /* @var NodeInterface $configTree */
    private $configTree;

    /**
     * @param string[]      $configPaths a list of path names where to look for config files
     * @param string        $filename    the name of the config file to load
     * @param NodeInterface $configTree  a definition of the config files' structure
     */
    public function __construct($configPaths, $filename, $configTree)
    {
        $this->configPaths = $configPaths;
        $this->filename = $filename;
        $this->configTree = $configTree;
    }

    /**
     * @return string
     */
    public function getConfigPaths()
    {
        return $this->configPaths;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return in_array($offset, array('app', 'merchant')) || isset($this->settings[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        if (isset($this->settings[$offset])) {
            return $this->settings[$offset];
        }

        return null;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     *
     * @throws \BadMethodCallException
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException('Impossible set config settings at runtime.');
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     *
     * @throws \BadMethodCallException
     * @return void
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('Impossible remove config settings at runtime.');
    }

    public function isLoaded()
    {
        return $this->settings !== null;
    }

    public function load()
    {
        $locator = new FileLocator($this->configPaths);
        $settingsFile = $locator->locate($this->filename . '.yml', null, true);
        $loader = new YamlFileLoader($locator);
        $processor = new Processor();
        $loadedSettings = $loader->load($settingsFile);
        $this->settings = $this->replacePlaceholders(
            $processor->process($this->configTree, $loadedSettings['settings']),
            $loadedSettings['parameters']
        );
    }

    protected function replacePlaceholders($settings, $parameters)
    {
        if (count($parameters) === 0) {
            return $settings;
        }

        $search = array();
        $replace = array();
        foreach ($parameters as $key => $value) {
            $search[] = "%$key%";
            $replace[] = $value;
        }

        return json_decode(str_replace($search, $replace, json_encode($settings)), true);
    }
}
