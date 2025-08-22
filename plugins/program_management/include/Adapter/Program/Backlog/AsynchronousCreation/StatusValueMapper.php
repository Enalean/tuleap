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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BindValueIdentifierProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MapStatusByValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BindValueIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BindValueLabel;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StatusValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\NoDuckTypedMatchingValueException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReference;
use Tuleap\Tracker\FormElement\Field\ListField;

final class StatusValueMapper implements MapStatusByValue
{
    public function __construct(private \Tracker_FormElementFactory $form_element_factory)
    {
    }

    public function mapStatusValueByDuckTyping(StatusValue $source_value, StatusFieldReference $target_field): array
    {
        $matching_values = [];
        $status_field    = $this->form_element_factory->getFieldById($target_field->getId());
        assert($status_field instanceof ListField);
        foreach ($source_value->getListValues() as $label) {
            $matching_value = $this->getMatchingValueByDuckTyping($label, $status_field);
            if ($matching_value === null) {
                throw new NoDuckTypedMatchingValueException(
                    $label->getLabel(),
                    $target_field->getId(),
                    $status_field->getTrackerId()
                );
            }
            $matching_values[] = $matching_value;
        }

        return $matching_values;
    }

    private function getMatchingValueByDuckTyping(
        BindValueLabel $source_label,
        \Tuleap\Tracker\FormElement\Field\ListField $target_field,
    ): ?BindValueIdentifier {
        $lowercase_label = strtolower($source_label->getLabel());
        foreach ($target_field->getBind()->getAllValues() as $target_value) {
            if ($lowercase_label === strtolower($target_value->getLabel())) {
                return BindValueIdentifierProxy::fromListBindValue($target_value);
            }
        }
        return null;
    }
}
