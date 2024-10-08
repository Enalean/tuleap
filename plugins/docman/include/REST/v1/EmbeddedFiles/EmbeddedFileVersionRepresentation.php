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

namespace Tuleap\Docman\REST\v1\EmbeddedFiles;

use Tuleap\REST\JsonCast;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\REST\UserRepresentation;

/**
 * @psalm-immutable
 */
final class EmbeddedFileVersionRepresentation
{
    /**
     * @var int Version identifier
     */
    public $id;
    /**
     * @var int Version number
     */
    public $number;
    /**
     * @var string name of version
     */
    public $name;
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
    /**
     * @var string Link to open the item at this specific version
     */
    public string $open_href;

    private function __construct(
        int $id,
        int $number,
        ?string $label,
        int $group_id,
        int $item_id,
        ?string $approval_href,
        string $open_href,
        UserRepresentation $author,
        string $date,
        string $changelog,
    ) {
        $this->id            = $id;
        $this->number        = $number;
        $this->name          = ($label) ?: '';
        $this->author        = $author;
        $this->date          = $date;
        $this->changelog     = $changelog;
        $this->approval_href = $approval_href;
        $this->open_href     = $open_href;
    }

    public static function build(
        int $version_id,
        int $number,
        ?string $label,
        int $group_id,
        int $item_id,
        ?string $approval_href,
        string $open_href,
        \PFUser $author,
        \DateTimeInterface $date,
        string $changelog,
        ProvideUserAvatarUrl $provide_user_avatar_url,
    ): self {
        return new self(
            $version_id,
            $number,
            $label,
            $group_id,
            $item_id,
            $approval_href,
            $open_href,
            UserRepresentation::build($author, $provide_user_avatar_url),
            JsonCast::fromNotNullDateTimeToDate($date),
            $changelog,
        );
    }
}
