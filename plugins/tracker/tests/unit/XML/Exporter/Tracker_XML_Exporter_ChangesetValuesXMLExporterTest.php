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
 */

declare(strict_types=1);

use Tuleap\Tracker\Artifact\Artifact;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_XML_Exporter_ChangesetValuesXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var Tracker_XML_Exporter_ChangesetValueXMLExporterVisitor */
    private $visitor;

    /** @var SimpleXMLElement */
    private $changeset_xml;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_Artifact_ChangesetValue */
    private $int_changeset_value;

    /** @var Tracker_Artifact_ChangesetValue */
    private $float_changeset_value;

    /** @var Tracker_XML_Exporter_ChangesetValuesXMLExporter */
    private $values_exporter;

    /** @var Artifact */
    private $artifact;
    private $values;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artifact_xml    = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml   = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');
        $this->visitor         = $this->createMock(\Tracker_XML_Exporter_ChangesetValueXMLExporterVisitor::class);
        $this->values_exporter = new Tracker_XML_Exporter_ChangesetValuesXMLExporter($this->visitor, false);

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);

        $this->int_changeset_value   = new Tracker_Artifact_ChangesetValue_Integer('*', $changeset, '*', '*', '*');
        $this->float_changeset_value = new Tracker_Artifact_ChangesetValue_Float('*', $changeset, '*', '*', '*');
        $this->values                = [
            $this->int_changeset_value,
            $this->float_changeset_value,
        ];

        $this->artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
    }

    public function testItCallsTheVisitorForEachChangesetValue(): void
    {
        $matcher = $this->exactly(2);
        $this->visitor
            ->expects($matcher)
            ->method('export')
            ->willReturnCallback(
                fn (
                    SimpleXMLElement $artifact_xml,
                    SimpleXMLElement $changeset_xml,
                    Artifact $artifact,
                    Tracker_Artifact_ChangesetValue $changeset_value,
                ) => match (true) {
                    $changeset_value === $this->int_changeset_value && $matcher->numberOfInvocations() === 1,
                    $changeset_value === $this->float_changeset_value && $matcher->numberOfInvocations() === 2 => true
                }
            );

        $this->values_exporter->exportSnapshot(
            $this->artifact_xml,
            $this->changeset_xml,
            $this->artifact,
            $this->values
        );
    }

    public function testItDoesNotCrashWhenExportingASnapshotIfAChangesetValueIsNull(): void
    {
        $matcher = $this->exactly(2);
        $this->visitor
            ->expects($matcher)
            ->method('export')
            ->willReturnCallback(
                fn (
                    SimpleXMLElement $artifact_xml,
                    SimpleXMLElement $changeset_xml,
                    Artifact $artifact,
                    Tracker_Artifact_ChangesetValue $changeset_value,
                ) => match (true) {
                    $changeset_value === $this->int_changeset_value && $matcher->numberOfInvocations() === 1,
                    $changeset_value === $this->float_changeset_value && $matcher->numberOfInvocations() === 2 => true
                }
            );

        $this->values_exporter->exportSnapshot(
            $this->artifact_xml,
            $this->changeset_xml,
            $this->artifact,
            array_merge([null], $this->values)
        );
    }

    public function testItDoesNotCrashWhenExportingChangedFieldsIfAChangesetValueIsNull(): void
    {
        $matcher = $this->exactly(2);
        $this->visitor
            ->expects($matcher)
            ->method('export')
            ->willReturnCallback(
                fn (
                    SimpleXMLElement $artifact_xml,
                    SimpleXMLElement $changeset_xml,
                    Artifact $artifact,
                    Tracker_Artifact_ChangesetValue $changeset_value,
                ) => match (true) {
                    $changeset_value === $this->int_changeset_value && $matcher->numberOfInvocations() === 1,
                    $changeset_value === $this->float_changeset_value && $matcher->numberOfInvocations() === 2 => true
                }
            );

        $this->values_exporter->exportChangedFields(
            $this->artifact_xml,
            $this->changeset_xml,
            $this->artifact,
            array_merge([null], $this->values)
        );
    }
}
