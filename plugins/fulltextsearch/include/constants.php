<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

define('FULLTEXTSEARCH_BASE_URL', '/plugins/fulltextsearch');
define('FULLTEXTSEARCH_BASE_DIR', dirname(__FILE__));
define('FULLTEXTSEARCH_TEMPLATE_DIR', dirname(__FILE__).'/../templates');

/**
 * Get all the document category search types available
 *
 * Parameters:
 * 'all_document_search_types'  => array
 *
 * Expected results
 * 'all_document_search_types' => array(
 *      array(
 *          'key'       => SERVICE_NAME,
 *          'name'      => DISPLAY_NAME,
 *          'info'      => string | false, #special info to display
 *          'available' => boolean,        #is currently available
 *      )
 * )
 */
define('FULLTEXTSEARCH_EVENT_FETCH_ALL_DOCUMENT_SEARCH_TYPES', 'fulltextsearch_event_fetch_all_document_search_types');
