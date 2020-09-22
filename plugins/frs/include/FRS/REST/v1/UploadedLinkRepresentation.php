<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\FRS\REST\v1;

use Tuleap\FRS\UploadedLink;
use Tuleap\REST\JsonCast;
use Tuleap\User\REST\UserRepresentation;

/**
 * @psalm-immutable
 */
class UploadedLinkRepresentation
{
    /**
     * @var int $id {@type int}
     */
    public $id;

    /**
     * @var UserRepresentation
     */
    public $owner;

    /**
     * @var string $link {@type string}
     */
    public $link;

    /**
     * @var string $link {@type string}
     */
    public $name;

    /**
     * @var string $release_time {@type string}
     */
    public $release_time;

    public function __construct(UploadedLink $link)
    {
        $this->id             = JsonCast::toInt($link->getId());
        $this->link           = $link->getLink();
        $this->name           = $link->getName();
        $this->release_time   = JsonCast::toDate($link->getReleaseTime());

        $owner_representation = UserRepresentation::build($link->getOwner());
        $this->owner = $owner_representation;
    }
}
