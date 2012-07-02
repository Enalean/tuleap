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

/**
 * Renders some data for an external actor (typically the end user, but maybe
 * also some external system).
 */
interface View {
    
    /**
     * Generates and returns the rendered data (typically in HTML).
     * 
     * @return String
     */
    public function renderToString();
    
    /**
     * Same as renderToString, but appends the result to the current page.
     */
    public function renderToPage();
}
?>
