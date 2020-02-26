<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Docman\XML\Import;

use DateTimeImmutable;
use PFUser;

final class ImportProperties
{
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $description;
    /**
     * @var int
     */
    private $item_type_id;
    /**
     * @var string|null
     */
    private $link_url;
    /**
     * @var \DateTimeImmutable
     */
    private $create_date;
    /**
     * @var DateTimeImmutable
     */
    private $update_date;
    /**
     * @var PFUser
     */
    private $owner;

    private function __construct(
        string $title,
        string $description,
        int $item_type_id,
        \DateTimeImmutable $create_date,
        \DateTimeImmutable $update_date,
        PFUser $owner,
        ?string $link_url
    ) {
        $this->title        = $title;
        $this->description  = $description;
        $this->item_type_id = $item_type_id;
        $this->create_date  = $create_date;
        $this->update_date  = $update_date;
        $this->link_url     = $link_url;
        $this->owner        = $owner;
    }

    public static function buildLink(
        string $title,
        string $description,
        string $link_url,
        DateTimeImmutable $create_date,
        DateTimeImmutable $update_date,
        PFUser $owner
    ): self {
        return new self(
            $title,
            $description,
            PLUGIN_DOCMAN_ITEM_TYPE_LINK,
            $create_date,
            $update_date,
            $owner,
            $link_url
        );
    }

    public static function buildEmpty(
        string $title,
        string $description,
        DateTimeImmutable $create_date,
        DateTimeImmutable $update_date,
        PFUser $owner
    ): self {
        return new self(
            $title,
            $description,
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY,
            $create_date,
            $update_date,
            $owner,
            null
        );
    }

    public static function buildEmbedded(
        string $title,
        string $description,
        DateTimeImmutable $create_date,
        DateTimeImmutable $update_date,
        PFUser $owner
    ): self {
        return new self(
            $title,
            $description,
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
            $create_date,
            $update_date,
            $owner,
            null
        );
    }

    public static function buildFile(
        string $title,
        string $description,
        DateTimeImmutable $create_date,
        DateTimeImmutable $update_date,
        PFUser $owner
    ): self {
        return new self(
            $title,
            $description,
            PLUGIN_DOCMAN_ITEM_TYPE_FILE,
            $create_date,
            $update_date,
            $owner,
            null
        );
    }

    public static function buildFolder(
        string $title,
        string $description,
        DateTimeImmutable $create_date,
        DateTimeImmutable $update_date,
        PFUser $owner
    ): self {
        return new self(
            $title,
            $description,
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
            $create_date,
            $update_date,
            $owner,
            null
        );
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getItemTypeId(): int
    {
        return $this->item_type_id;
    }

    public function getLinkUrl(): ?string
    {
        return $this->link_url;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCreateDate(): \DateTimeImmutable
    {
        return $this->create_date;
    }

    public function getUpdateDate(): DateTimeImmutable
    {
        return $this->update_date;
    }

    public function getOwner(): PFUser
    {
        return $this->owner;
    }
}
