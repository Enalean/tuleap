<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use Psr\Log\LoggerInterface;
use Tracker_FormElement_Field_List_BindValue;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ListFields\RetrieveMatchingBindValueByDuckTyping;

final class CanStaticFieldValuesBeFullyMovedVerifier implements VerifyStaticFieldValuesCanBeFullyMoved
{
    public function __construct(private readonly RetrieveMatchingBindValueByDuckTyping $retrieve_matching_bind_value_by_duck_typing)
    {
    }

    #[\Override]
    public function canAllStaticFieldValuesBeMoved(
        \Tuleap\Tracker\FormElement\Field\ListField $source_field,
        \Tuleap\Tracker\FormElement\Field\ListField $destination_field,
        Artifact $artifact,
        LoggerInterface $logger,
    ): bool {
        $last_changeset_value = $source_field->getLastChangesetValue($artifact);
        if (! $last_changeset_value instanceof \Tracker_Artifact_ChangesetValue_List) {
            $logger->error(sprintf('Last changeset value is not a list for field #%d', $source_field->getId()));
            return false;
        }

        $list_field_values = array_values($last_changeset_value->getListValues());

        foreach ($list_field_values as $value) {
            assert($value instanceof Tracker_FormElement_Field_List_BindValue);
            $list_bind_value = $this->retrieve_matching_bind_value_by_duck_typing->getMatchingBindValueByDuckTyping(
                $value,
                $destination_field
            );

            if ($list_bind_value === null) {
                $logger->debug(sprintf('Value %s not found in destination field #%d (%s)', $value->getId(), $destination_field->getId(), $destination_field->getName()));
                return false;
            }
        }

        return true;
    }
}
