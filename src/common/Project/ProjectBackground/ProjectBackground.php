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

namespace Tuleap\Project\ProjectBackground;

/**
 * @psalm-immutable
 * @psalm-import-type ValidProjectBackgroundName from \Tuleap\Project\ProjectBackground\ProjectBackgroundName
 */
class ProjectBackground
{
    private const NO_BACKGROUND_IDENTIFIER = "0";

    /**
     * @var string
     * @psalm-var ValidProjectBackgroundName|self::NO_BACKGROUND_IDENTIFIER
     */
    public $identifier;
    /**
     * @var string
     */
    public $author;
    /**
     * @var bool
     */
    public $is_selected;
    /**
     * @var bool
     */
    public $is_no_background;

    /**
     * @psalm-param ValidProjectBackgroundName|self::NO_BACKGROUND_IDENTIFIER $identifier
     */
    private function __construct(string $identifier, string $author, bool $is_selected)
    {
        $this->identifier  = $identifier;
        $this->author      = $author;
        $this->is_selected = $is_selected;

        $this->is_no_background = $identifier === self::NO_BACKGROUND_IDENTIFIER;
    }

    /**
     * @psalm-param ValidProjectBackgroundName $identifier
     */
    public static function buildFromIdentifier(string $identifier, string $author, bool $is_selected): self
    {
        return new self($identifier, $author, $is_selected);
    }

    public static function buildNoBackground(bool $is_selected): self
    {
        return new self(self::NO_BACKGROUND_IDENTIFIER, '', $is_selected);
    }
}
