<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * File based plugin options management
 */
class PluginFileInfo extends PluginInfo // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /** @var string */
    private $conf_path;

    /** @var string */
    private $default_conf_path;

    public function __construct(Plugin $plugin, string $incname)
    {
        parent::__construct($plugin);

        $this->conf_path         = $plugin->getPluginEtcRoot()  . '/' . $incname . '.inc';
        $this->default_conf_path = $this->getDefaultConfPath($plugin, $incname);
        $this->loadProperties();
    }

    /**
     * Override this in order to load default variables (in .dist files). Else only /etc will be loaded.
     *
     * This is left intentionnaly protected so that we can deploy this feature progressively. When all concerned plugins
     * will use it this method will not be required anymore and should be inlined.
     */
    protected function getDefaultConfPath(Plugin $plugin, $incname)
    {
        return null;
    }

    /**
     * Load properties from the configuration file
     */
    #[\Override]
    public function loadProperties(): void
    {
        if (is_file($this->default_conf_path)) {
            \ForgeConfig::loadFromFile($this->default_conf_path);
        }
        if (is_file($this->conf_path)) {
            \ForgeConfig::loadFromFile($this->conf_path);
        }
    }

    /**
     * Return the property value for given property name
     */
    public function getPropertyValueForName(string $name): mixed
    {
        return \ForgeConfig::get($name);
    }

    /**
     * Alias for getPropertyValueForName
     */
    public function getPropVal($name)
    {
        return $this->getPropertyValueForName($name);
    }
}
