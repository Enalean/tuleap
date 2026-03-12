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

namespace Tuleap\Tracker\Test\Stub\FormElement;

use Tuleap\Tracker\FormElement\RetrieveFormElementByName;
use Tuleap\Tracker\FormElement\TrackerFormElement;

/**
 * @psalm-immutable
 */
final readonly class RetrieveFormElementByNameStub implements RetrieveFormElementByName
{
    /**
     * @param array<string, TrackerFormElement> $form_elements
     */
    private function __construct(private array $form_elements)
    {
    }

    public static function withFormElements(TrackerFormElement $form_element, TrackerFormElement ...$other_elements): self
    {
        $form_elements = [];
        foreach ([$form_element, ...$other_elements] as $field) {
            $form_elements[$field->getName()] = $field;
        }

        return new self($form_elements);
    }

    public function getFormElementByName(int $tracker_id, string $name): ?TrackerFormElement
    {
        return $this->form_elements[$name] ?? null;
    }
}
