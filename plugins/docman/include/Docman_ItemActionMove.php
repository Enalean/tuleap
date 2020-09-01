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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_ItemActionMove extends \Docman_ItemAction
{
    public function __construct(&$item)
    {
        parent::__construct($item);
        $this->action = 'move';
        $this->classes = 'docman_item_option_move';
        $this->title = \dgettext('tuleap-docman', 'Move');
        $this->other_icons[] = 'move-up';
        $this->other_icons[] = 'move-down';
        $this->other_icons[] = 'move-beginning';
        $this->other_icons[] = 'move-end';
    }
}
