<?php
/**
 * Copyright (c) Enalean, 2015. All rights reserved
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

class SiteCache {

    private $logger;

    public function __construct(Logger $logger = null) {
        $this->logger = $logger ? $logger : new BackendLogger() ;
    }

    public function invalidatePluginBasedCaches() {
        $this->invalidateRestler();
        $this->invalidateLanguage();
        $this->invalidateJsCombined();
        $this->invalidateWSDL();
        $this->invalidatePlugin();
    }

    private function invalidateRestler() {
        $this->logger->info('Invalidate Restler cache');
        $restler = new RestlerCache();
        $restler->invalidateCache();
    }

    private function invalidateLanguage() {
        $this->logger->info('Invalidate language cache');
        $GLOBALS['Language']->invalidateCache();
    }

    private function invalidateJsCombined() {
        $this->logger->info('Invalidate JS combined cache');
        $combined = new Combined($GLOBALS['HTML']->getCombinedDirectory());
        $combined->invalidateCache();
    }

    private function invalidateWSDL() {
        $this->logger->info('Invalidate WSDL');
        foreach (glob('/tmp/wsdl-') as $file) {
            unlink($file);
        }
    }

    private function invalidatePlugin() {
        $this->logger->info('Invalidate Plugin hooks');
        PluginManager::instance()->invalidateCache();
    }

    public function restoreCacheDirectories() {
        $cache_directory = ForgeConfig::get('codendi_cache_dir');
        $this->recreateDirectory($cache_directory);

        $combined_cache_directory = ForgeConfig::get('sys_combined_dir');
        $this->recreateDirectory($combined_cache_directory);

        $language_cache_directory = $GLOBALS['Language']->getCacheDirectory();
        $this->recreateDirectory($language_cache_directory);
    }

    private function recreateDirectory($directory) {
        if (! is_dir($directory)) {
            $this->logger->info('Recreating ' . $directory);
            mkdir($directory);
        }
    }

    /**
     * Some files might have been generated as root but should be owned by codendiadm
     */
    public function restoreOwnership() {
        $backend = Backend::instance();

        $cache_directory = ForgeConfig::get('codendi_cache_dir');
        $this->logger->debug('Restore ownership to ' . $cache_directory);
        $backend->changeOwnerGroupMode(
            $cache_directory,
            ForgeConfig::get('sys_http_user'),
            ForgeConfig::get('sys_http_user'),
            0750
        );

        $combined_cache_directory = ForgeConfig::get('sys_combined_dir');
        $this->logger->debug('Restore ownership to ' . $combined_cache_directory);
        $backend->recurseChownChgrp(
            $combined_cache_directory,
            ForgeConfig::get('sys_http_user'),
            ForgeConfig::get('sys_http_user')
        );

        $language_cache_directory = $GLOBALS['Language']->getCacheDirectory();
        $this->logger->debug('Restore ownership to ' . $language_cache_directory);
        $backend->recurseChownChgrp(
            $language_cache_directory,
            ForgeConfig::get('sys_http_user'),
            ForgeConfig::get('sys_http_user')
        );

        $plugin_manager    = PluginManager::instance();
        $plugin_cache_file = $plugin_manager->getCacheFile();
        $this->logger->debug('Restore ownership to ' . $plugin_cache_file);
        $backend->changeOwnerGroupMode(
            $plugin_cache_file,
            ForgeConfig::get('sys_http_user'),
            ForgeConfig::get('sys_http_user'),
            0600
        );
    }
}
