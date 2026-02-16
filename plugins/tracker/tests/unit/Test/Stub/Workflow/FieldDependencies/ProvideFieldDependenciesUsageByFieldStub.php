<?php
/**
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

namespace Tuleap\Tracker\Test\Stub\Workflow\FieldDependencies;

use Override;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Workflow\FieldDependencies\ProvideFieldDependenciesUsageByField;

final readonly class ProvideFieldDependenciesUsageByFieldStub implements ProvideFieldDependenciesUsageByField
{
    private function __construct(private bool $has_field_dependencies)
    {
    }

    public static function withFieldDependencies(): self
    {
        return new self(true);
    }

    public static function withoutFieldDependencies(): self
    {
        return new self(false);
    }

    #[Override]
    public function isFieldUsedInFieldDependencies(TrackerField $field): bool
    {
        return $this->has_field_dependencies;
    }
}
