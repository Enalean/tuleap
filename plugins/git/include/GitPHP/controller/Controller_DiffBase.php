<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han <xiphux@gmail.com>
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
 * GitPHP Controller DiffBase
 *
 * Base controller for diff-type views
 *
 */
/**
 * DiffBase controller class
 *
 */
abstract class Controller_DiffBase extends ControllerBase // @codingStandardsIgnoreLine
{
    public const DIFF_UNIFIED    = 1;
    public const DIFF_SIDEBYSIDE = 2;

    /**
     * ReadQuery
     *
     * Read query into parameters
     *
     * @access protected
     */
    protected function ReadQuery() // @codingStandardsIgnoreLine
    {
        if (! isset($this->params['plain']) || $this->params['plain'] != true) {
            if ($this->DiffMode(isset($_GET['o']) ? $_GET['o'] : '') == self::DIFF_SIDEBYSIDE) {
                $this->params['sidebyside'] = true;
            }
        }
    }

    /**
     * DiffMode
     *
     * Determines the diff mode to use
     *
     * @param string $overrideMode mode overridden by the user
     * @access protected
     */
    protected function DiffMode($overrideMode = '') // @codingStandardsIgnoreLine
    {
        $mode = self::DIFF_UNIFIED; // default

        if (! empty($overrideMode)) {
            /*
             * User is choosing a new mode
             */
            if ($overrideMode == 'sidebyside') {
                $mode = self::DIFF_SIDEBYSIDE;
            } elseif ($overrideMode == 'unified') {
                $mode = self::DIFF_UNIFIED;
            }
        }

        return $mode;
    }

    /**
     * LoadHeaders
     *
     * Loads headers for this template
     *
     * @access protected
     */
    protected function LoadHeaders() // @codingStandardsIgnoreLine
    {
        if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
            $this->headers[] = 'Content-type: text/plain; charset=UTF-8';
        }
    }
}
