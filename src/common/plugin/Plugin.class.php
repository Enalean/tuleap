<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

use Psr\Log\LoggerInterface;
use Tuleap\Layout\IncludeAssets;

/**
 * Plugin
 */
class Plugin implements PFO_Plugin
{
    /** @var LoggerInterface */
    private $backend_logger;

    public $id;
    public $pluginInfo;
    /** @var Map */
    public $hooks;
    /**
     * @var int
     */
    private $scope;

    /** @var bool */
    private $is_custom = false;

    /** @var bool */
    private $is_resricted;

    /** @var string */
    private $name;

    protected $filesystem_path = '';

    public const SCOPE_SYSTEM  = 0;
    public const SCOPE_PROJECT = 1;

    /**
     * @var bool True if the plugin should be disabled for all projects on installation
     *
     * Usefull only for plugins with scope == SCOPE_PROJECT
     */
    public $isRestrictedByDefault = false;

    /**
     * @var array List of allowed projects
     */
    protected $allowedForProject = array();

    /**
     * @param int|null $id
     */
    public function __construct($id = -1)
    {
        $this->id            = $id;
        $this->hooks         = new Map();

        $this->scope = self::SCOPE_SYSTEM;
    }

    /**
     * Callback called when the plugin is loaded
     *
     * @return void
     */
    public function loaded()
    {
    }

    public function isAllowed($group_id)
    {
        if (!isset($this->allowedForProject[$group_id])) {
            $this->allowedForProject[$group_id] = PluginManager::instance()->isPluginAllowedForProject($this, $group_id);
        }
        return $this->allowedForProject[$group_id];
    }

    /**
     * @return bool
     */
    public function isRestricted()
    {
        return (bool) PluginManager::instance()->isProjectPluginRestricted($this);
    }

    /**
     * Hook call for @see Event::SERVICES_ALLOWED_FOR_PROJECT
     *
     * You just need to add $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT)
     * to your plugin to automatically manage presence of service in projects
     */
    public function services_allowed_for_project(array $params)
    {
        $this->addServiceForProject($params['project'], $params['services']);
    }

    protected function addServiceForProject(Project $project, array &$services)
    {
        if ($this->is_resricted !== null && $this->is_resricted === false) {
            $services[] = $this->getServiceShortname();
        } elseif ($this->isAllowed($project->getID())) {
            $services[] = $this->getServiceShortname();
        }
    }

    public function setIsRestricted($is_restricted)
    {
        $this->is_resricted = $is_restricted;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPluginInfo()
    {
        if (!is_a($this->pluginInfo, 'PluginInfo')) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks()
    {
        return $this->hooks->getValues();
    }

    /**
     * Call when the plugin is uninstalled
     */
    public function uninstall()
    {
    }

    public function addHook($hook, $callback = null, $recallHook = false)
    {
        if ($this->hooks->containsKey($hook)) {
            throw new RuntimeException('A plugin cannot listen to the same hook several time. Please check ' . $hook);
        }
        $value = array();
        $value['hook']       = $hook;
        $value['callback']   = $callback ?: $this->deduceCallbackFromHook($hook);
        $value['recallHook'] = $recallHook;
        $this->hooks->put($hook, $value);
    }

    private function deduceCallbackFromHook($hook)
    {
        $hook_in_camel_case = lcfirst(
            str_replace(
                ' ',
                '',
                ucwords(
                    str_replace('_', ' ', $hook)
                )
            )
        );
        $current_plugin = static::class;

        if (method_exists($this, $hook_in_camel_case)) {
            if ($hook_in_camel_case !== $hook && method_exists($this, $hook)) {
                throw new RuntimeException("$current_plugin should not implement both $hook() and $hook_in_camel_case()");
            }

            return $hook_in_camel_case;
        }

        if (! method_exists($this, $hook)) {
            throw new RuntimeException("$current_plugin must implement $hook_in_camel_case()");
        }

        return $hook;
    }

    public function getScope(): int
    {
        return $this->scope;
    }

    /**
     * @psalm-param self::SCOPE_* $s
     */
    public function setScope(int $s): void
    {
        $this->scope = $s;
    }

    public function getPluginEtcRoot()
    {
        return $GLOBALS['sys_custompluginsroot'] . '/' . $this->getName() . '/etc';
    }

    public function getEtcTemplatesPath()
    {
        return $GLOBALS['sys_custompluginsroot'] . '/' . $this->getName() . '/templates';
    }

    public function _getPluginPath()
    {
        $trace = debug_backtrace();
        trigger_error("Plugin->_getPluginPath() is deprecated. Please use Plugin->getPluginPath() instead in " . $trace[0]['file'] . " at line " . $trace[0]['line'], E_USER_WARNING);
        return $this->getPluginPath();
    }

    /**
     * Return plugin's URL path from the server root
     *
     * Example: /plugins/docman
     *
     * @return String
     */
    public function getPluginPath()
    {
        $pm = $this->_getPluginManager();
        if (isset($GLOBALS['sys_pluginspath'])) {
            $path = $GLOBALS['sys_pluginspath'];
        } else {
            $path = "";
        }
        if ($pm->pluginIsCustom($this)) {
            $path = $GLOBALS['sys_custompluginspath'];
        }
        return $path . '/' . $this->getName();
    }

    public function _getThemePath()
    {
        $trace = debug_backtrace();
        trigger_error("Plugin->_getThemePath() is deprecated. Please use Plugin->getThemePath() instead in " . $trace[0]['file'] . " at line " . $trace[0]['line'], E_USER_WARNING);
        return $this->getThemePath();
    }

    public function getThemePath()
    {
        if (!isset($GLOBALS['sys_user_theme'])) {
            return null;
        }

        $pluginName = $this->getName();

        $paths  = array($GLOBALS['sys_custompluginspath'], $GLOBALS['sys_pluginspath']);
        $roots  = array($GLOBALS['sys_custompluginsroot'], $GLOBALS['sys_pluginsroot']);
        $dir    = '/' . $pluginName . '/www/themes/';
        $dirs   = array($dir . $GLOBALS['sys_user_theme'], $dir . 'default');
        $dir    = '/' . $pluginName . '/themes/';
        $themes = array($dir . $GLOBALS['sys_user_theme'], $dir . 'default');
        foreach ($dirs as $kd => $dir) {
            foreach ($roots as $kr => $root) {
                if (is_dir($root . $dir) && $paths[$kr] . $themes[$kd]) {
                    return $paths[$kr] . $themes[$kd];
                }
            }
        }
        return false;
    }

    /**
     * Returns plugin's path on the server file system
     *
     * Example: /usr/share/codendi/plugins/docman
     *
     * @return String
     */
    public function getFilesystemPath()
    {
        if (!$this->filesystem_path) {
            $pm = $this->_getPluginManager();
            if ($pm->pluginIsCustom($this)) {
                $path = $GLOBALS['sys_custompluginsroot'];
            } else {
                $path = $GLOBALS['sys_pluginsroot'];
            }
            if ($path[strlen($path) - 1] != '/') {
                $path .= '/';
            }
            $this->filesystem_path = $path . $this->getName();
        }
        return $this->filesystem_path;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string the short name of the plugin (docman, tracker, …)
     */
    public function getName()
    {
        if (! isset($this->name)) {
            $this->name = $this->_getPluginManager()->getNameForPlugin($this);
        }
        return $this->name;
    }

    /**
     * Wrapper for PluginManager
     *
     * @return PluginManager
     */
    protected function _getPluginManager()
    {
        $pm = PluginManager::instance();
        return $pm;
    }

    /**
     * Function called before turning a plugin to available status
     * Allow you to check required things (DB connection, etc...)
     * and to forbid plugin to be made available if requirements are not met.
     *
     * @return bool true if the plugin can be made available, false if not
     */
    public function canBeMadeAvailable()
    {
        return true;
    }

    /**
     * Function called when a plugin is set as available or unavailable
     *
     * @param bool $available true if the plugin is available, false if unavailable
     */
    public function setAvailable($available)
    {
    }

    /**
     * Function executed after plugin installation
     */
    public function postInstall()
    {
    }

    public function getAdministrationOptions()
    {
        return '';
    }

    /**
     * Returns the content of the README file associated to the plugin
     *
     * @return String
     */
    public function getReadme()
    {
        return $this->getFilesystemPath() . '/README';
    }

    /**
     * @return array of strings (identifier of plugins this one depends on)
     */
    public function getDependencies()
    {
        return array();
    }

    public function setIsCustom($is_custom)
    {
        $this->is_custom = $is_custom;
    }

    public function isCustom()
    {
        return $this->is_custom;
    }

    /**
     * Return the name of the service that is managed by this plugin
     *
     * @return string
     */
    public function getServiceShortname()
    {
        return '';
    }

    protected function getBackendLogger(): LoggerInterface
    {
        if (! $this->backend_logger) {
            $this->backend_logger = BackendLogger::getDefaultLogger();
        }
        return $this->backend_logger;
    }

    protected function getMinifiedAssetHTML()
    {
        $include_assets = new IncludeAssets(
            $this->getFilesystemPath() . '/www/assets',
            $this->getPluginPath() . '/assets'
        );
        return $include_assets->getHTMLSnippet($this->getName() . '.js');
    }

    public function currentRequestIsForPlugin()
    {
        return strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0;
    }

    protected function removeOrphanWidgets(array $names)
    {
        $dao = new \Tuleap\Dashboard\Widget\DashboardWidgetDao(
            new \Tuleap\Widget\WidgetFactory(
                UserManager::instance(),
                new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
                EventManager::instance()
            )
        );
        $dao->removeOrphanWidgetsByNames($names);
    }

    protected function getRouteHandler(string $handler) : array
    {
        return [
            'plugin'  => $this->getName(),
            'handler' => $handler,
        ];
    }
}
