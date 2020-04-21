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

use Tuleap\Tracker\XML\Exporter\ChangesetValue\ExternalExporterCollector;

final class Tracker_XML_Exporter_ChangesetValueXMLExporterVisitorTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var ExternalExporterCollector|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $collector;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_XML_Exporter_ChangesetValue_ChangesetValueUnknownXMLExporter
     */
    private $unknown_exporter;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset
     */
    private $changeset;

    /** @var Tracker_XML_Exporter_ChangesetValueXMLExporterVisitor */
    private $visitor;

    /** @var SimpleXMLElement */
    private $changeset_xml;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter */
    private $int_exporter;

    /** @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter */
    private $float_exporter;

    /** @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueArtifactLinkXMLExporter */
    private $artlink_exporter;

    protected function setUp(): void
    {
        $this->int_exporter     = \Mockery::spy(
            \Tracker_XML_Exporter_ChangesetValue_ChangesetValueIntegerXMLExporter::class
        );
        $this->float_exporter   = \Mockery::spy(
            \Tracker_XML_Exporter_ChangesetValue_ChangesetValueFloatXMLExporter::class
        );
        $this->artlink_exporter = \Mockery::spy(
            \Tracker_XML_Exporter_ChangesetValue_ChangesetValueArtifactLinkXMLExporter::class
        );
        $this->collector    = Mockery::mock(ExternalExporterCollector::class);
        $this->artifact_xml     = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml    = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');
        $this->unknown_exporter       = \Mockery::spy(
            \Tracker_XML_Exporter_ChangesetValue_ChangesetValueUnknownXMLExporter::class
        );
        $this->visitor          = new Tracker_XML_Exporter_ChangesetValueXMLExporterVisitor(
            \Mockery::spy(\Tracker_XML_Exporter_ChangesetValue_ChangesetValueDateXMLExporter::class),
            \Mockery::spy(\Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter::class),
            $this->float_exporter,
            $this->int_exporter,
            \Mockery::spy(\Tracker_XML_Exporter_ChangesetValue_ChangesetValueStringXMLExporter::class),
            \Mockery::spy(\Tracker_XML_Exporter_ChangesetValue_ChangesetValueTextXMLExporter::class),
            \Mockery::spy(\Tracker_XML_Exporter_ChangesetValue_ChangesetValuePermissionsOnArtifactXMLExporter::class),
            \Mockery::spy(\Tracker_XML_Exporter_ChangesetValue_ChangesetValueListXMLExporter::class),
            \Mockery::spy(\Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter::class),
            $this->artlink_exporter,
            \Mockery::spy(\Tuleap\Tracker\XML\Exporter\ChangesetValue\ChangesetValueComputedXMLExporter::class),
            $this->unknown_exporter,
            $this->collector
        );

        $this->changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
    }

    public function testItCallsTheIntegerExporterAccordinglyToTheTypeOfTheChangesetValue(): void
    {
        $int_changeset_value = new Tracker_Artifact_ChangesetValue_Integer(
            '1',
            $this->changeset,
            Mockery::mock(Tracker_FormElement_Field_Integer::class),
            false,
            false
        );

        $this->int_exporter->shouldReceive('export')->once();
        $this->float_exporter->shouldReceive('export')->never();
        $this->artlink_exporter->shouldReceive('export')->never();

        $this->visitor->export(
            $this->artifact_xml,
            $this->changeset_xml,
            \Mockery::spy(\Tracker_Artifact::class),
            $int_changeset_value
        );
    }

    public function testItCallsTheFloatExporterAccordinglyToTheTypeOfTheChangesetValue(): void
    {
        $float_changeset_value = new Tracker_Artifact_ChangesetValue_Float(
            '2',
            $this->changeset,
            Mockery::mock(Tracker_FormElement_Field_Float::class),
            false,
            false
        );

        $this->int_exporter->shouldReceive('export')->never();
        $this->float_exporter->shouldReceive('export')->Once();
        $this->artlink_exporter->shouldReceive('export')->never();

        $this->visitor->export(
            $this->artifact_xml,
            $this->changeset_xml,
            \Mockery::spy(\Tracker_Artifact::class),
            $float_changeset_value
        );
    }

    public function testItCallsTheArtifactLinkExporterAccordinglyToTheTypeOfTheChangesetValue(): void
    {
        $artlink_changeset_value = new Tracker_Artifact_ChangesetValue_ArtifactLink(
            '3',
            $this->changeset,
            Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class),
            false,
            [],
            []
        );

        $this->int_exporter->shouldReceive('export')->never();
        $this->float_exporter->shouldReceive('export')->never();
        $this->artlink_exporter->shouldReceive('export')->once();

        $this->visitor->export(
            $this->artifact_xml,
            $this->changeset_xml,
            \Mockery::spy(\Tracker_Artifact::class),
            $artlink_changeset_value
        );
    }

    public function testItCallsTheExternalExporterAccordinglyToTheTypeOfTheChangesetValue(): void
    {
        $external_changeset_value = $this->getExternalChangeset(
            $this->changeset,
            Mockery::mock(Tracker_FormElement_Field::class)
        );

        $this->int_exporter->shouldReceive('export')->never();
        $this->float_exporter->shouldReceive('export')->never();
        $this->artlink_exporter->shouldReceive('export')->never();
        $this->unknown_exporter->shouldReceive('export')->never();
        $external_exporter = $this->getExternalExporter();
        $this->collector->shouldReceive('collectExporter')->once()->andReturn($external_exporter);

        $this->visitor->export(
            $this->artifact_xml,
            $this->changeset_xml,
            \Mockery::spy(\Tracker_Artifact::class),
            $external_changeset_value
        );
    }

    public function testItCallsTheUnknownExporterAccordinglyToTheTypeOfTheChangesetValue(): void
    {
        $external_changeset_value = $this->getExternalChangeset(
            $this->changeset,
            Mockery::mock(Tracker_FormElement_Field::class)
        );

        $this->int_exporter->shouldReceive('export')->never();
        $this->float_exporter->shouldReceive('export')->never();
        $this->artlink_exporter->shouldReceive('export')->never();
        $this->unknown_exporter->shouldReceive('export')->once();
        $this->collector->shouldReceive('collectExporter')->once()->andReturn(null);

        $this->visitor->export(
            $this->artifact_xml,
            $this->changeset_xml,
            \Mockery::spy(\Tracker_Artifact::class),
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
                Tracker_Artifact $artifact,
                Tracker_Artifact_ChangesetValue $changeset_value
            ) {
            }
        };
    }
}
