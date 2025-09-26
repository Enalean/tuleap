<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_ChangesetValue_Float;
use Tracker_Artifact_ChangesetValue_Integer;
use Tracker_Artifact_ChangesetValueVisitor;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\ArtifactLink\ArtifactLinkChangesetValue;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValueXMLExporterVisitor;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\Float\FloatField;
use Tuleap\Tracker\FormElement\Field\Integer\IntegerField;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetValueXMLExporterVisitorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ExternalExporterCollector&MockObject $collector;

    private ChangesetValueUnknownXMLExporter&MockObject $unknown_exporter;

    private Tracker_Artifact_Changeset $changeset;

    private ChangesetValueXMLExporterVisitor $visitor;

    private SimpleXMLElement $changeset_xml;

    private SimpleXMLElement $artifact_xml;

    private ChangesetValueIntegerXMLExporter $int_exporter;

    private ChangesetValueFloatXMLExporter $float_exporter;

    private ChangesetValueArtifactLinkXMLExporter $artlink_exporter;

    #[\Override]
    protected function setUp(): void
    {
        $this->int_exporter     = $this->createMock(
            \Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueIntegerXMLExporter::class
        );
        $this->float_exporter   = $this->createMock(
            \Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueFloatXMLExporter::class
        );
        $this->artlink_exporter = $this->createMock(
            \Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueArtifactLinkXMLExporter::class
        );
        $this->collector        = $this->createMock(ExternalExporterCollector::class);
        $this->artifact_xml     = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml    = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');
        $this->unknown_exporter = $this->createMock(
            \Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueUnknownXMLExporter::class
        );
        $this->visitor          = new ChangesetValueXMLExporterVisitor(
            $this->createMock(\Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueDateXMLExporter::class),
            $this->createMock(\Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueFileXMLExporter::class),
            $this->float_exporter,
            $this->int_exporter,
            $this->createMock(\Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueStringXMLExporter::class),
            $this->createMock(\Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueTextXMLExporter::class),
            $this->createMock(\Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValuePermissionsOnArtifactXMLExporter::class),
            $this->createMock(\Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueListXMLExporter::class),
            $this->createMock(\Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueOpenListXMLExporter::class),
            $this->artlink_exporter,
            $this->createMock(\Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueComputedXMLExporter::class),
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
            $this->createMock(IntegerField::class),
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
            $int_changeset_value,
            ['values' => []]
        );
    }

    public function testItCallsTheFloatExporterAccordinglyToTheTypeOfTheChangesetValue(): void
    {
        $float_changeset_value = new Tracker_Artifact_ChangesetValue_Float(
            '2',
            $this->changeset,
            $this->createMock(FloatField::class),
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
            $float_changeset_value,
            ['values' => []]
        );
    }

    public function testItCallsTheArtifactLinkExporterAccordinglyToTheTypeOfTheChangesetValue(): void
    {
        $artlink_changeset_value = new ArtifactLinkChangesetValue(
            3,
            $this->changeset,
            $this->createMock(ArtifactLinkField::class),
            false,
            ['values' => []],
            ['values' => []]
        );

        $this->int_exporter->expects($this->never())->method('export');
        $this->float_exporter->expects($this->never())->method('export');
        $this->artlink_exporter->expects($this->once())->method('export');

        $this->visitor->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class),
            $artlink_changeset_value,
            ['values' => []]
        );
    }

    public function testItCallsTheExternalExporterAccordinglyToTheTypeOfTheChangesetValue(): void
    {
        $external_changeset_value = $this->getExternalChangeset(
            $this->changeset,
            $this->createMock(TrackerField::class)
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
            $external_changeset_value,
            ['values' => []]
        );
    }

    public function testItCallsTheUnknownExporterAccordinglyToTheTypeOfTheChangesetValue(): void
    {
        $external_changeset_value = $this->getExternalChangeset(
            $this->changeset,
            $this->createMock(TrackerField::class)
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
            $external_changeset_value,
            ['values' => []]
        );
    }

    /**
     * @return Tracker_Artifact_ChangesetValue
     */
    private function getExternalChangeset($changeset, $field)
    {
        return new class (1, $changeset, $field, false) extends Tracker_Artifact_ChangesetValue {
            #[\Override]
            public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor)
            {
                return $visitor->visitExternalField($this);
            }

            #[\Override]
            public function diff($changeset_value, $format = 'html', ?PFUser $user = null, $ignore_perms = false)
            {
            }

            #[\Override]
            public function nodiff($format = 'html')
            {
            }

            #[\Override]
            public function getRESTValue(PFUser $user)
            {
            }

            #[\Override]
            public function getFullRESTValue(PFUser $user)
            {
            }

            #[\Override]
            public function getValue()
            {
            }
        };
    }

    private function getExternalExporter(): ChangesetValueXMLExporter
    {
        return new class extends ChangesetValueXMLExporter {
            #[\Override]
            protected function getFieldChangeType()
            {
            }

            #[\Override]
            public function export(
                SimpleXMLElement $artifact_xml,
                SimpleXMLElement $changeset_xml,
                Artifact $artifact,
                Tracker_Artifact_ChangesetValue $changeset_value,
                array $value_mapping,
            ) {
            }
        };
    }
}
