<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2010
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class FakePluginDescriptor
{

    public function __construct($basedir)
    {
        $this->basedir = $basedir;

        $GLOBALS['codendi_cache_dir']     = '/tmp';
        $GLOBALS['sys_incdir']            = $basedir.'/site-content';
        $GLOBALS['sys_pluginsroot']       = $basedir.'/plugins';
        $GLOBALS['sys_custom_incdir']     = false;
        $GLOBALS['sys_custompluginsroot'] = false;

        $GLOBALS['Language'] = new BaseLanguage('en_US', 'en_US');
        $GLOBALS['Language']->lang = 'en_US';
        $GLOBALS['Language']->compileLanguage('en_US');
    }

    public function getDescriptor($pluginName)
    {
        $plugin = $this->findPlugin($pluginName);
        $path   = $this->findDescriptor($plugin);
        return $this->instanciateDescriptor($pluginName, $path);
    }

    protected function instanciateDescriptor($pluginName, $filePath)
    {
        include $filePath;
        $classesAfter = get_declared_classes();
        foreach ($classesAfter as $className) {
            if (strtolower($className) == strtolower($pluginName.'PluginDescriptor')) {
                break;
            }
        }
        if (isset($className)) {
            return new $className();
        }
        throw new Exception("No descriptor class found for plugin $pluginName");
    }

    protected function findPlugin($pluginName)
    {
        $it = new DirectoryIterator($this->basedir.'/plugins/');
        foreach ($it as $plugin) {
            if (strtolower($plugin->getFilename()) == strtolower($pluginName)) {
                return $plugin->getPathname();
            }
        }
        throw new Exception("No plugin found with given name: $pluginName in $this->basedir");
    }

    public function findDescriptor($pluginPath)
    {
        $it = new DirectoryIterator($pluginPath.'/include');
        foreach ($it as $file) {
            if (preg_match('/PluginDescriptor\.class\.php$/', $file->getFilename())) {
                return $file->getPathname();
            }
        }
        throw new Exception("No descriptor found for plugin $pluginPath");
    }

    public function getDescriptorFromFile($pluginName, $path)
    {
        return $this->instanciateDescriptor($pluginName, $path);
    }
}
