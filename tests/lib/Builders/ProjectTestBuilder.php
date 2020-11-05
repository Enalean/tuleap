<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Test\Builders;

use Project;
use TemplateSingleton;

final class ProjectTestBuilder
{
    private $data = [
        'group_id'        => '101',
        'status'          => Project::STATUS_ACTIVE,
        'unix_group_name' => 'TestProject',
        'group_name'      => 'The Test Project',
    ];

    public function __construct()
    {
        $this->data['type'] = (string) TemplateSingleton::PROJECT;
    }

    public static function aProject(): self
    {
        return new self();
    }

    public function build(): Project
    {
        return new Project($this->data);
    }

    public function withId(int $id): self
    {
        $this->data['group_id'] = (string) $id;
        return $this;
    }

    public function withUnixName(string $unix_name): self
    {
        $this->data['unix_group_name'] = $unix_name;
        return $this;
    }

    public function withPublicName(string $name): self
    {
        $this->data['group_name'] = $name;
        return $this;
    }
}
