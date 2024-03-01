<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

namespace Tuleap\Tracker;

use Mockery;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_ArtifactLink;
use Tracker_ArtifactLinkInfo;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueArtifactLinksFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactReferenceWithType;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;

class Tracker_Artifact_ChangesetValue_ArtifactLinkTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_ChangesetValue_ArtifactLink
     */
    private function getChangesetValueArtifactLink(array $artifact_links, array $reverse_artifact_links)
    {
        $field = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getTracker')->andReturn(Mockery::mock(Tracker::class));

        $value = Mockery::mock(
            Tracker_Artifact_ChangesetValue_ArtifactLink::class,
            [
                111,
                Mockery::mock(Tracker_Artifact_Changeset::class),
                $field,
                false,
                $artifact_links,
                $reverse_artifact_links,
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();
        $value->shouldReceive('getTypePresenterFactory')->andReturn(Mockery::mock(TypePresenterFactory::class));

        return $value;
    }

    public function testNoDiffForChangesetValueArtifactLinkDiff(): void
    {
        $artlink_info_123 = Mockery::mock(
            Tracker_ArtifactLinkInfo::class,
            [
                'getArtifactId' => '123',
                'getKeyword'    => 'bug',
                'getUrl'        => '<a>bug #123</a>',
                '__toString'    => 'bug #123',
                'getLabel'      => 'bug #123',
                'userCanView'   => true,
            ]
        );

        $artlink_info_321 = Mockery::mock(
            Tracker_ArtifactLinkInfo::class,
            [
                'getArtifactId' => '321',
                'getKeyword'    => 'task',
                'getUrl'        => '<a>task #321</a>',
                '__toString'    => 'task #321',
                'getLabel'      => 'task #321',
                'userCanView'   => true,
            ]
        );

        $artlink_info_999 = Mockery::mock(
            Tracker_ArtifactLinkInfo::class,
            [
                'getArtifactId' => '999',
                'getKeyword'    => 'story',
                'getUrl'        => '<a>story #999</a>',
                '__toString'    => 'story #999',
                'getLabel'      => 'story #999',
                'userCanView'   => true,
            ]
        );

        $art_links_1 = ['123' => $artlink_info_123, '321' => $artlink_info_321, '999' => $artlink_info_999];
        $art_links_2 = ['999' => $artlink_info_999, '123' => $artlink_info_123, '321' => $artlink_info_321];
        $list_1      = $this->getChangesetValueArtifactLink($art_links_1, []);
        $list_2      = $this->getChangesetValueArtifactLink($art_links_2, []);
        $this->assertEquals(null, $list_1->diff($list_2));
        $this->assertEquals(null, $list_2->diff($list_1));
    }

    public function testItHasNoChangesWhenNoNewValues(): void
    {
        $old_values      = [];
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        \assert($changeset_value instanceof \Mockery\Mock || $changeset_value instanceof Tracker_Artifact_ChangesetValue_ArtifactLink);

        $changeset_value->shouldReceive('getValue')->andReturn($old_values);
        $new_value = ['list_of_artifactlinkinfo' => ''];
        $this->assertFalse($changeset_value->hasChanges($new_value));
    }

    public function testItHasNoChangesWhenSameValues(): void
    {
        $old_values      = [
            1 => Mockery::spy(Tracker_ArtifactLinkInfo::class),
            2 => Mockery::spy(Tracker_ArtifactLinkInfo::class),
        ];
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        \assert($changeset_value instanceof \Mockery\Mock || $changeset_value instanceof Tracker_Artifact_ChangesetValue_ArtifactLink);

        $changeset_value->shouldReceive('getValue')->andReturn($old_values);

        $new_value = [
            'list_of_artifactlinkinfo' => [
                2 => Mockery::spy(Tracker_ArtifactLinkInfo::class),
                1 => Mockery::spy(Tracker_ArtifactLinkInfo::class),
            ],
        ];

        $this->assertFalse($changeset_value->hasChanges($new_value));
    }

    public function testItHasChangesWhenLinksAreAdded(): void
    {
        $old_values      = [
            1 => Mockery::mock(Tracker_ArtifactLinkInfo::class),
            2 => Mockery::mock(Tracker_ArtifactLinkInfo::class),
        ];
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        \assert($changeset_value instanceof \Mockery\Mock || $changeset_value instanceof Tracker_Artifact_ChangesetValue_ArtifactLink);

        $changeset_value->shouldReceive('getValue')->andReturn($old_values);

        $new_value = [
            'list_of_artifactlinkinfo' => [
                1 => Mockery::mock(Tracker_ArtifactLinkInfo::class),
                2 => Mockery::mock(Tracker_ArtifactLinkInfo::class),
                3 => Mockery::mock(Tracker_ArtifactLinkInfo::class),
            ],
        ];

        $this->assertTrue($changeset_value->hasChanges($new_value));
    }

    public function testItHasChangesWhenLinksAreRemoved(): void
    {
        $old_values      = [
            1 => Mockery::mock(Tracker_ArtifactLinkInfo::class),
            2 => Mockery::mock(Tracker_ArtifactLinkInfo::class),
        ];
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        \assert($changeset_value instanceof \Mockery\Mock || $changeset_value instanceof Tracker_Artifact_ChangesetValue_ArtifactLink);

        $changeset_value->shouldReceive('getValue')->andReturn($old_values);

        $new_value = [
            'list_of_artifactlinkinfo' => [
                1 => Mockery::mock(Tracker_ArtifactLinkInfo::class),
            ],
        ];

        $this->assertTrue($changeset_value->hasChanges($new_value));
    }

    public function testItHasChangesWhenTypeIsChanged(): void
    {
        $old_values      = [
            1 => Mockery::mock(Tracker_ArtifactLinkInfo::class, ['getType' => '_is_child']),
            2 => Mockery::mock(Tracker_ArtifactLinkInfo::class, ['getType' => '']),
        ];
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        \assert($changeset_value instanceof \Mockery\Mock || $changeset_value instanceof Tracker_Artifact_ChangesetValue_ArtifactLink);

        $changeset_value->shouldReceive('getValue')->andReturn($old_values);

        $new_value = [
            'list_of_artifactlinkinfo' => [
                1 => Mockery::mock(Tracker_ArtifactLinkInfo::class, ['getType' => 'fixed_in']),
                2 => Mockery::mock(Tracker_ArtifactLinkInfo::class, ['getType' => '']),
            ],
        ];

        $this->assertTrue($changeset_value->hasChanges($new_value));
    }

    public function testBuildsFieldValueRESTRepresentation(): void
    {
        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->withLabel('Label')->build();

        $artifact_direct_link  = $this->buildArtifact(12);
        $artifact_reverse_link = $this->buildArtifact(13);

        $changeset_value_artifact_link = new Tracker_Artifact_ChangesetValue_ArtifactLink(
            1,
            $this->createStub(Tracker_Artifact_Changeset::class),
            $field,
            false,
            [$artifact_direct_link->getId() => $this->buildTrackerLinkInfo($artifact_direct_link, 'link_type')],
            [$artifact_reverse_link->getId() => $this->buildTrackerLinkInfo($artifact_reverse_link, null)],
        );

        $representation = $changeset_value_artifact_link->getFullRESTValue(UserTestBuilder::anActiveUser()->build());

        $expected_representation = new ArtifactFieldValueArtifactLinksFullRepresentation();
        $expected_representation->build(
            1,
            'art_link',
            'Label',
            [ArtifactReferenceWithType::buildWithType($artifact_direct_link, 'link_type')],
            [ArtifactReferenceWithType::buildWithType($artifact_reverse_link, null)],
        );


        self::assertEquals($expected_representation, $representation);
    }

    private function buildArtifact(int $id): Artifact
    {
        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getId')->willReturn($id);
        $tracker = $this->createStub(Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('getName')->willReturn('tracker_name');
        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('userCanView')->willReturn(true);

        return $artifact;
    }

    private function buildTrackerLinkInfo(Artifact $artifact, ?string $type): Tracker_ArtifactLinkInfo
    {
        return new class ($artifact, $type) extends Tracker_ArtifactLinkInfo {
            public function __construct(private Artifact $artifact, ?string $type)
            {
                parent::__construct(
                    $artifact->getId(),
                    'keyword',
                    102,
                    $this->artifact->getTracker()->getId(),
                    10,
                    $type
                );
            }

            public function getArtifact(): ?Artifact
            {
                return $this->artifact;
            }
        };
    }
}
