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
use Tuleap\Config\GetConfigKeys;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\Event\Dispatchable;
use Tuleap\Plugin\ListeningToEvent;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Plugin\ListeningToEventName;
use Tuleap\Plugin\PluginInstallRequirement;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\Service\AddMissingService;
use Tuleap\Project\Service\PluginWithService;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;

/**
 * Plugin
 */
class Plugin implements PFO_Plugin, \Tuleap\Plugin\IsProjectAllowedToUsePlugin //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
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
     * @var array List of allowed projects
     */
    protected $allowedForProject = [];

    /**
     * @param int|null $id
     */
    public function __construct($id = -1)
    {
        $this->id    = $id;
        $this->hooks = new Map();

        $this->scope = self::SCOPE_SYSTEM;
    }

    public function isAllowed($group_id): bool
    {
        if ($group_id === null) {
            return false;
        }

        if (! isset($this->allowedForProject[$group_id])) {
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
    public function servicesAllowedForProject(array $params): void
    {
        $this->addServiceForProject($params['project'], $params['services']);
    }

    protected function addServiceForProject(Project $project, array &$services): void
    {
        if (! $this->isServiceAllowedForProject($project)) {
            return;
        }
        $services[] = $this->getServiceShortname();
    }

    protected function isServiceAllowedForProject(\Project $project): bool
    {
        if ($this->is_resricted !== null && $this->is_resricted === false) {
            return true;
        }
        if ($this->isAllowed($project->getID())) {
            return true;
        }
        return false;
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
        if (! $this->pluginInfo instanceof \PluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks()
    {
        $this->addHooksFromImplementedInterfaces();
        $this->addHooksDeclaredInAttributes();
        return $this->hooks->getValues();
    }

    private function addHooksFromImplementedInterfaces(): void
    {
        if ($this instanceof PluginWithConfigKeys) {
            $this->addHookIfNotAlreadyListened(GetConfigKeys::NAME);
        }
        if ($this instanceof PluginWithService) {
            $this->addHookIfNotAlreadyListened(Event::SERVICE_CLASSNAMES);
            $this->addHookIfNotAlreadyListened(Event::SERVICES_ALLOWED_FOR_PROJECT);
            $this->addHookIfNotAlreadyListened(Event::SERVICE_IS_USED);
            $this->addHookIfNotAlreadyListened(ProjectServiceBeforeActivation::NAME);
            $this->addHookIfNotAlreadyListened(ServiceDisabledCollector::NAME);
            $this->addHookIfNotAlreadyListened(AddMissingService::NAME);
            $this->addHookIfNotAlreadyListened(ServiceEnableForXmlImportRetriever::NAME);
        }
    }

    private function addHooksDeclaredInAttributes(): void
    {
        $reflected_class = new \ReflectionClass($this);
        foreach ($reflected_class->getMethods() as $reflected_method) {
            $attributes = $reflected_method->getAttributes(ListeningToEvent::class, ReflectionAttribute::IS_INSTANCEOF);
            if (count($attributes) !== 1) {
                continue;
            }
            $listening_to_event = $attributes[0]->newInstance();
            match ($listening_to_event::class) {
                ListeningToEventClass::class => $this->addHooksFromEventClass($reflected_method),
                ListeningToEventName::class => $this->addHooksFromEventName($reflected_method, $listening_to_event),
            };
        }
    }

    private function addHooksFromEventClass(ReflectionMethod $method): void
    {
        $method_parameters = $method->getParameters();
        if (count($method_parameters) !== 1) {
            throw new \LogicException('Callback must declare exactly one parameter (the event). Check ' . $method->getName() . ' usage in ' . static::class);
        }
        $type = $method_parameters[0]->getType();
        if (! $type instanceof ReflectionNamedType) {
            throw new \LogicException('Callback parameter must have a type. Check ' . $method->getName() . ' usage in ' . static::class);
        }
        $type_class = $type->getName();
        if (! class_exists($type_class)) {
            // When callback is hooked on an event sent by a plugin that is not active, skip
            return;
        }
        $event_class   = new \ReflectionClass($type_class);
        $name_constant = $event_class->getConstant(Dispatchable::HOOK_CONST_NAME);
        if ($name_constant === false) {
            $this->listenToHook($event_class->name, $method->name);
        } else {
            if ($this->hooks->containsKey($name_constant)) {
                throw new \LogicException('Hooks can only be listening to once check ' . $name_constant . ' usage in ' . static::class);
            }
            $this->listenToHook($name_constant, $method->name);
        }
    }

    private function addHooksFromEventName(ReflectionMethod $method, ListeningToEventName $listening_to_event): void
    {
        if (! $this->hooks->containsKey($listening_to_event->event_name)) {
            $this->listenToHook($listening_to_event->event_name, $method->name);
        }
    }

    private function addHookIfNotAlreadyListened(string $name): void
    {
        if ($this->hooks->containsKey($name)) {
            return;
        }
        $this->addHook($name);
    }

    /**
     * @deprecated Use ListeningToEventClass attribute instead (adr/0021-attributes-based-events.md)
     */
    public function addHook($hook, $callback = null)
    {
        $this->listenToHook($hook, $callback);
    }

    private function listenToHook(string $hook, ?string $callback = null): void
    {
        if ($this->hooks->containsKey($hook)) {
            throw new RuntimeException('A plugin cannot listen to the same hook several time. Please check ' . $hook);
        }
        $value             = [];
        $value['hook']     = $hook;
        $value['callback'] = $callback ?: $this->deduceCallbackFromHook($hook);
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
        $current_plugin     = static::class;

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
        return ForgeConfig::get('sys_custompluginsroot') . '/' . $this->getName() . '/etc';
    }

    public function getEtcTemplatesPath()
    {
        return ForgeConfig::get('sys_custompluginsroot') . '/' . $this->getName() . '/templates';
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
        if (ForgeConfig::get('sys_pluginspath')) {
            $path = ForgeConfig::get('sys_pluginspath');
        } else {
            $path = "";
        }
        if ($pm->isACustomPlugin($this)) {
            $path = ForgeConfig::get('sys_custompluginspath');
        }
        return $path . '/' . $this->getName();
    }

    public function getThemePath()
    {
        if (ForgeConfig::get('sys_user_theme') === false) {
            return null;
        }

        $pluginName = $this->getName();

        $paths  = [ForgeConfig::get('sys_custompluginspath'), ForgeConfig::get('sys_pluginspath')];
        $roots  = [ForgeConfig::get('sys_custompluginsroot'), ForgeConfig::get('sys_pluginsroot')];
        $dir    = '/' . $pluginName . '/www/themes/';
        $dirs   = [$dir . ForgeConfig::get('sys_user_theme'), $dir . 'default'];
        $dir    = '/' . $pluginName . '/themes/';
        $themes = [$dir . ForgeConfig::get('sys_user_theme'), $dir . 'default'];
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
        if (! $this->filesystem_path) {
            $pm = $this->_getPluginManager();
            if ($pm->isACustomPlugin($this)) {
                $path = ForgeConfig::get('sys_custompluginsroot');
            } else {
                $path = ForgeConfig::get('sys_pluginsroot');
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
     * @return PluginInstallRequirement[]
     */
    public function getInstallRequirements(): array
    {
        return [];
    }

    protected function _getPluginManager(): PluginManager // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $pm = PluginManager::instance();
        return $pm;
    }

    public function postEnable(): void
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
        return [];
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
     */
    public function getServiceShortname(): string
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

    protected function getRouteHandler(string $handler): array
    {
        return [
            'plugin'  => $this->getName(),
            'handler' => $handler,
        ];
    }
}
