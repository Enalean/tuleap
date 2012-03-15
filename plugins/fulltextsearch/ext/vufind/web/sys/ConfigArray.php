<?php
/**
 *
 * Copyright (C) Villanova University 2009.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

/**
 * Support function -- get the file path to one of the ini files specified in the
 * [Extra_Config] section of config.ini.
 *
 * @param   name        The ini's name from the [Extra_Config] section of config.ini
 * @return  string      The file path
 */
function getExtraConfigArrayFile($name)
{
    global $configArray;

    // Load the filename from config.ini, and use the key name as a default
    //     filename if no stored value is found.
    $filename = isset($configArray['Extra_Config'][$name]) ?
        $configArray['Extra_Config'][$name] : $name . '.ini';

    // Return the file path (note that all ini files are in the conf/ directory)
    return 'conf/' . $filename;
}

/**
 * Support function -- get the contents of one of the ini files specified in the
 * [Extra_Config] section of config.ini.
 *
 * @param   name        The ini's name from the [Extra_Config] section of config.ini
 * @return  array       The retrieved configuration settings.
 */
function getExtraConfigArray($name)
{
    static $extraConfigs = array();

    // If the requested settings aren't loaded yet, pull them in:    
    if (!isset($extraConfigs[$name])) {
        // Try to load the .ini file; if loading fails, the file probably doesn't
        // exist, so we can treat it as an empty array.
        $extraConfigs[$name] = @parse_ini_file(getExtraConfigArrayFile($name), true);
        if ($extraConfigs[$name] === false) {
            $extraConfigs[$name] = array();
        }
    }

    return $extraConfigs[$name];
}

/**
 * Support function -- merge the contents of two arrays parsed from ini files.
 *
 * @param   config_ini  The base config array.
 * @param   custom_ini  Overrides to apply on top of the base array.
 * @return  array       The merged results.
 */
function ini_merge($config_ini, $custom_ini)
{
    foreach ($custom_ini as $k => $v) {
        if (is_array($v)) {
            $config_ini[$k] = ini_merge($config_ini[$k], $custom_ini[$k]);
        } else {
            $config_ini[$k] = $v;
        }
    }
    return $config_ini;
}

/**
 * Support function -- load the main configuration options, overriding with
 * custom local settings if applicable.
 *
 * @return  array       The desired config.ini settings in array format.
 */
function readConfig()
{
    $mainArray = parse_ini_file('conf/config.ini', true);
    if (isset($mainArray['Extra_Config']) && 
        isset($mainArray['Extra_Config']['local_overrides'])) {
        $file = trim('conf/' . $mainArray['Extra_Config']['local_overrides']);
        $localOverride = @parse_ini_file($file, true);
        if ($localOverride) {
            return ini_merge($mainArray, $localOverride);
        }
    }
    return $mainArray;
}
?>