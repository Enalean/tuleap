<?php
/**
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\FormElement\Field;

use PFUser;
use Tuleap\Tracker\FormElement\Field\Burndown\BurndownField;
use Tuleap\Tracker\FormElement\Field\RetrieveBurndownField;
use Tuleap\Tracker\Tracker;

final readonly class RetrieveBurndownFieldStub implements RetrieveBurndownField
{
    private function __construct(private ?BurndownField $burndown_field)
    {
    }

    public static function withField(BurndownField $field): self
    {
        return new self($field);
    }

    public static function withoutField(): self
    {
        return new self(null);
    }

    #[\Override]
    public function getABurndownField(PFUser $user, Tracker $tracker): ?BurndownField
    {
        return $this->burndown_field;
    }
}
