<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Folders;

use Tuleap\User\REST\MinimalUserRepresentation;

/**
 * @psalm-immutable
 */
final class SearchRepresentation
{
    /**
     * @var int {@type int}
     */
    public $id;

    /**
     * @var string {@type string}
     */
    public $title;

    /**
     * @var string | null {@type string}
     */
    public $description;
    public string $status;
    /**
     * @var MinimalUserRepresentation {@type MinimalUserRepresentation}
     */
    public MinimalUserRepresentation $owner;

    /**
     * @var string | null {@type string}
     */
    public $last_update_date;

    private function __construct(int $id, string $title, ?string $description, string $status, MinimalUserRepresentation $owner, ?string $update_date)
    {
        $this->id               = $id;
        $this->title            = $title;
        $this->description      = $description;
        $this->status           = $status;
        $this->owner            = $owner;
        $this->last_update_date = $update_date;
    }

    public static function build(array $item, string $status, \PFUser $user): self
    {
        return new self(
            (int) $item["item_id"],
            $item["title"],
            $item["description"],
            $status,
            MinimalUserRepresentation::build($user),
            $item["update_date"],
        );
    }
}
