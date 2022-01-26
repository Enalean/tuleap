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

use Tuleap\Docman\Item\PaginatedParentRowCollection;
use Tuleap\REST\JsonCast;
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
     * @var string {@type string}
     */
    public $post_processed_description;
    public string $status;
    /**
     * @var MinimalUserRepresentation {@type MinimalUserRepresentation}
     */
    public MinimalUserRepresentation $owner;

    /**
     * @var string | null {@type string}
     */
    public $last_update_date;
    /**
     * @var array {@type ParentFolderRepresentation}
     */
    public array $parents;
    /**
     * @var string | null {@type string}
     */
    public $type;

    private function __construct(
        int $id,
        string $title,
        string $post_processed_description,
        string $status,
        MinimalUserRepresentation $owner,
        ?string $update_date,
        PaginatedParentRowCollection $parents,
        ?string $type,
    ) {
        $this->id                         = $id;
        $this->title                      = $title;
        $this->post_processed_description = $post_processed_description;
        $this->status                     = $status;
        $this->owner                      = $owner;
        $this->last_update_date           = JsonCast::toDate($update_date);
        $this->parents                    = $parents->getPaginatedElementCollection();
        $this->type                       = $type;
    }

    public static function build(
        \Docman_Item $item,
        \Codendi_HTMLPurifier $purifier,
        string $status,
        \PFUser $user,
        PaginatedParentRowCollection $parents,
        ?string $type,
    ): self {
        return new self(
            (int) $item->getId(),
            (string) $item->getTitle(),
            $purifier->purifyTextWithReferences($item->getDescription(), $item->getGroupId()),
            $status,
            MinimalUserRepresentation::build($user),
            (string) $item->getUpdateDate(),
            $parents,
            $type
        );
    }
}
