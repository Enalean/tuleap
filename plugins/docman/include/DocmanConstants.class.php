<?php
/**
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */
 
define('PLUGIN_DOCMAN_DB_FALSE', 0);
define('PLUGIN_DOCMAN_DB_TRUE',  1);

define('PLUGIN_DOCMAN_EVENT_ADD',         1);
define('PLUGIN_DOCMAN_EVENT_EDIT',        2);
define('PLUGIN_DOCMAN_EVENT_MOVE',        3);
define('PLUGIN_DOCMAN_EVENT_DEL',         4);
define('PLUGIN_DOCMAN_EVENT_ACCESS',      5);
define('PLUGIN_DOCMAN_EVENT_NEW_VERSION', 6);
define('PLUGIN_DOCMAN_EVENT_METADATA_UPDATE', 7);

define('PLUGIN_DOCMAN_VIEW_PREF',            'plugin_docman_view');
define('PLUGIN_DOCMAN_EXPAND_FOLDER_PREF', 'plugin_docman_hide');
define('PLUGIN_DOCMAN_EXPAND_FOLDER',      2);
define('PLUGIN_DOCMAN_PREF', 'plugin_docman');

define('PLUGIN_DOCMAN_ITEM_TYPE_FOLDER',       1);
define('PLUGIN_DOCMAN_ITEM_TYPE_FILE',         2);
define('PLUGIN_DOCMAN_ITEM_TYPE_LINK',         3);
define('PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE', 4);
define('PLUGIN_DOCMAN_ITEM_TYPE_WIKI',         5);
define('PLUGIN_DOCMAN_ITEM_TYPE_EMPTY',        6);

define('PLUGIN_DOCMAN_ITEM_STATUS_NONE',     0);
define('PLUGIN_DOCMAN_ITEM_STATUS_DRAFT',    1);
define('PLUGIN_DOCMAN_ITEM_STATUS_APPROVED', 2);
define('PLUGIN_DOCMAN_ITEM_STATUS_REJECTED', 3);

define('PLUGIN_DOCMAN_ITEM_VALIDITY_PERMANENT', 0);

define('PLUGIN_DOCMAN_NOTIFICATION', 'plugin_docman');
define('PLUGIN_DOCMAN_NOTIFICATION_CASCADE', 'plugin_docman_cascade');

?>
