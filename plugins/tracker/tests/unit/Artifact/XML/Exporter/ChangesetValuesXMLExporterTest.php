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

namespace Tuleap\Tracker\Artifact\XML\Exporter;

use SimpleXMLElement;
use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_ChangesetValue_Float;
use Tracker_Artifact_ChangesetValue_Integer;
use Tuleap\Tracker\Artifact\Artifact;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetValuesXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ChangesetValueXMLExporterVisitor $visitor;

    private SimpleXMLElement $changeset_xml;

    private SimpleXMLElement $artifact_xml;

    private Tracker_Artifact_ChangesetValue $int_changeset_value;

    private Tracker_Artifact_ChangesetValue $float_changeset_value;

    private ChangesetValuesXMLExporter $values_exporter;

    private Artifact $artifact;
    private array $values;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artifact_xml    = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml   = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');
        $this->visitor         = $this->createMock(\Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValueXMLExporterVisitor::class);
        $this->values_exporter = new ChangesetValuesXMLExporter($this->visitor, false);

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
                fn(
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
            $this->values,
            []
        );
    }

    public function testItDoesNotCrashWhenExportingASnapshotIfAChangesetValueIsNull(): void
    {
        $matcher = $this->exactly(2);
        $this->visitor
            ->expects($matcher)
            ->method('export')
            ->willReturnCallback(
                fn(
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
            array_merge([null], $this->values),
            []
        );
    }

    public function testItDoesNotCrashWhenExportingChangedFieldsIfAChangesetValueIsNull(): void
    {
        $matcher = $this->exactly(2);
        $this->visitor
            ->expects($matcher)
            ->method('export')
            ->willReturnCallback(
                fn(
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
            array_merge([null], $this->values),
            []
        );
    }
}
