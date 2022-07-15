<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Test\Stubs;

use Tuleap\Reference\ReferenceInstance;

final class ExtractReferencesStub implements \Tuleap\Reference\ExtractReferences
{
    /**
     * @param list<ReferenceInstance> $references
     */
    private function __construct(private array $references)
    {
    }

    /**
     * @no-named-arguments
     */
    public static function withReferenceInstances(
        ReferenceInstance $instance,
        ReferenceInstance ...$other_instances,
    ): self {
        return new self([$instance, ...$other_instances]);
    }

    public static function withNoReference(): self
    {
        return new self([]);
    }

    public function extractReferences(string $html, int $group_id): array
    {
        return $this->references;
    }
}
