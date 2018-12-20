<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\News\Admin\PermissionsPerGroup;

class NewsVisibilityRepresentation
{
    /**
     * @var String
     */
    public $news_name;
    /**
     * @var String
     */
    public $admin_quicklink;
    /**
     * @var bool
     */
    public $is_public;

    public function __construct(
        $news_name,
        $admin_quicklink,
        $is_public
    ) {
        $this->news_name       = $news_name;
        $this->admin_quicklink = $admin_quicklink;
        $this->is_public       = $is_public;
    }
}
