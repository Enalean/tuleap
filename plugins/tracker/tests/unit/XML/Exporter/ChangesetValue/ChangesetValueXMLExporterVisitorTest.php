<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\XML\Exporter\ChangesetValue\ExternalExporterCollector;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_XML_Exporter_ChangesetValueXMLExporterVisitorTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    private ExternalExporterCollector&MockObject $collector;

    private Tracker_XML_Exporter_ChangesetValue_ChangesetValueUnknownXMLExporter&MockObject $unknown_exporter;

    private Tracker_Artifact_Changeset $changeset;

    private Tracker_XML_Exporter_ChangesetValueXMLExporterVisitor $visitor;

    private SimpleXMLElement $changeset_xml;

    private SimpleXMLElement $artifact_xml;

    private Tracker_XML_Exporter_ChangesetValue_ChangesetValueIntegerXMLExporter $int_exporter;

    private Tracker_XML_Exporter_ChangesetValue_ChangesetValueFloatXMLExporter $float_exporter;

    private Tracker_XML_Exporter_ChangesetValue_ChangesetValueArtifactLinkXMLExporter $artlink_exporter;

    protected function setUp(): void
    {
        $this->int_exporter     = $this->createMock(
            \Tracker_XML_Exporter_ChangesetValue_ChangesetValueIntegerXMLExporter::class
        );
        $this->float_exporter   = $this->createMock(
            \Tracker_XML_Exporter_ChangesetValue_ChangesetValueFloatXMLExporter::class
        );
        $this->artlink_exporter = $this->createMock(
            \Tracker_XML_Exporter_ChangesetValue_ChangesetValueArtifactLinkXMLExporter::class
        );
        $this->collector        = $this->createMock(ExternalExporterCollector::class);
        $this->artifact_xml     = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml    = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');
        $this->unknown_exporter = $this->createMock(
            \Tracker_XML_Exporter_ChangesetValue_ChangesetValueUnknownXMLExporter::class
        );
        $this->visitor          = new Tracker_XML_Exporter_ChangesetValueXMLExporterVisitor(
            $this->createMock(\Tracker_XML_Exporter_ChangesetValue_ChangesetValueDateXMLExporter::class),
            $this->createMock(\Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter::class),
            $this->float_exporter,
            $this->int_exporter,
            $this->createMock(\Tracker_XML_Exporter_ChangesetValue_ChangesetValueStringXMLExporter::class),
            $this->createMock(\Tracker_XML_Exporter_ChangesetValue_ChangesetValueTextXMLExporter::class),
            $this->createMock(\Tracker_XML_Exporter_ChangesetValue_ChangesetValuePermissionsOnArtifactXMLExporter::class),
            $this->createMock(\Tracker_XML_Exporter_ChangesetValue_ChangesetValueListXMLExporter::class),
            $this->createMock(\Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter::class),
            $this->artlink_exporter,
            $this->createMock(\Tuleap\Tracker\XML\Exporter\ChangesetValue\ChangesetValueComputedXMLExporter::class),
            $this->unknown_exporter,
            $this->collector
        );

        $this->changeset = ChangesetTestBuilder::aChangeset(101)->build();
    }

    public function testItCallsTheIntegerExporterAccordinglyToTheTypeOfTheChangesetValue(): void
    {
        $int_changeset_value = new Tracker_Artifact_ChangesetValue_Integer(
            '1',
            $this->changeset,
            $this->createMock(Tracker_FormElement_Field_Integer::class),
            false,
            false
        );

        $this->int_exporter->expects($this->once())->method('export');
        $this->float_exporter->expects($this->never())->method('export');
        $this->artlink_exporter->expects($this->never())->method('export');

        $this->visitor->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class),
            $int_changeset_value
        );
    }

    public function testItCallsTheFloatExporterAccordinglyToTheTypeOfTheChangesetValue(): void
    {
        $float_changeset_value = new Tracker_Artifact_ChangesetValue_Float(
            '2',
            $this->changeset,
            $this->createMock(Tracker_FormElement_Field_Float::class),
            false,
            false
        );

        $this->int_exporter->expects($this->never())->method('export');
        $this->float_exporter->expects($this->once())->method('export');
        $this->artlink_exporter->expects($this->never())->method('export');

        $this->visitor->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class),
            $float_changeset_value
        );
    }

    public function testItCallsTheArtifactLinkExporterAccordinglyToTheTypeOfTheChangesetValue(): void
    {
        $artlink_changeset_value = new Tracker_Artifact_ChangesetValue_ArtifactLink(
            '3',
            $this->changeset,
            $this->createMock(Tracker_FormElement_Field_ArtifactLink::class),
            false,
            [],
            []
        );

        $this->int_exporter->expects($this->never())->method('export');
        $this->float_exporter->expects($this->never())->method('export');
        $this->artlink_exporter->expects($this->once())->method('export');

        $this->visitor->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class),
            $artlink_changeset_value
        );
    }

    public function testItCallsTheExternalExporterAccordinglyToTheTypeOfTheChangesetValue(): void
    {
        $external_changeset_value = $this->getExternalChangeset(
            $this->changeset,
            $this->createMock(Tracker_FormElement_Field::class)
        );

        $this->int_exporter->expects($this->never())->method('export');
        $this->float_exporter->expects($this->never())->method('export');
        $this->artlink_exporter->expects($this->never())->method('export');
        $this->unknown_exporter->expects($this->never())->method('export');
        $external_exporter = $this->getExternalExporter();
        $this->collector->expects($this->once())->method('collectExporter')->willReturn($external_exporter);

        $this->visitor->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class),
            $external_changeset_value
        );
    }

    public function testItCallsTheUnknownExporterAccordinglyToTheTypeOfTheChangesetValue(): void
    {
        $external_changeset_value = $this->getExternalChangeset(
            $this->changeset,
            $this->createMock(Tracker_FormElement_Field::class)
        );

        $this->int_exporter->expects($this->never())->method('export');
        $this->float_exporter->expects($this->never())->method('export');
        $this->artlink_exporter->expects($this->never())->method('export');
        $this->unknown_exporter->expects($this->once())->method('export');
        $this->collector->expects($this->once())->method('collectExporter')->willReturn(null);

        $this->visitor->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class),
            $external_changeset_value
        );
    }

    /**
     * @return Tracker_Artifact_ChangesetValue
     */
    private function getExternalChangeset($changeset, $field)
    {
        return new class (1, $changeset, $field, false) extends Tracker_Artifact_ChangesetValue {
            public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor)
            {
                return $visitor->visitExternalField($this);
            }

            public function diff($changeset_value, $format = 'html', ?PFUser $user = null, $ignore_perms = false)
            {
            }

            public function nodiff($format = 'html')
            {
            }

            public function getRESTValue(PFUser $user)
            {
            }

            public function getFullRESTValue(PFUser $user)
            {
            }

            public function getValue()
            {
            }
        };
    }

    private function getExternalExporter(): Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter
    {
        return new class extends Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter{
            protected function getFieldChangeType()
            {
            }

            public function export(
                SimpleXMLElement $artifact_xml,
                SimpleXMLElement $changeset_xml,
                Artifact $artifact,
                Tracker_Artifact_ChangesetValue $changeset_value,
            ) {
            }
        };
    }
}
