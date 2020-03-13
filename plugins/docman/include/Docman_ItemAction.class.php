<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Docman\View\DocmanViewURLBuilder;

/**
 * ItemAction is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_ItemAction
{
    public $item;
    public $action;
    public $class;
    public $title;
    public $other_icons;
    public $extraUrlParams;

    public function __construct(&$item)
    {
        $this->item             = $item;
        $this->action           = '';
        $this->classes          = '';
        $this->title            = '';
        $this->other_icons      = array();
        $this->extraUrlParams   = array();
    }

    public function fetchAction($params)
    {
        $url   = $params['default_url'] . '&action=' . $this->action . '&id=' . $this->item->getId();
        $title = $this->title;
        $href  = '<a href="' . $url . '">' . $title . '</a>';
        $html  = '<li>' . $href . '</li>';
        return $html;
    }

    public function fetch($params)
    {
        $dfltUrlParams = array('action' => $this->action,
                               'id' => $this->item->getId());
        $_urlParams = array_merge($dfltUrlParams, $this->extraUrlParams);
        $url = DocmanViewURLBuilder::buildActionUrl(
            $this->item,
            $params,
            $_urlParams,
            true,
            true
        );
        $html  = '<a href="' . $url . '" class="' . $this->classes . '" title="' . $this->title . '">';
        $html .= '<img src="' . $params['docman_icons']->getActionIcon($this->action) . '" class="docman_item_icon" alt="[' . $this->title . ']" />';
        $html .= '</a>&nbsp;';
        return $html;
    }
}
class Docman_ItemActionNewFolder extends Docman_ItemAction
{
    public function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'newFolder';
        $this->classes          = 'docman_item_option_newfolder';
        $this->title            = dgettext('tuleap-docman', 'New folder');
    }
}
class Docman_ItemActionNewDocument extends Docman_ItemAction
{
    public function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'newDocument';
        $this->classes          = 'docman_item_option_newdocument';
        $this->title            = dgettext('tuleap-docman', 'New document');
    }
}
class Docman_ItemActionDetails extends Docman_ItemAction
{
    public function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'details';
        $this->classes          = 'docman_item_option_details';
        $this->title            = dgettext('tuleap-docman', 'Properties');
    }
}
class Docman_ItemActionNewVersion extends Docman_ItemAction
{
    public function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'action_new_version';
        $this->classes          = 'docman_item_option_newversion';
        $this->title            = dgettext('tuleap-docman', 'New version');
    }
}
class Docman_ItemActionMove extends Docman_ItemAction
{
    public function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'move';
        $this->classes          = 'docman_item_option_move';
        $this->title            = dgettext('tuleap-docman', 'Move');
        $this->other_icons[]    = 'move-up';
        $this->other_icons[]    = 'move-down';
        $this->other_icons[]    = 'move-beginning';
        $this->other_icons[]    = 'move-end';
    }
}
class Docman_ItemActionPermissions extends Docman_ItemAction
{
    public function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'details&section=permissions';
        $this->classes          = 'docman_item_option_permissions';
        $this->title            = dgettext('tuleap-docman', 'Permissions');
    }
}
class Docman_ItemActionHistory extends Docman_ItemAction
{
    public function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'details&section=history';
        $this->classes          = 'docman_item_option_history';
        $this->title            = dgettext('tuleap-docman', 'History');
    }
}
class Docman_ItemActionNotifications extends Docman_ItemAction
{
    public function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'details&section=notifications';
        $this->classes          = 'docman_item_option_notifications';
        $this->title            = dgettext('tuleap-docman', 'Notifications');
    }
}
class Docman_ItemActionDelete extends Docman_ItemAction
{
    public function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'confirmDelete';
        $this->classes          = 'docman_item_option_delete';
        $this->title            = dgettext('tuleap-docman', 'Delete');
    }
}
class Docman_ItemActionUpdate extends Docman_ItemAction
{
    public function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'action_update';
        $this->classes          = 'docman_item_option_update';
        $this->title            = dgettext('tuleap-docman', 'Update');
    }
}

class Docman_ItemActionCopy extends Docman_ItemAction
{
    public function __construct(&$item, $params)
    {
        parent::__construct($item);
        $this->action           = 'action_copy';
        $this->classes          = 'docman_item_option_copy';
        $this->title            = dgettext('tuleap-docman', 'Copy');

        $currentItem = $params['item'];
        $origAction = isset($params['action']) ? $params['action'] : 'show';
        $this->extraUrlParams   = array('orig_id'     => $currentItem->getId(),
                                        'orig_action' => $origAction);
    }
}

class Docman_ItemActionCut extends Docman_ItemAction
{
    public function __construct(&$item, $params)
    {
        parent::__construct($item);
        $this->action           = 'action_cut';
        $this->classes          = 'docman_item_option_cut';
        $this->title            = dgettext('tuleap-docman', 'Cut');

        $currentItem = $params['item'];
        $origAction = isset($params['action']) ? $params['action'] : 'show';
        $this->extraUrlParams   = array('orig_id'     => $currentItem->getId(),
                                        'orig_action' => $origAction);
    }
}

class Docman_ItemActionPaste extends Docman_ItemAction
{
    public function __construct(&$item, $params)
    {
        parent::__construct($item);
        $this->action           = 'action_paste';
        $this->classes          = 'docman_item_option_paste';
        $this->title            = dgettext('tuleap-docman', 'Paste');
    }
}

class Docman_ItemActionApproval extends Docman_ItemAction
{
    public function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'details&section=approval';
        $this->classes          = 'docman_item_option_approval';
        $this->title            = dgettext('tuleap-docman', 'Approval table');
    }
}
