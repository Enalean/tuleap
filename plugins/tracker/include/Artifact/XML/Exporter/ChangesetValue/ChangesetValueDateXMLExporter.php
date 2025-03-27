<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue;

use DateTimeImmutable;
use SimpleXMLElement;
use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeDateBuilder;

class ChangesetValueDateXMLExporter extends ChangesetValueXMLExporter
{
    public function __construct(private readonly FieldChangeDateBuilder $field_change_date_builder)
    {
    }

    protected function getFieldChangeType(): string
    {
        return Tracker_FormElementFactory::FIELD_DATE_TYPE;
    }

    public function export(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Artifact $artifact,
        Tracker_Artifact_ChangesetValue $changeset_value,
    ): void {
        assert($changeset_value instanceof Tracker_Artifact_ChangesetValue_Date);

        $changeset_date_time = (new DateTimeImmutable())->setTimestamp((int) $changeset_value->getTimestamp());

        $this->field_change_date_builder->build(
            $changeset_xml,
            $changeset_value->getField()->getName(),
            $changeset_date_time
        );
    }
}
