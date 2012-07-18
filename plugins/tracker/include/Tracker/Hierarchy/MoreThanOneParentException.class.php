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

class Tracker_Hierarchy_MoreThanOneParentException extends Exception {

    public function __construct(Tracker_Artifact $child, array $parents) {
        parent::__construct($this->getTranslatedMessage(array($this->getParentTitle($child), $this->getParentsList($parents))));
    }

    private function getTranslatedMessage(array $arguments) {
        return $GLOBALS['Language']->getText('plugin_tracker_hierarchy', 'error_more_than_one_parent', $arguments);
    }

    private function getParentsList(array $parents) {
        return implode(', ', array_map(array($this, 'getParentTitle'), $parents));
    }

    private function getParentTitle(Tracker_Artifact $artifact) {
        return '"'.$artifact->getTitle().' ('.$artifact->fetchXRefLink().')"';
    }

}

?>
