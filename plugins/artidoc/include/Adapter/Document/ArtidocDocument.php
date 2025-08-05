<?php
/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Artidoc\Adapter\Document;

use Tuleap\Artidoc\Domain\Document\Artidoc;
use Tuleap\Docman\Item\OtherDocument;

class ArtidocDocument extends OtherDocument implements Artidoc
{
    public const TYPE = 'artidoc';

    public function __construct(array $row)
    {
        parent::__construct($row);
    }

    public function toRow(): array
    {
        $row               = parent::toRow();
        $row['other_type'] = self::TYPE;

        return $row;
    }

    public function getId(): int
    {
        if (parent::getId() === null) {
            throw new \RuntimeException('ID not found');
        }

        return (int) parent::getId();
    }

    public function getTitle(bool $key = false): string
    {
        return parent::getTitle($key);
    }

    public function getParentId(): int
    {
        if (parent::getParentId() === null) {
            throw new \RuntimeException('parent ID not found');
        }

        return (int) parent::getParentId();
    }

    public function getProjectId(): int
    {
        return (int) $this->getGroupId();
    }
}
