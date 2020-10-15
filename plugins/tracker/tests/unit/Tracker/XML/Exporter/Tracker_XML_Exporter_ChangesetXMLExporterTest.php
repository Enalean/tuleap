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
final class Tracker_XML_Exporter_ChangesetXMLExporterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var Tracker_XML_Exporter_ChangesetXMLExporter */
    private $exporter;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_XML_Exporter_ChangesetValuesXMLExporter */
    private $values_exporter;

    /** @var Tracker_Artifact_ChangesetValue */
    private $values;

    /** @var Artifact */
    private $artifact;
    private $user_manager;

    /** @var UserXMLExporter */
    private $user_xml_exporter;
    /**
     * @var Tracker_Artifact_ChangesetValue_Integer
     */
    private $int_changeset_value;
    /**
     * @var Tracker_Artifact_ChangesetValue_Float
     */
    private $float_changeset_value;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset
     */
    private $changeset;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset_Comment
     */
    private $comment;

    protected function setUp(): void
    {
        $this->user_manager      = \Mockery::spy(\UserManager::class);
        $this->user_xml_exporter = \Mockery::mock(
            \UserXMLExporter::class,
            [$this->user_manager, Mockery::spy(UserXMLExportedCollection::class)]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->artifact_xml      = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->values_exporter   = \Mockery::spy(\Tracker_XML_Exporter_ChangesetValuesXMLExporter::class);
        $this->exporter          = new Tracker_XML_Exporter_ChangesetXMLExporter(
            $this->values_exporter,
            $this->user_xml_exporter
        );

        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);

        $this->int_changeset_value   = new Tracker_Artifact_ChangesetValue_Integer('*', $changeset, '*', '*', '*');
        $this->float_changeset_value = new Tracker_Artifact_ChangesetValue_Float('*', $changeset, '*', '*', '*');
        $this->values = [
            $this->int_changeset_value,
            $this->float_changeset_value
        ];

        $this->artifact  = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);
        $this->comment   = \Mockery::spy(\Tracker_Artifact_Changeset_Comment::class);

        $this->changeset->shouldReceive(
            [
                'getValues'      => $this->values,
                'getArtifact'    => $this->artifact,
                'getComment'     => $this->comment,
                'getSubmittedBy' => 101,
                'getSubmittedOn' => 1234567890,
                'getId'          => 123,
            ]
        );
        $this->changeset->shouldReceive('forceFetchAllValues');
    }

    public function testItAppendsChangesetNodeToArtifactNode(): void
    {
        $this->exporter->exportWithoutComments($this->artifact_xml, $this->changeset);

        $this->assertCount(1, $this->artifact_xml->changeset);
        $this->assertCount(1, $this->artifact_xml->changeset->submitted_by);
        $this->assertCount(1, $this->artifact_xml->changeset->submitted_on);
    }

    public function testItDelegatesTheExportOfValues(): void
    {
        $this->values_exporter->shouldReceive('exportSnapshot')->with($this->artifact_xml, \Mockery::any(), $this->artifact, $this->values)->once();
        $this->comment->shouldReceive('exportToXML')->never();

        $this->exporter->exportWithoutComments($this->artifact_xml, $this->changeset);
    }

    public function testItExportsTheComments(): void
    {
        $user = new PFUser([
            'user_id' => 101,
            'language_id' => 'en',
            'user_name' => 'user_01',
            'ldap_id' => 'ldap_01'
        ]);
        $this->user_manager->shouldReceive('getUserById')->with(101)->andReturn($user);

        $this->values_exporter->shouldReceive('exportChangedFields')->with($this->artifact_xml, \Mockery::any(), $this->artifact, $this->values)->once();
        $this->comment->shouldReceive('exportToXML')->once();

        $this->exporter->exportFullHistory($this->artifact_xml, $this->changeset);
    }

    public function testItExportsTheIdOfTheChangeset(): void
    {
        $user = new PFUser([
            'user_id' => 101,
            'language_id' => 'en',
            'user_name' => 'user_01',
            'ldap_id' => 'ldap_01'
        ]);
        $this->user_manager->shouldReceive('getUserById')->with(101)->andReturn($user);

        $this->exporter->exportFullHistory($this->artifact_xml, $this->changeset);

        $this->assertEquals('CHANGESET_123', (string) $this->artifact_xml->changeset['id']);
    }

    public function testItExportsAnonUser(): void
    {
        $this->user_xml_exporter->shouldReceive('exportUserByMail')->once();

        $changeset = Mockery::spy(\Tracker_Artifact_Changeset::class)->shouldReceive('getValues')->andReturns([])->getMock();
        $changeset->shouldReceive('getSubmittedBy')->andReturns(null);
        $changeset->shouldReceive('getEmail')->andReturns('veloc@dino.com');
        $changeset->shouldReceive('getArtifact')->andReturns($this->artifact);
        $this->exporter->exportFullHistory($this->artifact_xml, $changeset);
    }

    public function testItRemovesNullValuesInChangesetValues(): void
    {
        $value = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);

        $this->values_exporter->shouldReceive('exportChangedFields')->with(\Mockery::any(), \Mockery::any(), \Mockery::any(), [101 => $value])->once();

        $changeset = Mockery::spy(\Tracker_Artifact_Changeset::class)->shouldReceive('getValues')->andReturns([
            101 => $value,
            102 => null
        ])->getMock();

        $changeset->shouldReceive('getArtifact')->andReturns($this->artifact);

        $this->exporter->exportFullHistory($this->artifact_xml, $changeset);
    }
}
