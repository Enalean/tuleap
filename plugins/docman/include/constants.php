<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * Event raised when someone comments an approval table
 *
 * parameters =>
 *   'item'       => Docman_Item
 *   'version_nb' => Docman_Version
 *   'table'      => Docman_ApprovalTable
 *   'review'     => Docman_ApprovalReviewer
 */
define('PLUGIN_DOCMAN_EVENT_APPROVAL_TABLE_COMMENT', 'plugin_docman_approval_table_comment');



/**
 * Event raised when a new empty doc is created
 *
 * Parameters:
 *      'item' => Docman_Item
 */
define('PLUGIN_DOCMAN_EVENT_NEW_EMPTY', 'plugin_docman_event_new_empty');

/**
 * Event raised when a new link is created
 *
 * Parameters:
 *      'item' => Docman_Item
 */
define('PLUGIN_DOCMAN_EVENT_NEW_LINK', 'plugin_docman_event_new_link');

/**
 * Event raised when a new folder is created
 *
 * Parameters:
 *      'item' => Docman_Item
 */
define('PLUGIN_DOCMAN_EVENT_NEW_FOLDER', 'plugin_docman_event_new_folder');

/**
 * Event raised when a docman item is copied
 *
 * Parameters:
 *      'item' => Docman_Item
 */
define('PLUGIN_DOCMAN_EVENT_COPY', 'plugin_docman_event_copy');
