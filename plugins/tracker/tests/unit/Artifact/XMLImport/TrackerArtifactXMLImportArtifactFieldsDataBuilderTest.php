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

namespace Tuleap\Tracker\Artifact\XMLImport;

use Mockery;
use PFUser;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tracker;
use Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder;
use Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact;
use Tracker_FormElement_Field;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tracker_FormElementFactory;
use Tracker_XML_Importer_ArtifactImportedMapping;
use TrackerXmlFieldsMapping;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use User\XML\Import\IFindUserFromXMLReference;

final class TrackerArtifactXMLImportArtifactFieldsDataBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder
     */
    private $artifact_fields_data_builder;
    private PFUser $user;
    private Artifact $artifact;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $formelement_factory;
    private Tracker $tracker;
    private PostCreationContext $context;

    protected function setUp(): void
    {
        $this->user                = UserTestBuilder::anActiveUser()->build();
        $this->artifact            = ArtifactTestBuilder::anArtifact(1)->build();
        $this->tracker             = TrackerTestBuilder::aTracker()->withId(111)->build();
        $this->formelement_factory = Mockery::mock(Tracker_FormElementFactory::class);
        $user_finder               = Mockery::mock(IFindUserFromXMLReference::class);
        $files_importer            = Mockery::mock(Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact::class);
        $extraction_path           = '';
        $static_value_dao          = Mockery::mock(BindStaticValueDao::class);
        $logger                    = Mockery::mock(LoggerInterface::class);
        $xml_fields_mapping        = Mockery::mock(TrackerXmlFieldsMapping::class);
        $artifact_id_mapping       = Mockery::mock(Tracker_XML_Importer_ArtifactImportedMapping::class);
        $nature_dao                = Mockery::mock(TypeDao::class);
        $this->context             = PostCreationContext::withNoConfig(false);

        $this->artifact_fields_data_builder = Mockery::mock(
            Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder::class,
            [
                $this->formelement_factory,
                $user_finder,
                $this->tracker,
                $files_importer,
                $extraction_path,
                $static_value_dao,
                $logger,
                $xml_fields_mapping,
                $artifact_id_mapping,
                $nature_dao,
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();
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

        $field_1        = Mockery::mock(Tracker_FormElement_Field::class);
        $field_2        = Mockery::mock(Tracker_FormElement_Field::class);
        $external_field = Mockery::mock(Tracker_FormElement_Field::class);

        $field_1->shouldReceive('setTracker')->with($this->tracker);
        $field_2->shouldReceive('setTracker')->with($this->tracker);
        $external_field->shouldReceive('setTracker')->with($this->tracker);

        $field_1->shouldReceive('validateField')->andReturn(true);
        $field_2->shouldReceive('validateField')->andReturn(true);
        $external_field->shouldReceive('validateField')->andReturn(true);

        $field_1->shouldReceive('getId')->andReturn(1)->once();
        $field_2->shouldReceive('getId')->andReturn(2)->once();
        $external_field->shouldReceive('getId')->andReturn(3)->once();

        $this->formelement_factory->shouldReceive('getUsedFieldByName')
                                  ->withArgs([111, 'summary'])
                                  ->andReturn($field_1);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')
                                  ->withArgs([111, 'details'])
                                  ->andReturn($field_2);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')
                                  ->withArgs([111, 'steps'])
                                  ->andReturn($external_field);

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

        $this->formelement_factory->shouldNotReceive('getUsedFieldByName');

        self::assertEmpty($this->artifact_fields_data_builder->getFieldsData($xml, $this->user, $this->artifact, $this->context));
    }
}
