<?php

class PluginRegister
{
    private $requestedPlugins;

    public function __construct($requestedPlugins=array())
    {
        $this->requestedPlugins = $requestedPlugins;
        $this->pluginOrder = array();
        foreach ($this->requestedPlugins as $index => $value) {
            $this->pluginOrder[$value] = $index;
        }
    }

    /**
     * Returns the plugin configurations found from plugin folders inside the plugins folder
     * @return array
     */
    protected function getPlugins()
    {
        $plugins = array();
        $pluginconfs = glob('plugins/*/plugin.json');
        foreach ($pluginconfs as $path) {
            $folder = explode('/', $path);
            if (file_exists($path)) {
                $plugins[$folder[1]] = json_decode(file_get_contents($path), true);
            }
        }
        return $plugins;
    }

    /**
     * Sort plugins by order defined in class constructor
     * @return array
     */
    private function sortPlugins($plugins)
    {
        $sortedPlugins = array();
        $sortedPlugins = array_replace($this->pluginOrder, $plugins);
        return $sortedPlugins;
    }

    /**
     * Returns the plugin configurations found from plugin folders
     * inside the plugins folder filtered by filetype.
     * @param string $type filetype e.g. 'css', 'js' or 'template'
     * @param boolean $raw interpret $type values as raw text instead of files
     * @return array
     */
    private function filterPlugins($type, $raw=false)
    {
        $plugins = $this->getPlugins();
        $plugins = $this->sortPlugins($plugins);
        $ret = array();
        if (!empty($plugins)) {
            foreach ($plugins as $name => $files) {
                if (isset($files[$type])) {
                    $ret[$name] = array();
                    if ($raw) {
                        $ret[$name] = $files[$type];
                    } else {
                        foreach ($files[$type] as $file) {
                            array_push($ret[$name], 'plugins/' . $name . '/' . $file);
                        }
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * Returns the plugin configurations found from plugin folders
     * inside the plugins folder filtered by plugin name (the folder name).
     * @param string $type filetype e.g. 'css', 'js' or 'template'
     * @param array $names the plugin name strings (foldernames) in an array
     * @return array
     */
    private function filterPluginsByName($type, $names)
    {
        $files = $this->filterPlugins($type);
        foreach ($files as $plugin => $filelist) {
            if (!in_array($plugin, $names)) {
                unset($files[$plugin]);
            }
        }
        return $files;
    }

    /**
     * Returns an array of javascript filepaths
     * @param array $names the plugin name strings (foldernames) in an array
     * @return array
     */
    public function getPluginsJS($names=null)
    {
        if ($names) {
            $names = array_merge($this->requestedPlugins, $names);
            return $this->filterPluginsByName('js', $names);
        }
        return $this->filterPluginsByName('js', $this->requestedPlugins);
    }

    /**
     * Returns an array of css filepaths
     * @param array $names the plugin name strings (foldernames) in an array
     * @return array
     */
    public function getPluginsCSS($names=null)
    {
        if ($names) {
            $names = array_merge($this->requestedPlugins, $names);
            return $this->filterPluginsByName('css', $names);
        }
        return $this->filterPluginsByName('css', $this->requestedPlugins);
    }

    /**
     * Returns an array of template filepaths
     * @param array $names the plugin name strings (foldernames) in an array
     * @return array
     */
    public function getPluginsTemplates($names=null)
    {
        if ($names) {
            $names = array_merge($this->requestedPlugins, $names);
            return $this->filterPluginsByName('templates', $names);
        }
        return $this->filterPluginsByName('templates', $this->requestedPlugins);
    }

    /**
     * Returns an array of template files contents as strings
     * @param array $names the plugin name strings (foldernames) in an array
     * @return array
     */
    public function getTemplates($names=null)
    {
        $templateStrings = array();
        $plugins = $this->getPluginsTemplates($names);
        foreach ($plugins as $folder => $templates) {
            foreach ($templates as $path) {
                if (file_exists($path)) {
                    $filename = explode('/', $path);
                    $filename = $filename[sizeof($filename)-1];
                    $id = $folder . '-' . substr($filename, 0, (strrpos($filename, ".")));
                    $templateStrings[$id] = file_get_contents($path);
                }
            }
        }
        return $templateStrings;
    }

    /**
     * Returns an array of plugin callback function names
     * @param array $names the plugin name strings (foldernames) in an array
     * @return array
     */
    public function getPluginCallbacks($names=null)
    {
        if ($names) {
            $names = array_merge($this->requestedPlugins, $names);
            return $this->filterPluginsByName('callback', $names);
        }
        return $this->filterPluginsByName('callback', $this->requestedPlugins);
    }

    /**
     * Returns a sorted array of javascript function names to call when loading pages
     * in order configured with skosmos:vocabularyPlugins
     * @return array
     */
    public function getCallbacks()
    {
        $ret = array();
        $sortedCallbacks = array();
        $plugins = $this->getPluginCallbacks($this->requestedPlugins);
        foreach ($plugins as $callbacks) {
            foreach ($callbacks as $callback) {
                $split = explode('/', $callback);
                $sortedCallbacks[$split[1]] = $split[2];
            }
        }
        $sortedCallbacks = array_replace($this->pluginOrder, $sortedCallbacks);
        foreach ($sortedCallbacks as $callback) {
            $ret[] = $callback;
        }
        return $ret;
    }

    /**
     * Returns an array that is flattened from its possibly multidimensional form
     * copied from https://stackoverflow.com/a/1320156
     * @param mixed[] $array Flattens this input array
     * @return array Flattened input
     */
    protected function flatten($array)
    {
        $return = array();
        array_walk_recursive($array, function ($a) use (&$return) { $return[] = $a; });
        return $return;
    }

    /**
     * Returns a flattened array containing the external properties we are interested in saving
     * @return string[]
     */
    public function getExtProperties()
    {
        return array_unique($this->flatten($this->filterPlugins('ext-properties', true)));
    }
}
