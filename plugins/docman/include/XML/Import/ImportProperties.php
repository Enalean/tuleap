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
    private $wiki_page;
    /**
     * @var string|null
     */
    private $link_url;

    private function __construct(
        string $title,
        string $description,
        int $item_type_id,
        ?string $wiki_page,
        ?string $link_url
    ) {
        $this->title        = $title;
        $this->description  = $description;
        $this->item_type_id = $item_type_id;
        $this->wiki_page    = $wiki_page;
        $this->link_url     = $link_url;
    }

    public static function buildWiki(string $title, string $description, string $wiki_page): self
    {
        return new self($title, $description, PLUGIN_DOCMAN_ITEM_TYPE_WIKI, $wiki_page, null);
    }

    public static function buildLink(string $title, string $description, string $link_url): self
    {
        return new self($title, $description, PLUGIN_DOCMAN_ITEM_TYPE_LINK, null, $link_url);
    }

    public static function buildEmpty(string $title, string $description): self
    {
        return new self($title, $description, PLUGIN_DOCMAN_ITEM_TYPE_EMPTY, null, null);
    }

    public static function buildEmbedded(string $title, string $description): self
    {
        return new self($title, $description, PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE, null, null);
    }

    public static function buildFile(string $title, string $description): self
    {
        return new self($title, $description, PLUGIN_DOCMAN_ITEM_TYPE_FILE, null, null);
    }

    public static function buildFolder(string $title, string $description): self
    {
        return new self($title, $description, PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, null, null);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getItemTypeId(): int
    {
        return $this->item_type_id;
    }

    public function getWikiPage(): ?string
    {
        return $this->wiki_page;
    }

    public function getLinkUrl(): ?string
    {
        return $this->link_url;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
