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
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeTextBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueTextTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use XML_SimpleXMLCDATAFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetValueTextXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ChangesetValueTextXMLExporter $exporter;

    private SimpleXMLElement $changeset_xml;

    private SimpleXMLElement $artifact_xml;

    private Tracker_Artifact_ChangesetValue_Text $changeset_value;

    private Tracker_FormElement_Field $field;

    protected function setUp(): void
    {
        $this->field    = TextFieldBuilder::aTextField(1001)->withName('textarea')->build();
        $this->exporter = new ChangesetValueTextXMLExporter(
            new FieldChangeTextBuilder(
                new XML_SimpleXMLCDATAFactory()
            )
        );

        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');

        $changeset = ChangesetTestBuilder::aChangeset(102)->build();

        $this->changeset_value = ChangesetValueTextTestBuilder::aValue(101, $changeset, $this->field)
            ->withValue('<p>test</p>')
            ->withFormat(Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT)
            ->build();
    }

    public function testItCreatesTextNodeWithHTMLFormattedText(): void
    {
        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            ArtifactTestBuilder::anArtifact(101)->build(),
            $this->changeset_value,
            []
        );

        $field_change = $this->changeset_xml->field_change;

        $this->assertEquals('textarea', (string) $field_change['field_name']);
        $this->assertEquals('text', (string) $field_change['type']);

        $this->assertEquals('<p>test</p>', (string) $field_change->value);
        $this->assertEquals('html', (string) $field_change->value['format']);
    }
}
