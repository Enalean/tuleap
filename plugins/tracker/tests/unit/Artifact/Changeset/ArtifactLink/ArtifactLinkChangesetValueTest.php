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

namespace Tuleap\Tracker\Artifact\Changeset\ArtifactLink;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_Artifact_Changeset;
use Tracker_ArtifactLinkInfo;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueArtifactLinksFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactReferenceWithType;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkChangesetValueTest extends TestCase
{
    use GlobalLanguageMock;

    private function getChangesetValueArtifactLink(array $artifact_links, array $reverse_artifact_links): ArtifactLinkChangesetValue
    {
        $value = $this->getMockBuilder(ArtifactLinkChangesetValue::class)
            ->setConstructorArgs([
                111,
                ChangesetTestBuilder::aChangeset(3541)->build(),
                ArtifactLinkFieldBuilder::anArtifactLinkField(1542)->build(),
                false,
                $artifact_links,
                $reverse_artifact_links,
            ])
            ->onlyMethods(['getTypePresenterFactory'])
            ->getMock();
        $value->method('getTypePresenterFactory')->willReturn($this->createStub(TypePresenterFactory::class));

        return $value;
    }

    public function testNoDiffForChangesetValueArtifactLinkDiff(): void
    {
        $artlink_info_123 = new Tracker_ArtifactLinkInfo(123, 'bug', 12, 45, 654, null);
        $artlink_info_321 = new Tracker_ArtifactLinkInfo(321, 'task', 12, 46, 645, null);
        $artlink_info_999 = new Tracker_ArtifactLinkInfo(999, 'story', 12, 47, 645, null);

        $art_links_1 = ['123' => $artlink_info_123, '321' => $artlink_info_321, '999' => $artlink_info_999];
        $art_links_2 = ['999' => $artlink_info_999, '123' => $artlink_info_123, '321' => $artlink_info_321];
        $list_1      = $this->getChangesetValueArtifactLink($art_links_1, []);
        $list_2      = $this->getChangesetValueArtifactLink($art_links_2, []);
        self::assertEquals(null, $list_1->diff($list_2));
        self::assertEquals(null, $list_2->diff($list_1));
    }

    public function testItHasNoChangesWhenNoNewValues(): void
    {
        $changeset_value = new ArtifactLinkChangesetValue(
            354,
            ChangesetTestBuilder::aChangeset(4352)->build(),
            ArtifactLinkFieldBuilder::anArtifactLinkField(354)->build(),
            true,
            [],
            [],
        );

        $new_value = ['list_of_artifactlinkinfo' => ''];
        self::assertFalse($changeset_value->hasChanges($new_value));
    }

    public function testItHasNoChangesWhenSameValues(): void
    {
        $changeset_value = new ArtifactLinkChangesetValue(
            354,
            ChangesetTestBuilder::aChangeset(4352)->build(),
            ArtifactLinkFieldBuilder::anArtifactLinkField(354)->build(),
            true,
            [
                1 => new Tracker_ArtifactLinkInfo(1, 'bug', 12, 45, 645, null),
                2 => new Tracker_ArtifactLinkInfo(2, 'bug', 12, 45, 645, null),
            ],
            [],
        );

        $new_value = [
            'list_of_artifactlinkinfo' => [
                2 => new Tracker_ArtifactLinkInfo(2, 'bug', 12, 45, 645, null),
                1 => new Tracker_ArtifactLinkInfo(1, 'bug', 12, 45, 645, null),
            ],
        ];

        self::assertFalse($changeset_value->hasChanges($new_value));
    }

    public function testItHasChangesWhenLinksAreAdded(): void
    {
        $changeset_value = new ArtifactLinkChangesetValue(
            354,
            ChangesetTestBuilder::aChangeset(4352)->build(),
            ArtifactLinkFieldBuilder::anArtifactLinkField(354)->build(),
            true,
            [
                1 => new Tracker_ArtifactLinkInfo(1, 'bug', 12, 45, 645, null),
                2 => new Tracker_ArtifactLinkInfo(2, 'bug', 12, 45, 645, null),
            ],
            [],
        );

        $new_value = [
            'list_of_artifactlinkinfo' => [
                1 => new Tracker_ArtifactLinkInfo(1, 'bug', 12, 45, 645, null),
                2 => new Tracker_ArtifactLinkInfo(2, 'bug', 12, 45, 645, null),
                3 => new Tracker_ArtifactLinkInfo(3, 'bug', 12, 45, 645, null),
            ],
        ];

        self::assertTrue($changeset_value->hasChanges($new_value));
    }

    public function testItHasChangesWhenLinksAreRemoved(): void
    {
        $changeset_value = new ArtifactLinkChangesetValue(
            354,
            ChangesetTestBuilder::aChangeset(4352)->build(),
            ArtifactLinkFieldBuilder::anArtifactLinkField(354)->build(),
            true,
            [
                1 => new Tracker_ArtifactLinkInfo(1, 'bug', 12, 45, 645, null),
                2 => new Tracker_ArtifactLinkInfo(2, 'bug', 12, 45, 645, null),
            ],
            [],
        );

        $new_value = [
            'list_of_artifactlinkinfo' => [
                1 => new Tracker_ArtifactLinkInfo(1, 'bug', 12, 45, 645, null),
            ],
        ];

        self::assertTrue($changeset_value->hasChanges($new_value));
    }

    public function testItHasChangesWhenTypeIsChanged(): void
    {
        $changeset_value = new ArtifactLinkChangesetValue(
            354,
            ChangesetTestBuilder::aChangeset(4352)->build(),
            ArtifactLinkFieldBuilder::anArtifactLinkField(354)->build(),
            true,
            [
                1 => new Tracker_ArtifactLinkInfo(1, 'bug', 12, 45, 645, '_is_child'),
                2 => new Tracker_ArtifactLinkInfo(2, 'bug', 12, 45, 645, null),
            ],
            [],
        );

        $new_value = [
            'list_of_artifactlinkinfo' => [
                1 => new Tracker_ArtifactLinkInfo(1, 'bug', 12, 45, 645, 'fixed_in'),
                2 => new Tracker_ArtifactLinkInfo(2, 'bug', 12, 45, 645, null),
            ],
        ];

        self::assertTrue($changeset_value->hasChanges($new_value));
    }

    public function testBuildsFieldValueRESTRepresentation(): void
    {
        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->withLabel('Label')->build();

        $artifact_direct_link  = $this->buildArtifact(12);
        $artifact_reverse_link = $this->buildArtifact(13);

        $changeset_value_artifact_link = new ArtifactLinkChangesetValue(
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

        $tracker = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->withId(101)
            ->withName('tracker_name')
            ->build();

        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('userCanView')->willReturn(true);

        return $artifact;
    }

    private function buildTrackerLinkInfo(Artifact $artifact, ?string $type): Tracker_ArtifactLinkInfo
    {
        return new class ($artifact, $type) extends Tracker_ArtifactLinkInfo {
            public function __construct(private readonly Artifact $artifact, ?string $type)
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

            #[\Override]
            public function getArtifact(): ?Artifact
            {
                return $this->artifact;
            }
        };
    }
}
