<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

use Tuleap\Docman\Version\Version;

class Docman_LinkVersion implements Version //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    private $id;
    private $authorId;
    private $itemId;
    private $number;
    private $label;
    private $changelog;
    private $date;
    private $link;

    public function __construct(array $data)
    {
        $this->initFromRow($data);
    }

    #[Override]
    public function getId(): int
    {
        return (int) $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getAuthorId()
    {
        return $this->authorId;
    }

    public function setAuthorId($authorId)
    {
        $this->authorId = $authorId;
    }

    public function getItemId()
    {
        return $this->itemId;
    }

    public function setItemId($itemId)
    {
        $this->itemId = $itemId;
    }

    #[Override]
    public function getNumber(): int
    {
        return (int) $this->number;
    }

    public function setNumber($number)
    {
        $this->number = $number;
    }

    #[Override]
    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel($label): void
    {
        $this->label = $label;
    }

    public function getChangelog()
    {
        return $this->changelog;
    }

    public function setChangelog($changelog): void
    {
        $this->changelog = $changelog;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date): void
    {
        $this->date = $date;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function setLink($link): void
    {
        $this->link = $link;
    }

    public function getContent()
    {
        return $this->link;
    }

    private function initFromRow($row): void
    {
        $this->setId($row['id']);
        $this->setAuthorId($row['user_id']);
        $this->setItemId($row['item_id']);
        $this->setNumber($row['number']);
        $this->setLabel($row['label']);
        $this->setChangelog($row['changelog']);
        $this->setDate($row['date']);
        $this->setLink($row['link_url']);
    }
}
