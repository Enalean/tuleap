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

    /**
     * Some files might have been generated as root but should be owned by codendiadm
     */
    public function restoreOwnership() {
        $backend = Backend::instance();
        $this->logger->debug("Restore ownership to ".$GLOBALS['Language']->getCacheDirectory());
        $backend->recurseChownChgrp($GLOBALS['Language']->getCacheDirectory(), ForgeConfig::get('sys_http_user'), ForgeConfig::get('sys_http_user'));

        $plugin_manager = PluginManager::instance();
        $this->logger->debug("Restore ownership to ".$plugin_manager->getCacheFile());
        $backend->changeOwnerGroupMode($plugin_manager->getCacheFile(), ForgeConfig::get('sys_http_user'), ForgeConfig::get('sys_http_user'), 0600);
    }
}
