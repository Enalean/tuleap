<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Files;

use Tuleap\REST\JsonCast;
use Tuleap\User\REST\UserRepresentation;

/**
 * @psalm-immutable
 */
final class FileVersionRepresentation
{
    /**
     * @var int Item identifier
     */
    public $id;
    /**
     * @var string name of version
     */
    public $name;
    /**
     * @var string name of the uploaded file
     */
    public $filename;
    /**
     * @var string link to download the version
     */
    public $download_href;
    /**
     * @var string|null link to the approval table
     */
    public ?string $approval_href;
    /**
     * @var UserRepresentation User who made the change
     */
    public UserRepresentation $author;
    /**
     * @var string Date of the change
     */
    public string $date;
    /**
     * @var string Description of the changes
     */
    public string $changelog;

    private function __construct(
        int $id,
        ?string $label,
        string $filename,
        int $group_id,
        int $item_id,
        ?string $approval_href,
        UserRepresentation $author,
        string $date,
        string $changelog,
    ) {
        $this->id            = $id;
        $this->name          = ($label) ?: "";
        $this->filename      = $filename;
        $this->author        = $author;
        $this->date          = $date;
        $this->changelog     = $changelog;
        $this->approval_href = $approval_href;
        $this->download_href = "/plugins/docman/?"
            . http_build_query(
                [
                    'group_id'       => $group_id,
                    'action'         => 'show',
                    'id'             => $item_id,
                    'version_number' => $id,
                ]
            );
    }

    public static function build(
        int $version_id,
        ?string $label,
        string $filename,
        int $group_id,
        int $item_id,
        ?string $approval_href,
        \PFUser $author,
        \DateTimeInterface $date,
        string $changelog,
    ): self {
        return new self(
            $version_id,
            $label,
            $filename,
            $group_id,
            $item_id,
            $approval_href,
            UserRepresentation::build($author),
            JsonCast::fromNotNullDateTimeToDate($date),
            $changelog,
        );
    }
}
