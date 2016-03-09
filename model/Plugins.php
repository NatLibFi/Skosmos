<?php

class Plugins {
    private $globalPlugins;

    public function __construct($globalPlugins=array()) {
        $this->globalPlugins = $globalPlugins;
    }
    
    /**
     * Returns the plugin configurations found from plugin folders inside the plugins folder
     * @return array
     */
    private function getPlugins() 
    {
        $plugins = array();
        $pluginconfs = glob('plugins/*/plugin.json');
        foreach ($pluginconfs as $path) {
            $folder = explode('/', $path)[1];
            if (file_exists($path)) {
                $plugins[$folder] = json_decode(file_get_contents($path), true);
            }
        }
        return $plugins;
    }

    /**
     * Returns the plugin configurations found from plugin folders 
     * inside the plugins folder filtered by filetype.
     * @param string $type filetype e.g. 'css', 'js' or 'template'
     * @return array
     */
    private function filterPlugins($type) {
        $plugins = $this->getPlugins();
        $ret = array();
        if (!empty($plugins)) {
            foreach ($plugins as $name => $files) {
                if (isset($files[$type])) {
                    $ret[$name] = array();
                    foreach ($files[$type] as $file) {
                        array_push($ret[$name], 'plugins/' . $name . '/' . $file);
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
    private function filterPluginsByName($type, $names) {
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
    public function getPluginsJS($names=null) {
        if ($names) {
            $names = array_merge($this->globalPlugins, $names);
            return $this->filterPluginsByName('js', $names);
        }
        return $this->filterPluginsByName('js', $this->globalPlugins);
    }

    /**
     * Returns an array of css filepaths
     * @param array $names the plugin name strings (foldernames) in an array 
     * @return array
     */
    public function getPluginsCSS($names=null) {
        if ($names) {
            $names = array_merge($this->globalPlugins, $names);
            return $this->filterPluginsByName('css', $names);
        }
        return $this->filterPluginsByName('css', $this->globalPlugins);
    }

    /**
     * Returns an array of template filepaths
     * @param array $names the plugin name strings (foldernames) in an array 
     * @return array
     */
    public function getPluginsTemplates($names=null) {
        if ($names) {
            $names = array_merge($this->globalPlugins, $names);
            return $this->filterPluginsByName('templates', $names);
        }
        return $this->filterPluginsByName('templates', $this->globalPlugins);
    }

    /**
     * Returns an array of template files contents as strings
     * @param array $names the plugin name strings (foldernames) in an array 
     * @return array
     */
    public function getTemplates($names=null) {
        $templateStrings = array();
        $plugins = $this->getPluginsTemplates($names);
        foreach ($plugins as $folder => $templates) {
            foreach ($templates as $path) {
                if (file_exists($path)) {
                    $filename = explode('/', $path)[sizeof(explode('/', $path))-1];
                    $id = $folder . '-' . substr($filename, 0 , (strrpos($filename, ".")));
                    $templateStrings[$id] = file_get_contents($path);
                }
            }
        }
        return $templateStrings;
    }
}
