<?php
/**
 * Copyright (c) Enalean SAS, 2015 - Present. All rights reserved
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

use Tuleap\DAO\DBTablesDao;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Plugin\InvalidPluginNameException;
use Tuleap\Plugin\RetrieveEnabledPlugins;
use Tuleap\Plugin\UnableToCreatePluginException;

class PluginManager implements RetrieveEnabledPlugins // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * @var ContentInterpretor
     */
    private $commonmark_content_interpretor;

    /** @var PluginFactory */
    private $plugin_factory;

    /** @var SiteCache */
    private $site_cache;

    /** @var PluginManager */
    private static $instance;

    /** @var ForgeUpgradeConfig */
    private $forgeupgrade_config;

    public $pluginHookPriorityManager;

    /**
     * @var array<int, string>
     */
    private $plugins_name = [];

    public function __construct(
        PluginFactory $plugin_factory,
        SiteCache $site_cache,
        ForgeUpgradeConfig $forgeupgrade_config,
        ContentInterpretor $commonmark_content_interpretor,
    ) {
        $this->plugin_factory                 = $plugin_factory;
        $this->site_cache                     = $site_cache;
        $this->forgeupgrade_config            = $forgeupgrade_config;
        $this->commonmark_content_interpretor = $commonmark_content_interpretor;
    }

    public static function instance(): PluginManager
    {
        if (! self::$instance) {
            self::$instance = new self(
                PluginFactory::instance(),
                new SiteCache(),
                new ForgeUpgradeConfig(
                    new \Tuleap\ForgeUpgrade\ForgeUpgrade(
                        \Tuleap\DB\DBFactory::getMainTuleapDBConnection()->getDB()->getPdo(),
                        new \Psr\Log\NullLogger(),
                    )
                ),
                CommonMarkInterpreter::build(Codendi_HTMLPurifier::instance())
            );
        }

        return self::$instance;
    }

    public static function setInstance(PluginManager $plugin_manager)
    {
        self::$instance = $plugin_manager;
    }

    public static function clearInstance()
    {
        self::$instance = null;
    }

    /**
     * @return Plugin[]
     */
    public function getEnabledPlugins(): array
    {
        return $this->plugin_factory->getEnabledPlugins();
    }

    /**
     * @return Plugin[]
     */
    public function getAllPlugins(): array
    {
        return $this->plugin_factory->getAllPlugins();
    }

    public function isPluginEnabled(Plugin $plugin): bool
    {
        return $this->plugin_factory->isPluginEnabled($plugin);
    }

    public function enablePlugin(Plugin $plugin): void
    {
        $this->plugin_factory->enablePlugin($plugin);
        $this->site_cache->invalidatePluginBasedCaches();
    }

    public function disablePlugin(Plugin $plugin): void
    {
        $this->plugin_factory->disablePlugin($plugin);
        $this->site_cache->invalidatePluginBasedCaches();
    }

    /**
     * @throws InvalidPluginNameException
     * @throws UnableToCreatePluginException
     */
    public function installAndEnable(string $name): Plugin
    {
        $plugin = $this->plugin_factory->getPluginByName($name);
        if (! $plugin) {
            $this->getPluginDuringInstall($name); // ensures the plugin actually exists
            $plugin = $this->installPlugin($name);
            if (! $plugin) {
                throw new Exception("Unable to install plugin $name");
            }
        }
        $this->enablePluginAndItsDependencies($plugin);

        return $plugin;
    }

    public function enablePluginAndItsDependencies(Plugin $plugin): void
    {
        $this->recursivelyEnablePlugin($plugin);
        $this->site_cache->invalidatePluginBasedCaches();
    }

    /**
     * @throws InvalidPluginNameException
     */
    private function recursivelyEnablePlugin(Plugin $plugin): void
    {
        foreach ($plugin->getDependencies() as $dependency_name) {
            $dependency = $this->getPluginByName($dependency_name);
            if (! $dependency) {
                throw new InvalidPluginNameException($dependency_name);
            }
            $this->recursivelyEnablePlugin($dependency);
        }
        $this->plugin_factory->enablePlugin($plugin);
    }

    /**
     * @throws InvalidPluginNameException
     * @throws UnableToCreatePluginException
     */
    public function installPlugin(string $name): ?Plugin
    {
        if (! $this->isNameValid($name)) {
            throw new InvalidPluginNameException($name);
        }
        $name = $this->getValidatedName($name);
        if ($this->plugin_factory->isPluginInstalled($name)) {
            return $this->plugin_factory->getPluginByName($name);
        }

        $this->installPluginDependencies($name);

        $this->executeSqlStatements('install', $name);
        $plugin = $this->plugin_factory->createPlugin($name);
        if (! $plugin) {
            throw new UnableToCreatePluginException($name);
        }

        $this->createEtc($name);
        $this->configureForgeUpgrade($name);

        return $plugin;
    }

    /**
     * @throws InvalidPluginNameException
     * @throws UnableToCreatePluginException
     */
    private function installPluginDependencies(string $name): void
    {
        $plugin = $this->getPluginDuringInstall($name);
        foreach ($plugin->getDependencies() as $dependency_name) {
            if (! $this->plugin_factory->isPluginInstalled($dependency_name)) {
                $this->installPlugin($dependency_name);
            }
        }
    }

    public function uninstallPlugin(Plugin $plugin)
    {
        $name = $this->plugin_factory->getNameForPlugin($plugin);
        $this->executeSqlStatements('uninstall', $name);
        $this->site_cache->invalidatePluginBasedCaches();
        return $this->plugin_factory->removePlugin($plugin);
    }

    public function getInstallReadme($name)
    {
        foreach ($this->plugin_factory->getAllPossiblePluginsDir() as $dir) {
            $path = $dir . '/' . $name;
            if (file_exists($path . '/README.mkd') || file_exists($path . '/README.md') || file_exists($path . '/README.txt') || file_exists($path . '/README')) {
                return $path . '/README';
            }
        }
        return false;
    }

    /**
     * Format the readme file of a plugin
     *
     * Use markdown formatter if README.mkd exists
     * Otherwise assume text/plain and put it in <pre> tags
     * If README file doesn't exist, return empty string.
     *
     * @return string html
     */
    public function fetchFormattedReadme($file)
    {
        if (is_file("$file.mkd")) {
            $content = file_get_contents("$file.mkd");

            return $this->commonmark_content_interpretor->getInterpretedContent($content);
        }

        if (is_file("$file.txt")) {
            return $this->getEscapedReadme(file_get_contents("$file.txt"));
        }

        if (is_file($file)) {
            return $this->getEscapedReadme(file_get_contents($file));
        }

        return '';
    }

    private function getEscapedReadme($content)
    {
        return '<pre>' . Codendi_HTMLPurifier::instance()->purify($content) . '</pre>';
    }

    /**
     * Initialize ForgeUpgrade configuration for given plugin
     *
     * Record existing migration scripts as 'skipped'
     * because the 'install.sql' script is up-to-date with latest DB modif.
     */
    private function configureForgeUpgrade(string $name): void
    {
        $plugin_path = ForgeConfig::get('sys_pluginsroot') . $name . '/db';
        if (! is_dir($plugin_path)) {
            return;
        }
        $this->forgeupgrade_config->recordOnlyPath($plugin_path);
    }

    private function createEtc(string $name): void
    {
        if (! is_dir(ForgeConfig::get('sys_custompluginsroot') . '/' . $name)) {
            mkdir(ForgeConfig::get('sys_custompluginsroot') . '/' . $name, 0700);
        }
        if (is_dir(ForgeConfig::get('sys_pluginsroot') . '/' . $name . '/etc')) {
            if (! is_dir(ForgeConfig::get('sys_custompluginsroot') . '/' . $name . '/etc')) {
                mkdir(ForgeConfig::get('sys_custompluginsroot') . '/' . $name . '/etc', 0700);
            }
            $etcs = glob(ForgeConfig::get('sys_pluginsroot') . '/' . $name . '/etc/*');
            foreach ($etcs as $etc) {
                if (is_dir($etc)) {
                    $this->copyDirectory($etc, ForgeConfig::get('sys_custompluginsroot') . '/' . $name . '/etc/' . basename($etc));
                } else {
                    copy($etc, ForgeConfig::get('sys_custompluginsroot') . '/' . $name . '/etc/' . basename($etc));
                }
            }
            $incdists = glob(ForgeConfig::get('sys_custompluginsroot') . '/' . $name . '/etc/*.dist');
            foreach ($incdists as $incdist) {
                rename($incdist, ForgeConfig::get('sys_custompluginsroot') . '/' . $name . '/etc/' . basename($incdist, '.dist'));
            }
        }
    }

    private function executeSqlStatements(string $file, string $name): void
    {
        $path_to_file = '/' . $name . '/db/' . $file . '.sql';

        foreach ($this->plugin_factory->getAllPossiblePluginsDir() as $dir) {
            $sql_filename = $dir . $path_to_file;
            if (file_exists($sql_filename)) {
                $dbtables = new DBTablesDao();
                $dbtables->updateFromFile($sql_filename);
            }
        }
    }

    /**
     * @return Plugin[]
     */
    public function getNotYetInstalledPlugins(): array
    {
        return $this->plugin_factory->getNotYetInstalledPlugins();
    }

    public function isNameValid($name)
    {
        return (0 === preg_match('/[^a-zA-Z0-9_-]/', $name));
    }

    /**
     * @psalm-taint-escape file
     * @psalm-taint-escape text
     */
    private function getValidatedName(string $name): string
    {
        if (! $this->isNameValid($name)) {
            throw new RuntimeException('$name does not respect the expected criteria, got ' . $name);
        }
        return $name;
    }

    public function getPluginByName(string $name): ?Plugin
    {
        return $this->plugin_factory->getPluginByName($name);
    }

    public function getEnabledPluginByName(string $name): ?Plugin
    {
        $plugin = $this->getPluginByName($name);
        if ($plugin && $this->isPluginEnabled($plugin)) {
            return $plugin;
        }

        return null;
    }

    public function getPluginById(int $id): ?Plugin
    {
        return $this->plugin_factory->getPluginById($id);
    }

    public function isACustomPlugin(Plugin $plugin): bool
    {
        return $this->plugin_factory->isACustomPlugin($plugin);
    }

    public function getNameForPlugin(Plugin $plugin): string
    {
        if (! isset($this->plugins_name[$plugin->getId()])) {
            $this->plugins_name[$plugin->getId()] = $this->plugin_factory->getNameForPlugin($plugin);
        }

        return $this->plugins_name[$plugin->getId()];
    }

    /**
     * @param list<int>|int $projectIds
     */
    private function updateProjectForPlugin(string $action, Plugin $plugin, $projectIds): void
    {
        $success     = true;
        $successOnce = false;

        if (is_array($projectIds)) {
            foreach ($projectIds as $prjId) {
                switch ($action) {
                    case 'add':
                        $success = $success && $this->plugin_factory->addProjectForPlugin($plugin, $prjId);
                        break;
                    case 'del':
                        $success = $success && $this->plugin_factory->delProjectForPlugin($plugin, $prjId);
                        break;
                }

                if ($success === true) {
                    $successOnce = true;
                }
            }
        } elseif (is_numeric($projectIds)) {
            switch ($action) {
                case 'add':
                    $success = $success && $this->plugin_factory->addProjectForPlugin($plugin, $projectIds);
                    break;
                case 'del':
                    $success = $success && $this->plugin_factory->delProjectForPlugin($plugin, $projectIds);
                    break;
            }
            $successOnce = $success;
        }

        if ($successOnce && ($action == 'add')) {
            $this->plugin_factory->restrictProjectPluginUse($plugin, true);
        }
    }

    /**
     * @param list<int>|int $projectIds
     */
    public function addProjectForPlugin(Plugin $plugin, $projectIds): void
    {
        $this->updateProjectForPlugin('add', $plugin, $projectIds);
    }

    /**
     * @param list<int>|int $projectIds
     */
    public function delProjectForPlugin(Plugin $plugin, $projectIds)
    {
        $this->updateProjectForPlugin('del', $plugin, $projectIds);
    }

    public function isProjectPluginRestricted($plugin)
    {
        return $this->plugin_factory->isProjectPluginRestricted($plugin);
    }

    public function updateProjectPluginRestriction($plugin, $restricted)
    {
        $this->plugin_factory->restrictProjectPluginUse($plugin, $restricted);
        if ($restricted == false) {
            $this->plugin_factory->truncateProjectPlugin($plugin);
        }
    }

    public function isPluginAllowedForProject($plugin, $projectId)
    {
        if ($this->isProjectPluginRestricted($plugin)) {
            return $this->plugin_factory->isPluginAllowedForProject($plugin, $projectId);
        } else {
            return true;
        }
    }

    /**
     * This method instantiate a plugin that should not be used outside
     * of installation use case. It bypass all caches and do not check availability
     * of the plugin.
     *
     * @throws InvalidPluginNameException
     */
    public function getPluginDuringInstall(string $name): Plugin
    {
        $plugin = $this->plugin_factory->instantiatePlugin(0, $name);
        if (! $plugin) {
            throw new InvalidPluginNameException($name);
        }
        return $plugin;
    }

    private function copyDirectory($source, $destination)
    {
        if (! is_dir($destination)) {
            if (! mkdir($destination)) {
                return false;
            }
        }

        $iterator = new DirectoryIterator($source);
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                copy($file->getRealPath(), "$destination/" . $file->getFilename());
            } elseif (! $file->isDot() && $file->isDir()) {
                $this->copyDirectory($file->getRealPath(), "$destination/$file");
            }
        }
    }
}
