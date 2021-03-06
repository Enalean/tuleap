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

namespace Tuleap\ProgramManagement\Domain;

/**
 * @psalm-immutable
 */
final class ProgramManagementProject
{
    private int $id;
    private string $name;
    private string $public_name;
    private string $url;

    public function __construct(int $id, string $name, string $public_name, string $url)
    {
        $this->id          = $id;
        $this->name        = $name;
        $this->public_name = $public_name;
        $this->url         = $url;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPublicName(): string
    {
        return $this->public_name;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
