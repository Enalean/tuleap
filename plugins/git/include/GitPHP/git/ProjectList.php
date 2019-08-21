<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han
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

namespace Tuleap\Git\GitPHP;

/**
 * GitPHP ProjectList
 *
 * Project list singleton instance and factory
 *
 */

/**
 * ProjectList class
 *
 */
class ProjectList
{

    /**
     * instance
     *
     * Stores the singleton instance of the projectlist
     *
     * @access protected
     * @static
     */
    protected static $instance = null;

    /**
     * GetInstance
     *
     * Returns the singleton instance
     *
     * @access public
     * @static
     * @return mixed instance of projectlist
     * @throws \Exception if projectlist has not been instantiated yet
     */
    public static function GetInstance() // @codingStandardsIgnoreLine
    {
        return self::$instance;
    }

    /**
     * Instantiate
     *
     * Instantiates the singleton instance
     *
     * @static
     * @throws \Exception
     */
    public static function Instantiate(\GitRepository $repository) // @codingStandardsIgnoreLine
    {
        if (self::$instance !== null) {
            return;
        }

        self::$instance = new ProjectProvider($repository);
    }
}
