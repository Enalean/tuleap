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

namespace Tuleap\Artidoc\Document\Field\Date;

use DateTimeImmutable;
use DateTimeZone;
use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Date;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\DateFieldWithValue;
use Tuleap\Option\Option;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\FormElement\Field\LastUpdateDate\LastUpdateDateField;
use Tuleap\Tracker\FormElement\Field\SubmittedOn\SubmittedOnField;

final readonly class DateFieldWithValueBuilder
{
    public function __construct(
        private PFUser $current_user,
    ) {
    }

    public function buildDateFieldWithValue(
        ConfiguredField $configured_field,
        Tracker_Artifact_Changeset $changeset,
        ?Tracker_Artifact_ChangesetValue_Date $changeset_value,
    ): DateFieldWithValue {
        assert($configured_field->field instanceof DateField);
        return new DateFieldWithValue(
            $configured_field->field->getLabel(),
            $configured_field->display_type,
            $this->buildValue($configured_field, $changeset, $changeset_value),
            $configured_field->field->isTimeDisplayed(),
        );
    }

    /**
     * @return Option<DateTimeImmutable>
     */
    private function buildValue(
        ConfiguredField $configured_field,
        Tracker_Artifact_Changeset $changeset,
        ?Tracker_Artifact_ChangesetValue_Date $changeset_value,
    ): Option {
        if ($configured_field->field instanceof LastUpdateDateField) {
            $timestamp = (int) $changeset->getSubmittedOn();
        } elseif ($configured_field->field instanceof SubmittedOnField) {
            $timestamp = $changeset->getArtifact()->getSubmittedOn();
        } else {
            $timestamp = $changeset_value?->getTimestamp();
        }

        if ($timestamp === null) {
            return Option::nothing(DateTimeImmutable::class);
        }

        $date = DateTimeImmutable::createFromTimestamp($timestamp)->setTimezone(new DateTimeZone(TimezoneRetriever::getUserTimezone($this->current_user)));
        assert($date instanceof DateTimeImmutable);
        return Option::fromValue($date);
    }
}
