<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue;

use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;

/**
 * @psalm-immutable
 */
final readonly class StepsDefinitionFieldWithValue implements FieldWithValue
{
    /**
     * @param list<StepValue> $steps
     */
    public function __construct(
        public string $label,
        public DisplayType $display_type,
        public array $steps,
    ) {
    }
}
