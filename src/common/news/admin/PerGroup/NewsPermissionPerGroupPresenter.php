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

namespace Tuleap\News\Admin\PerGroup;

use ProjectUGroup;

class NewsPermissionPerGroupPresenter
{
    /**
     * @var array
     */
    public $news;

    /**
     * @var bool
     */
    public $has_news;

    /**
     * @var String
     */
    public $user_group_name;

    /**
     * @var ProjectUGroup
     */
    private $ugroup;

    /**
     * @var bool
     */
    public $is_a_user_group_selected;

    public function __construct(
        array $news,
        ProjectUGroup $ugroup = null
    ) {
        $this->news     = $news;
        $this->has_news = count($news) > 0;
        $this->ugroup   = $ugroup;

        $this->setUserGroupName();

        $this->is_a_user_group_selected = $this->user_group_name !== '';
    }

    private function setUserGroupName()
    {
        if ($this->ugroup === null
            || $this->ugroup ->getName() === null
        ) {
            $this->user_group_name = '';

            return;
        }

        $this->user_group_name = $this->ugroup->getTranslatedName();
    }
}
