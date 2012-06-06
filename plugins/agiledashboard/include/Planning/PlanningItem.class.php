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

require_once 'Planning.class.php';

/**
 * An item to be displayed in a planning.
 * 
 * Given a planning was configured to move stories from the product backlog to
 * the selected release:
 * 
 *     Product Backlog | Release 1.0
 *     ----------------+-------------
 *     + Epic 2        | + Epic 1
 *       + Story 2     |   + Story 1
 *         + Task 2    |     + Task 1
 * 
 * Epics, stories and tasks all need to be displayed in the planning. They are
 * all "planning items".
 * 
 * But there are some subtle differences:
 *   - Epics are just here to provide some context to stories.
 *   - Stories are the real plannified items.
 *   - Tasks are just here to provide some details regarding the stories.
 * 
 * The Planning_Item::isPlannifiable() method allows one to know whether an
 * item is the kind being plannified (e.g. Stories).
 * 
 * The Planning_Item::isContext() method allows one to know whether an item is
 * an ancestor of the kind being plannified (e.g. Epics).
 * 
 * Items for which both of these methods return false are details (e.g. Tasks).
 */
abstract class Planning_Item {

    /**
     * @var Planning
     */
    protected $planning;
    
    public function __construct(Planning $planning) {
        $this->planning = $planning;
    }
    
    public abstract function getEditUri();
    public abstract function getXRef();
    public abstract function getTitle();
    public abstract function getId();
    public abstract function isPlannifiable();
    public abstract function isContext();
}

?>
