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
use Tuleap\Docman\REST\v1\Files\FilePropertiesRepresentation;
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

    /**
     * @var string | null {@type string}
     */
    public $status;

    /**
     * @var MinimalUserRepresentation {@type MinimalUserRepresentation}
     */
    public MinimalUserRepresentation $owner;

    /**
     * @var string | null {@type string}
     */
    public $last_update_date;

    /**
     * @var string | null {@type string}
     */
    public $creation_date;

    /**
     * @var string | null {@type string}
     */
    public $obsolescence_date;

    /**
     * @var array {@type ParentFolderRepresentation}
     */
    public array $parents;
    /**
     * @var string | null {@type string}
     */
    public $type;

    /**
     * @var FilePropertiesRepresentation | null
     */
    public $file_properties;

    private function __construct(
        int $id,
        string $title,
        string $post_processed_description,
        ?string $status,
        MinimalUserRepresentation $owner,
        string $update_date,
        string $creation_date,
        ?int $obsolescence_date,
        PaginatedParentRowCollection $parents,
        ?string $type,
        ?FilePropertiesRepresentation $file_properties,
    ) {
        $this->id                         = $id;
        $this->title                      = $title;
        $this->post_processed_description = $post_processed_description;
        $this->status                     = $status;
        $this->owner                      = $owner;
        $this->last_update_date           = JsonCast::toDate($update_date);
        $this->creation_date              = JsonCast::toDate($creation_date);
        $this->obsolescence_date          = JsonCast::toDate($obsolescence_date);
        $this->parents                    = $parents->getPaginatedElementCollection();
        $this->type                       = $type;
        $this->file_properties            = $file_properties;
    }

    public static function build(
        \Docman_Item $item,
        \Codendi_HTMLPurifier $purifier,
        ?string $status,
        \PFUser $user,
        PaginatedParentRowCollection $parents,
        ?string $type,
        ?FilePropertiesRepresentation $file_properties,
    ): self {
        $obsolescence_date = $item->getObsolescenceDate();

        return new self(
            (int) $item->getId(),
            (string) $item->getTitle(),
            $purifier->purifyTextWithReferences($item->getDescription(), $item->getGroupId()),
            $status,
            MinimalUserRepresentation::build($user),
            (string) $item->getUpdateDate(),
            (string) $item->getCreateDate(),
            $obsolescence_date > 0 ? $obsolescence_date : null,
            $parents,
            $type,
            $file_properties,
        );
    }
}
