<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class Planning_IndexPresenter {

    public function __construct(array $short_access) {
        $this->short_access = $short_access;
    }

    public function getShortAccess() {
        return $this->short_access;
    }

    public function hasShortAccess() {
        return count($this->short_access);
    }
    
    public function getLatestLeafMilestone() {
        $latest_short_access = end($this->short_access);
        return end($latest_short_access->getLastTenOpenArtifacts());
    }
}

?>
