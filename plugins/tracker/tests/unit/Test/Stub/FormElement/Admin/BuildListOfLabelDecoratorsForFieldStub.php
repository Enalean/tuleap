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

namespace Tuleap\Tracker\Test\Stub\FormElement\Admin;

use Override;
use Tuleap\Tracker\FormElement\Admin\BuildListOfLabelDecoratorsForField;
use Tuleap\Tracker\FormElement\Admin\LabelDecorator;
use Tuleap\Tracker\FormElement\TrackerFormElement;

final class BuildListOfLabelDecoratorsForFieldStub implements BuildListOfLabelDecoratorsForField
{
    /**
     * @param array<int, LabelDecorator[]> $label_decorators
     */
    private array $label_decorators = [];

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    public function withDecorator(TrackerFormElement $form_element, LabelDecorator $label_decorator): self
    {
        if (! isset($this->label_decorators[$form_element->getId()])) {
            $this->label_decorators[$form_element->getId()] = [];
        }

        $this->label_decorators[$form_element->getId()][] = $label_decorator;

        return $this;
    }

    #[Override]
    public function getLabelDecorators(TrackerFormElement $form_element): array
    {
        return $this->label_decorators[$form_element->getId()] ?? [];
    }
}
