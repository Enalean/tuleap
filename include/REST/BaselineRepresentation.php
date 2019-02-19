<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

namespace Tuleap\Baseline\REST;

use DateTime;
use Tuleap\Baseline\Baseline;

class BaselineRepresentation
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var int */
    public $milestone_id;

    /** @var int */
    public $author_id;

    /** @var DateTime */
    public $creation_date;

    public function __construct(Baseline $baseline)
    {
        $this->id            = $baseline->getId();
        $this->name          = $baseline->getName();
        $this->milestone_id  = $baseline->getMilestone()->getId();
        $this->author_id     = $baseline->getAuthor()->getId();
        $this->creation_date = $baseline->getCreationDate()->format('c');
    }
}
