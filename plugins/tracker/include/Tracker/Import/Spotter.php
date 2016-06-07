<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\Import;

class Spotter
{
    /**
     * @var Spotter
     */
    private static $instance;

    /**
     * @var bool
     */
    private $is_running;

    private function __construct()
    {
        $this->is_running = false;
    }

    /**
     * @return Spotter
     */
    public static function instance()
    {
        if (! isset(self::$instance)) {
            self::$instance = new Spotter();
        }
        return self::$instance;
    }

    public static function setInstance(Spotter $spotter)
    {
        self::$instance = $spotter;
    }

    public static function clearInstance()
    {
        self::$instance = null;
    }

    /**
     * @return bool
     */
    public function isImportRunning()
    {
        return $this->is_running;
    }

    public function startImport()
    {
        $this->is_running = true;
    }

    public function endImport()
    {
        $this->is_running = false;
    }
}
