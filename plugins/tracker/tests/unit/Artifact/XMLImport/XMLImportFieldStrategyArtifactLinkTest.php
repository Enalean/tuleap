<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

use ColinODell\PsrTestLogger\TestLogger;
use PFUser;
use PHPUnit\Framework\MockObject\Stub;
use SimpleXMLElement;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink;
use Tracker_ArtifactLinkInfo;
use Tracker_XML_Importer_ArtifactImportedMapping;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueArtifactLinkTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLImportFieldStrategyArtifactLinkTest extends TestCase
{
    private ArtifactLinkField $field;
    private PFUser $submitted_by;
    private TestLogger $logger;
    private TypeDao&Stub $nature_dao;
    private Artifact $artifact;

    protected function setUp(): void
    {
        $this->field        = ArtifactLinkFieldBuilder::anArtifactLinkField(568)->build();
        $this->submitted_by = UserTestBuilder::buildWithDefaults();
        $this->logger       = new TestLogger();
        $this->nature_dao   = $this->createStub(TypeDao::class);
        $this->artifact     = ArtifactTestBuilder::anArtifact(412)->inTracker(TrackerTestBuilder::aTracker()->withId(888)->build())->build();
    }

    public function testItShouldWorkWithCompleteMapping(): void
    {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add(100, 1);
        $mapping->add(101, 2);
        $strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink(
            $mapping,
            $this->logger,
            $this->nature_dao
        );

        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value>101</value>
                    <value>100</value>
                  </field_change>');

        $this->nature_dao->method('getTypeByShortname')->willReturn([[]]);
        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('getValues')->willReturn([]);
        $this->artifact->setLastChangeset($changeset);

        $res          = $strategy->getFieldData($this->field, $xml_change, $this->submitted_by, $this->artifact, PostCreationContext::withNoConfig(false));
        $expected_res = ['new_values' => '2,1', 'removed_values' => [], 'types' => ['1' => '', '2' => '']];
        self::assertEquals($expected_res, $res);
    }

    public function testItShouldImportSystemNatures(): void
    {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add(100, 1);
        $mapping->add(101, 2);
        $strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink(
            $mapping,
            $this->logger,
            $this->nature_dao
        );

        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value nature="_is_child">101</value>
                    <value nature="_fixed_in">100</value>
                  </field_change>');

        $this->nature_dao->method('getTypeByShortname')->willReturn([[]]);
        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('getValues')->willReturn([]);
        $this->artifact->setLastChangeset($changeset);

        $res          = $strategy->getFieldData($this->field, $xml_change, $this->submitted_by, $this->artifact, PostCreationContext::withNoConfig(false));
        $expected_res = ['new_values' => '2,1', 'removed_values' => [], 'types' => ['1' => '_fixed_in', '2' => '_is_child']];

        self::assertEquals($expected_res, $res);
    }

    public function testItShouldWorkWithCompleteMappingAndNature(): void
    {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add(100, 1);
        $mapping->add(101, 2);
        $mapping->add(102, 3);

        $strategy   = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink(
            $mapping,
            $this->logger,
            $this->nature_dao
        );
        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value nature="toto">101</value>
                    <value nature="titi">100</value>
                    <value>102</value>
                  </field_change>');

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('getValues')->willReturn([]);
        $this->artifact->setLastChangeset($changeset);
        $this->nature_dao->method('getTypeByShortname')->willReturn([['titi']]);

        $res          = $strategy->getFieldData($this->field, $xml_change, $this->submitted_by, $this->artifact, PostCreationContext::withNoConfig(false));
        $expected_res = ['new_values' => '2,1,3', 'removed_values' => [], 'types' => ['1' => 'titi', '2' => 'toto', '3' => '']];
        self::assertEquals($expected_res, $res);
    }

    public function testItShouldLogWhenArtifactLinkReferenceIsBroken(): void
    {
        $mapping    = new Tracker_XML_Importer_ArtifactImportedMapping();
        $strategy   = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink(
            $mapping,
            $this->logger,
            $this->nature_dao
        );
        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value>101</value>
                  </field_change>');

        $this->nature_dao->method('getTypeByShortname')->willReturn([[]]);
        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('getValues')->willReturn([]);
        $this->artifact->setLastChangeset($changeset);

        $strategy->getFieldData($this->field, $xml_change, $this->submitted_by, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItShouldRemoveValuesWhenArtifactChildrenAreRemoved(): void
    {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add(200, 1);
        $mapping->add(101, 2);
        $mapping->add(102, 3);

        $strategy   = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink(
            $mapping,
            $this->logger,
            $this->nature_dao
        );
        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value nature="toto">200</value>
                  </field_change>');

        $changeset = ChangesetTestBuilder::aChangeset(123)->build();
        $changeset->setFieldValue($this->field, ChangesetValueArtifactLinkTestBuilder::aValue(
            1,
            $changeset,
            $this->field
        )->withLinks([
            1 => new Tracker_ArtifactLinkInfo(1, '', 101, 888, 123, null),
            2 => new Tracker_ArtifactLinkInfo(2, '', 101, 888, 123, null),
            3 => new Tracker_ArtifactLinkInfo(3, '', 101, 888, 123, null),
        ])->build());
        $this->artifact->setLastChangeset($changeset);

        $this->nature_dao->method('getTypeByShortname')->willReturn([['toto']]);
        $res          = $strategy->getFieldData($this->field, $xml_change, $this->submitted_by, $this->artifact, PostCreationContext::withNoConfig(false));
        $expected_res = ['new_values' => '1', 'removed_values' => [2 => 2, 3 => 3], 'types' => ['1' => 'toto']];

        self::assertEquals($expected_res, $res);
    }
}
