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

namespace Tuleap\Docman\REST\v1;

use Docman_Item;
use Docman_ItemFactory;
use PFUser;
use Project;

class DocmanItemsRequest
{
    /**
     * @var Docman_Item
     */
    private $item;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var Docman_ItemFactory
     */
    private $factory;

    public function __construct(
        Docman_ItemFactory $factory,
        Docman_Item $item,
        Project $project,
        PFUser $user
    ) {
        $this->item    = $item;
        $this->project = $project;
        $this->user    = $user;
        $this->factory = $factory;
    }

    /**
     * @return Docman_Item
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return PFUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return Docman_ItemFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }
}
