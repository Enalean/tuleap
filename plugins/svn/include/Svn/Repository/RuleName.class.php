<?php
/**
 * Copyright (c) Enalean 2016. All rights reserved
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
namespace Tuleap\Svn\Repository;

use Rule;

/**
 * Check if a project name is valid
 *
 * This extends the user name validation
 */
class RuleName extends Rule {

    /**
     * Check validity
     *
     * @param String $val
     *
     * @return Boolean
     */
    public function isValid($val) {
        return preg_match('/^[a-z][a-z1-9-_.]{2,254}\z/i', $val);
    }

    private function getErrorNoSpaces() {
        return $GLOBALS['Language']->getText('plugin_svn', 'repository_spaces');
    }
}