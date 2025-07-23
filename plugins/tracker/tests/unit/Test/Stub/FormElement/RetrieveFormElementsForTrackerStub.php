<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\FormElement;

use Tuleap\Tracker\Tracker;

final class RetrieveFormElementsForTrackerStub implements \Tuleap\Tracker\FormElement\RetrieveFormElementsForTracker
{
    public function __construct(private readonly array $elements)
    {
    }

    public static function withoutAnyElements(): self
    {
        return new self([]);
    }

    public static function with(\Tracker_FormElement $form_element, \Tracker_FormElement ...$other_elements): self
    {
        return new self([$form_element, ...$other_elements]);
    }

    #[\Override]
    public function getUsedFormElementForTracker(Tracker $tracker): array
    {
        return $this->elements;
    }
}
