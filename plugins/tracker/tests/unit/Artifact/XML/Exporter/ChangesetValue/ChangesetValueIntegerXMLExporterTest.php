<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue;

use SimpleXMLElement;
use Tracker_Artifact_ChangesetValue_Integer;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueIntegerTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetValueIntegerXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ChangesetValueIntegerXMLExporter $exporter;

    private SimpleXMLElement $changeset_xml;

    private SimpleXMLElement $artifact_xml;

    private Tracker_Artifact_ChangesetValue_Integer $changeset_value;

    private Tracker_FormElement_Field $field;

    protected function setUp(): void
    {
        $this->field         = IntFieldBuilder::anIntField(1001)->withName('story_points')->build();
        $this->exporter      = new ChangesetValueIntegerXMLExporter();
        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');

        $this->changeset_value = ChangesetValueIntegerTestBuilder::aValue(
            101,
            ChangesetTestBuilder::aChangeset(101)->build(),
            $this->field
        )->withValue(123)->build();
    }

    public function testItCreatesFieldChangeNodeInChangesetNode(): void
    {
        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            ArtifactTestBuilder::anArtifact(101)->build(),
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertEquals('int', (string) $field_change['type']);
        $this->assertEquals($this->field->getName(), (string) $field_change['field_name']);
        $this->assertEquals(123, (int) $field_change->value);
    }
}
