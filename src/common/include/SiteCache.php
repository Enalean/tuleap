<?php
/**
 * Copyright (c) Enalean, 2015-Present. All rights reserved
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

use Tuleap\Plugin\PluginLoader;

class SiteCache
{

    private $logger;

    public function __construct(?\Psr\Log\LoggerInterface $logger = null)
    {
        $this->logger = $logger ? $logger : BackendLogger::getDefaultLogger();
    }

    public function invalidatePluginBasedCaches()
    {
        $this->invalidateTemplateEngine();
        $this->invalidateRestler();
        $this->invalidateFrontRouter();
        $this->invalidateLanguage();
        $this->invalidateWSDL();
        $this->invalidatePlugin();
    }

    private function invalidateTemplateEngine()
    {
        $this->logger->info('Invalidate template engine cache');
        $template_engine_cache = new \Tuleap\Templating\TemplateCache();
        $template_engine_cache->invalidate();
    }

    private function invalidateRestler()
    {
        $this->logger->info('Invalidate Restler cache');
        $restler = new RestlerCache();
        $restler->invalidateCache();
    }

    private function invalidateFrontRouter()
    {
        $this->logger->info('Invalidate FrontRouter cache');
        \Tuleap\Request\FrontRouter::invalidateCache();
    }

    private function invalidateLanguage()
    {
        $this->logger->info('Invalidate language cache');
        $GLOBALS['Language']->invalidateCache();
    }

    private function invalidateWSDL()
    {
        $this->logger->info('Invalidate WSDL');
        foreach (glob(ForgeConfig::get('codendi_cache_dir') . '/php/wsdlcache/wsdl*') as $file) {
            unlink($file);
        }
    }

    private function invalidatePlugin()
    {
        $this->logger->info('Invalidate Plugin hooks');
        PluginLoader::invalidateCache();
    }

    public function restoreCacheDirectories()
    {
        $this->restoreRootCacheDirectory();

        $language_cache_directory = $GLOBALS['Language']->getCacheDirectory();
        $this->recreateDirectory($language_cache_directory);
    }

    public function restoreRootCacheDirectory()
    {
        $cache_directory = ForgeConfig::get('codendi_cache_dir');
        $this->recreateDirectory($cache_directory);
    }

    private function recreateDirectory($directory)
    {
        if (! is_dir(realpath($directory))) {
            $this->logger->info('Recreating ' . $directory);
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Some files might have been generated as root but should be owned by codendiadm
     */
    public function restoreOwnership()
    {
        $backend = Backend::instance();

        $cache_directory = realpath(ForgeConfig::get('codendi_cache_dir'));
        if ($cache_directory === false || $cache_directory === '') {
            $this->logger->error('codendi_cache_dir parameter is invalid, please check your configuration');
            return;
        }
        $this->logger->debug('Restore ownership to ' . $cache_directory);
        $backend->changeOwnerGroupMode(
            $cache_directory,
            ForgeConfig::getApplicationUserLogin(),
            ForgeConfig::getApplicationUserLogin(),
            0750
        );

        $language_cache_directory = $GLOBALS['Language']->getCacheDirectory();
        $this->logger->debug('Restore ownership to ' . $language_cache_directory);
        $backend->recurseChownChgrp(
            $language_cache_directory,
            ForgeConfig::getApplicationUserLogin(),
            ForgeConfig::getApplicationUserLogin(),
            array('php')
        );

        \Tuleap\Request\FrontRouter::restoreOwnership($this->logger, $backend);

        PluginLoader::restoreOwnershipOnCacheFile($this->logger, $backend);
    }
}
