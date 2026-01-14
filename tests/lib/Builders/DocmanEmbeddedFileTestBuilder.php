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

namespace Tuleap\Test\Builders;

final class DocmanEmbeddedFileTestBuilder
{
    private array $data = [
        'item_id'     => 1,
        'title'       => 'An embedded file',
        'description' => 'An embedded file description',
        'group_id'    => 100,
        'parent_id'   => 0,
        'user_id'     => 101,
    ];

    private function __construct()
    {
    }

    public static function anEmbeddedFile(): self
    {
        return new self();
    }

    public function withId(int $id): self
    {
        $this->data['item_id'] = $id;
        return $this;
    }

    public function withTitle(string $title): self
    {
        $this->data['title'] = $title;
        return $this;
    }

    public function withDescription(string $description): self
    {
        $this->data['description'] = $description;
        return $this;
    }

    public function withGroupId(int $group_id): self
    {
        $this->data['group_id'] = $group_id;
        return $this;
    }

    public function withParentId(int $parent_id): self
    {
        $this->data['parent_id'] = $parent_id;
        return $this;
    }

    public function withOwnerId(int $user_id): self
    {
        $this->data['user_id'] = $user_id;
        return $this;
    }

    public function build(): \Docman_EmbeddedFile
    {
        return new \Docman_EmbeddedFile($this->data);
    }
}
