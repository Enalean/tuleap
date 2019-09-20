<?php
/**
 * Copyright (c) Enalean SAS 2011 - Present All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * PluginInfo
 */
class PluginInfo
{

    var $plugin;
    var $pluginDescriptor;
    var $propertyDescriptors;

    public function __construct($plugin)
    {
        $this->plugin              = $plugin;
        $this->propertyDescriptors = new Map();
    }

    function setPluginDescriptor($descriptor)
    {
        $this->pluginDescriptor = $descriptor;
    }

    /** @return PluginDescriptor */
    public function getPluginDescriptor()
    {
        if (!is_a($this->pluginDescriptor, 'PluginDescriptor')) {
            $this->setPluginDescriptor(new PluginDescriptor('', '', ''));
        }
        return $this->pluginDescriptor;
    }
    function getPropertyDescriptors()
    {
        return $this->propertyDescriptors;
    }

    function _addPropertyDescriptor($descriptor)
    {
        $name = $descriptor->getName();
        $this->propertyDescriptors->put($name, $descriptor);
    }
    function _removePropertyDescriptor($descriptor)
    {
        $name = $descriptor->getName();
        return $this->propertyDescriptors->remove($name, $descriptor);
    }

    function loadProperties()
    {
    }

    function saveProperties()
    {
    }

    function getPropertyDescriptorForName($name)
    {
        $n =  $name;
        return $this->propertyDescriptors->get($n);
    }
}
