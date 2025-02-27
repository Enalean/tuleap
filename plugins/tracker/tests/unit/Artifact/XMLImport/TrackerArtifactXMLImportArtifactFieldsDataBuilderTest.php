<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\XMLImport;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tracker;
use Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder;
use Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact;
use Tracker_FormElement_Field;
use Tracker_FormElementFactory;
use Tracker_XML_Importer_ArtifactImportedMapping;
use TrackerXmlFieldsMapping;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use User\XML\Import\IFindUserFromXMLReference;

final class TrackerArtifactXMLImportArtifactFieldsDataBuilderTest extends TestCase
{
    private Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder $artifact_fields_data_builder;
    private PFUser $user;
    private Artifact $artifact;
    private Tracker_FormElementFactory&MockObject $formelement_factory;
    private Tracker $tracker;
    private PostCreationContext $context;

    protected function setUp(): void
    {
        $this->user                = UserTestBuilder::anActiveUser()->build();
        $this->artifact            = ArtifactTestBuilder::anArtifact(1)->build();
        $this->tracker             = TrackerTestBuilder::aTracker()->withId(111)->build();
        $this->formelement_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->context             = PostCreationContext::withNoConfig(false);

        $this->artifact_fields_data_builder = new Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder(
            $this->formelement_factory,
            $this->createMock(IFindUserFromXMLReference::class),
            $this->tracker,
            $this->createMock(Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact::class),
            '',
            $this->createMock(BindStaticValueDao::class),
            new NullLogger(),
            $this->createMock(TrackerXmlFieldsMapping::class),
            $this->createMock(Tracker_XML_Importer_ArtifactImportedMapping::class),
            $this->createMock(TypeDao::class),
        );
    }

    public function testGetFieldsData(): void
    {
        $xml_data = '
        <changeset>
            <submitted_on/>
            <submitted_by/>
            <field_change field_name="summary" type="string">
              <value><![CDATA[Ceci n\'est pas un test]]></value>
            </field_change>
            <field_change field_name="details" type="text">
              <value format="text"><![CDATA[]]></value>
            </field_change>
            <external_field_change field_name="steps" type="text">
                <step>
                    <description format="text"><![CDATA[Yep]]></description>
                    <expected_results format="text"><![CDATA[Non]]></expected_results>
                </step>
            </external_field_change>
        </changeset>';

        $xml = new SimpleXMLElement($xml_data);

        $field_1        = $this->createMock(Tracker_FormElement_Field::class);
        $field_2        = $this->createMock(Tracker_FormElement_Field::class);
        $external_field = $this->createMock(Tracker_FormElement_Field::class);

        $field_1->method('setTracker')->with($this->tracker);
        $field_2->method('setTracker')->with($this->tracker);
        $external_field->method('setTracker')->with($this->tracker);

        $field_1->method('validateField')->willReturn(true);
        $field_2->method('validateField')->willReturn(true);
        $external_field->method('validateField')->willReturn(true);

        $field_1->expects(self::once())->method('getId')->willReturn(1);
        $field_2->expects(self::once())->method('getId')->willReturn(2);
        $external_field->expects(self::once())->method('getId')->willReturn(3);

        $this->formelement_factory->method('getUsedFieldByName')->with(111, self::anything())
            ->willReturnCallback(static fn(int $tracker_id, string $name) => match ($name) {
                'summary' => $field_1,
                'details' => $field_2,
                'steps'   => $external_field,
            });

        self::assertCount(3, $this->artifact_fields_data_builder->getFieldsData($xml, $this->user, $this->artifact, $this->context));
    }

    public function testFieldsDataIsEmptyIfNoFieldChangeProvided(): void
    {
        $xml_data = '
        <changeset>
            <submitted_on/>
            <submitted_by/>
        </changeset>';

        $xml = new SimpleXMLElement($xml_data);

        $this->formelement_factory->expects(self::never())->method('getUsedFieldByName');

        self::assertEmpty($this->artifact_fields_data_builder->getFieldsData($xml, $this->user, $this->artifact, $this->context));
    }
}
