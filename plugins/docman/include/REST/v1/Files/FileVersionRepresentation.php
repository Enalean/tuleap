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
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\REST\UserRepresentation;

/**
 * @psalm-immutable
 */
final class FileVersionRepresentation
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
     * @var UserRepresentation[] Other users who contributed to the change
     */
    public array $coauthors;
    /**
     * @var string Date of the change
     */
    public string $date;
    /**
     * @var string Description of the changes
     */
    public string $changelog;
    /**
     * @var string Authoring tool used for this version. Empty if unknown.
     */
    public string $authoring_tool;

    /**
     * @param UserRepresentation[] $coauthors
     */
    private function __construct(
        int $id,
        int $number,
        ?string $label,
        string $filename,
        int $group_id,
        int $item_id,
        ?string $approval_href,
        UserRepresentation $author,
        array $coauthors,
        string $date,
        string $changelog,
        string $authoring_tool,
    ) {
        $this->id            = $id;
        $this->number        = $number;
        $this->name          = ($label) ?: '';
        $this->filename      = $filename;
        $this->author        = $author;
        $this->coauthors     = $coauthors;
        $this->date          = $date;
        $this->changelog     = $changelog;
        $this->approval_href = $approval_href;
        $this->download_href = '/plugins/docman/?'
            . http_build_query(
                [
                    'group_id'       => $group_id,
                    'action'         => 'show',
                    'id'             => $item_id,
                    'version_number' => $number,
                ]
            );

        $this->authoring_tool = $authoring_tool;
    }

    /**
     * @param \PFUser[] $coauthors
     */
    public static function build(
        int $version_id,
        int $number,
        ?string $label,
        string $filename,
        int $group_id,
        int $item_id,
        ?string $approval_href,
        \PFUser $author,
        array $coauthors,
        \DateTimeInterface $date,
        string $changelog,
        string $authoring_tool,
        ProvideUserAvatarUrl $provide_user_avatar_url,
    ): self {
        return new self(
            $version_id,
            $number,
            $label,
            $filename,
            $group_id,
            $item_id,
            $approval_href,
            UserRepresentation::build($author, $provide_user_avatar_url),
            array_map(
                static fn (\PFUser $coauthor): UserRepresentation => UserRepresentation::build($coauthor, $provide_user_avatar_url),
                $coauthors
            ),
            JsonCast::fromNotNullDateTimeToDate($date),
            $changelog,
            $authoring_tool,
        );
    }
}
