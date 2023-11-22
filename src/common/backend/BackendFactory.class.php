<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class BackendFactory
{
    /**
     * Return a Backend instance
     *
     * Let plugins propose their own backend. If none provided, use the default one.
     *
     * @return Backend
     */
    public static function getBackend()
    {
        $backend = null;
        $params  = ['backend' => &$backend];
        EventManager::instance()->processEvent('backend_factory_get_backend', $params);
        if ($backend === null) {
            $backend = Backend::instance();
        }
        return $backend;
    }

    /**
     * Return a BackendSystem instance
     *
     * Let plugins propose their own backend. If none provided, use the default one.
     *
     * @return BackendSystem
     */
    public static function getSystem()
    {
        $backend = null;
        $params  = ['backend' => &$backend];
        EventManager::instance()->processEvent('backend_factory_get_system', $params);
        if ($backend === null) {
            $backend = BackendSystem::instance();
        }
        return $backend;
    }

    /**
     * Return a BackendAliases instance
     *
     * Let plugins propose their own backend. If none provided, use the default one.
     *
     * @return BackendAliases
     */
    public static function getAliases()
    {
        $backend = null;
        $params  = ['backend' => &$backend];
        EventManager::instance()->processEvent('backend_factory_get_aliases', $params);
        if ($backend === null) {
            $backend = BackendAliases::instance();
        }
        return $backend;
    }
}
