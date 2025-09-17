<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Tree\Create;

use Project;
use Tuleap\Event\Dispatchable;

class NewItemAlternativeCollector implements Dispatchable
{
    public const string NAME = 'newItemAlternativeCollector';

    /**
     * @var NewItemAlternativeSection[]
     */
    private array $sections = [];

    public function __construct(private Project $project)
    {
    }

    public function getSections(): array
    {
        return $this->sections;
    }

    public function addSection(NewItemAlternativeSection $section): void
    {
        $this->sections[] = $section;
    }

    public function getProject(): Project
    {
        return $this->project;
    }
}
