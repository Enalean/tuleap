<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */


define('PLUGIN_DOCMAN_DB_FALSE', 0);
define('PLUGIN_DOCMAN_DB_TRUE', 1);

define('PLUGIN_DOCMAN_EVENT_ADD', 1);
define('PLUGIN_DOCMAN_EVENT_EDIT', 2);
define('PLUGIN_DOCMAN_EVENT_MOVE', 3);
define('PLUGIN_DOCMAN_EVENT_DEL', 4);
define('PLUGIN_DOCMAN_EVENT_ACCESS', 5);
define('PLUGIN_DOCMAN_EVENT_NEW_VERSION', 6);
define('PLUGIN_DOCMAN_EVENT_METADATA_UPDATE', 7);
define('PLUGIN_DOCMAN_EVENT_WIKIPAGE_UPDATE', 8);

define('PLUGIN_DOCMAN_EVENT_SET_VERSION_AUTHOR', 9);
define('PLUGIN_DOCMAN_EVENT_SET_VERSION_DATE', 10);
define('PLUGIN_DOCMAN_EVENT_DEL_VERSION', 11);
define('PLUGIN_DOCMAN_EVENT_RESTORE', 12);
define('PLUGIN_DOCMAN_EVENT_RESTORE_VERSION', 13);

define('PLUGIN_DOCMAN_EVENT_LOCK_ADD', 20);
define('PLUGIN_DOCMAN_EVENT_LOCK_DEL', 21);

define('PLUGIN_DOCMAN_EVENT_PERMS_CHANGE', 22);

define('PLUGIN_DOCMAN_VIEW_PREF', 'plugin_docman_view');
define('PLUGIN_DOCMAN_EXPAND_FOLDER_PREF', 'plugin_docman_hide');
define('PLUGIN_DOCMAN_EXPAND_FOLDER', 2);
define('PLUGIN_DOCMAN_PREF', 'plugin_docman');

define('PLUGIN_DOCMAN_ITEM_TYPE_FOLDER', 1);
define('PLUGIN_DOCMAN_ITEM_TYPE_FILE', 2);
define('PLUGIN_DOCMAN_ITEM_TYPE_LINK', 3);
define('PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE', 4);
define('PLUGIN_DOCMAN_ITEM_TYPE_WIKI', 5);
define('PLUGIN_DOCMAN_ITEM_TYPE_EMPTY', 6);

define('PLUGIN_DOCMAN_ITEM_STATUS_NONE', 100);
define('PLUGIN_DOCMAN_ITEM_STATUS_DRAFT', 101);
define('PLUGIN_DOCMAN_ITEM_STATUS_APPROVED', 102);
define('PLUGIN_DOCMAN_ITEM_STATUS_REJECTED', 103);

define('PLUGIN_DOCMAN_ITEM_VALIDITY_PERMANENT', 0);

define('PLUGIN_DOCMAN_NOTIFICATION', 'plugin_docman');
define('PLUGIN_DOCMAN_NOTIFICATION_CASCADE', 'plugin_docman_cascade');

define('PLUGIN_DOCMAN_SORT_DESC', 0);
define('PLUGIN_DOCMAN_SORT_ASC', 1);

define('DOCMAN_BASE_URL', '/plugins/docman');
