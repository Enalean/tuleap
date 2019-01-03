<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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

require_once('DocmanConstants.class.php');

define('PLUGIN_DOCMAN_BASE_DIR', dirname(__FILE__));

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

/**
 * Event raised when a docman link item ihas new version
 *
 * Parameters:
 *      'item'    => Docman_Link
 *      'version' => Docman_LinkVersion
 */
define('PLUGIN_DOCMAN_EVENT_NEW_LINKVERSION', 'plugin_docman_event_new_linkVersion');

/**
 * Event raised when a new Docman wiki item is created
 *
 * Parameters:
 *      'item'      => Docman_Item
 *      'group_id'  => integer
 *      'wiki_page' => string
 */
define('PLUGIN_DOCMAN_EVENT_NEW_PHPWIKI_PAGE', 'plugin_docman_event_new_wikipage');

/**
 * Event raised when docman need to get a phpwiki page
 *
 * Parameters:
 *      'phpwiki_page_name'
 *      'project_id'
 *      'phpwiki_page'
 */
define('PLUGIN_DOCMAN_EVENT_GET_PHPWIKI_PAGE', 'plugin_docman_event_get_phpwiki_page');


define('PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED', 0);
define('PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED',  1);
define('PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED',   2);
define('PLUGIN_DOCMAN_APPROVAL_TABLE_DELETED',  3);

define('PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET',   0);
define('PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED', 1);
define('PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED', 2);
define('PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED', 3);
define('PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED', 4);

define('PLUGIN_DOCMAN_APPROVAL_NOTIF_DISABLED',   0);
define('PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE',  1);
define('PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL', 2);