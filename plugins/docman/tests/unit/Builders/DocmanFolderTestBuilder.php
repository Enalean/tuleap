<?php
/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Builders;

final class DocmanFolderTestBuilder
{
    private int $item_id        = 1;
    private string $title       = 'A folder';
    private string $description = 'A folder description';
    private int $group_id       = 100;
    private int $parent_id      = 0;
    private int $user_id        = 101;

    private function __construct()
    {
    }

    public static function aFolder(): self
    {
        return new self();
    }

    public function withId(int $id): self
    {
        $this->item_id = $id;
        return $this;
    }

    public function withTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function withDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function withGroupId(int $group_id): self
    {
        $this->group_id = $group_id;
        return $this;
    }

    public function withParentId(int $parent_id): self
    {
        $this->parent_id = $parent_id;
        return $this;
    }

    public function withOwnerId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function build(): \Docman_Folder
    {
        return new \Docman_Folder([
            'item_id'     => $this->item_id,
            'title'       => $this->title,
            'description' => $this->description,
            'group_id'    => $this->group_id,
            'parent_id'   => $this->parent_id,
            'user_id'     => $this->user_id,
        ]);
    }
}
