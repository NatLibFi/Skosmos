<?php

class Plugins {
    
    /**
     * @return array
     */
    private function getPlugins() 
    {
        $plugins = array();
        $pluginconfs = glob('plugins/*/plugin.json');
        foreach ($pluginconfs as $path) {
            if (file_exists($path)) {
                $plugins = array_merge($plugins, json_decode(file_get_contents($path), true));
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
                    $ret[$name] = $files[$type];
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
            return $this->filterPluginsByName('js', $names);
        }
        return $this->filterPlugins('js');
    }

    public function getPluginsCSS($names=null) {
        if ($names) {
            return $this->filterPluginsByName('css', $names);
        }
        return $this->filterPlugins('css');
    }

    public function getPluginsHTML($names=null) {
        if ($names) {
            return $this->filterPluginsByName('html', $names);
        }
        return $this->filterPlugins('html');
    }
}
