<?php

class Plugins {
    private $globalPlugins;

    public function __construct($globalPlugins=array()) {
        $this->globalPlugins = $globalPlugins;
    }
    
    /**
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

    private function filterPluginsByName($type, $names) {
        $files = $this->filterPlugins($type);
        foreach ($files as $plugin => $filelist) {
            if (!in_array($plugin, $names)) {
                unset($files[$plugin]);
            }
        }
        return $files;
    }

    public function getPluginsJS($names=null) {
        if ($names) {
            $names = array_merge($this->globalPlugins, $names);
            return $this->filterPluginsByName('js', $names);
        }
        return $this->filterPluginsByName('js', $this->globalPlugins);
    }

    public function getPluginsCSS($names=null) {
        if ($names) {
            $names = array_merge($this->globalPlugins, $names);
            return $this->filterPluginsByName('css', $names);
        }
        return $this->filterPluginsByName('css', $this->globalPlugins);
    }

    public function getPluginsTemplates($names=null) {
        if ($names) {
            $names = array_merge($this->globalPlugins, $names);
            return $this->filterPluginsByName('templates', $names);
        }
        return $this->filterPluginsByName('templates', $this->globalPlugins);
    }

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
