<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata;

use DateTime;
use ForgeConfig;
use LogicException;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Project;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\AlwaysThereField\ArtifactId\ArtifactIdResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Date\MetadataDateResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Special\LinkType\LinkTypeResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Special\ProjectName\ProjectNameResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Special\TrackerName\TrackerNameResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\User\MetadataUserResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\ArtifactRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\DateResultRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\NumericResultRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\ProjectRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\TrackerRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\UserRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\CrossTracker\Tests\Stub\Query\Advanced\ResultBuilder\Metadata\Semantic\AssignedTo\BuildResultAssignedToStub;
use Tuleap\CrossTracker\Tests\Stub\Query\Advanced\ResultBuilder\Metadata\Semantic\Description\BuildResultDescriptionStub;
use Tuleap\CrossTracker\Tests\Stub\Query\Advanced\ResultBuilder\Metadata\Semantic\Status\BuildResultStatusStub;
use Tuleap\CrossTracker\Tests\Stub\Query\Advanced\ResultBuilder\Metadata\Semantic\Title\BuildResultTitleStub;
use Tuleap\CrossTracker\Tests\Stub\Query\Advanced\ResultBuilder\Metadata\Special\BuildResultPrettyTitleStub;
use Tuleap\CrossTracker\Tests\Stub\Query\InstantiateRetrievedQueryTrackersStub;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;
use Tuleap\Tracker\Tracker;

#[DisableReturnValueGenerationForTestDoubles]
final class MetadataResultBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    private Tracker $first_tracker;
    private Tracker $second_tracker;
    private Project $project;

    private BuildResultTitleStub $result_title;
    private BuildResultDescriptionStub $result_description;
    private BuildResultStatusStub $result_status;
    private BuildResultAssignedToStub $result_assigned_to;
    private BuildResultPrettyTitleStub $result_pretty_title;

    #[\Override]
    protected function setUp(): void
    {
        ForgeConfig::set(ConfigurationVariables::SERVER_TIMEZONE, 'Europe/Paris');
        $this->project        = ProjectTestBuilder::aProject()->withId(154)->build();
        $this->first_tracker  = TrackerTestBuilder::aTracker()->withId(38)->withProject($this->project)->build();
        $this->second_tracker = TrackerTestBuilder::aTracker()->withId(4)->withProject($this->project)->build();

        $this->result_title        = BuildResultTitleStub::withDefaultValues();
        $this->result_description  = BuildResultDescriptionStub::withDefaultValues();
        $this->result_status       = BuildResultStatusStub::withDefaultValues();
        $this->result_assigned_to  = BuildResultAssignedToStub::withDefaultValues();
        $this->result_pretty_title = BuildResultPrettyTitleStub::withDefaultValues();
    }

    private function getSelectedResult(
        Metadata $metadata,
        RetrieveArtifactStub $artifact_retriever,
        InstantiateRetrievedQueryTrackersStub $query_trackers_retriever,
        array $selected_result,
    ): SelectedValuesCollection {
        $user_helper    = $this->createStub(\UserHelper::class);
        $user_retriever = RetrieveUserByIdStub::withUsers(
            UserTestBuilder::aUser()->withId(135)->withUserName('jean')->withRealName('Jean Eude')->withAvatarUrl('https://example.com/jean')->build(),
            UserTestBuilder::aUser()->withId(145)->withUserName('alice')->withRealName('Alice')->withAvatarUrl('https://example.com/alice')->build(),
        );
        $builder        = new MetadataResultBuilder(
            $this->result_title,
            $this->result_description,
            $this->result_status,
            $this->result_assigned_to,
            new MetadataDateResultBuilder(),
            new MetadataUserResultBuilder($user_retriever, $user_helper),
            new ArtifactIdResultBuilder(),
            new ProjectNameResultBuilder(),
            new TrackerNameResultBuilder(),
            $this->result_pretty_title,
            new LinkTypeResultBuilder(),
            new ArtifactResultBuilder($artifact_retriever, $query_trackers_retriever),
        );

        $user_helper->method('getDisplayNameFromUser')->willReturnCallback(static fn(PFUser $user) => $user->getRealName());

        return $builder->getResult(
            $metadata,
            $selected_result,
            UserTestBuilder::buildWithDefaults(),
        );
    }

    public function testItThrowsIfUnknownMetadata(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unknown metadata type: @not-existing');
        $this->getSelectedResult(
            new Metadata('not-existing'),
            RetrieveArtifactStub::withNoArtifact(),
            InstantiateRetrievedQueryTrackersStub::withNoTrackers(),
            [],
        );
    }

    public function testItReturnsValuesForTitleSemantic(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('title'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(11)->inTracker($this->first_tracker)->build(),
            ),
            InstantiateRetrievedQueryTrackersStub::withNoTrackers(),
            [
                ['id' => 11, '@title' => 'My title', '@title_format' => 'text'],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@title', CrossTrackerSelectedType::TYPE_TEXT),
            $result->selected,
        );
        self::assertSame(1, $this->result_title->getCallCount());
        self::assertSame(0, $this->result_description->getCallCount());
        self::assertSame(0, $this->result_status->getCallCount());
        self::assertSame(0, $this->result_assigned_to->getCallCount());
        self::assertSame(0, $this->result_pretty_title->getCallCount());
    }

    public function testItReturnsValuesForDescriptionSemantic(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('description'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(21)->inTracker($this->first_tracker)->build(),
            ),
            InstantiateRetrievedQueryTrackersStub::withNoTrackers(),
            [
                ['id' => 21, '@description' => 'blablabla', '@description_format' => 'text'],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@description', CrossTrackerSelectedType::TYPE_TEXT),
            $result->selected,
        );
        self::assertSame(0, $this->result_title->getCallCount());
        self::assertSame(1, $this->result_description->getCallCount());
        self::assertSame(0, $this->result_status->getCallCount());
        self::assertSame(0, $this->result_assigned_to->getCallCount());
        self::assertSame(0, $this->result_pretty_title->getCallCount());
    }

    public function testItReturnsValuesStatusSemantic(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('status'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(31)->inTracker($this->first_tracker)->build(),
            ),
            InstantiateRetrievedQueryTrackersStub::withNoTrackers(),
            [
                ['id' => 31, '@status' => 'Open', '@status_color' => 'neon-green'],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@status', CrossTrackerSelectedType::TYPE_STATIC_LIST),
            $result->selected,
        );
        self::assertSame(0, $this->result_title->getCallCount());
        self::assertSame(0, $this->result_description->getCallCount());
        self::assertSame(1, $this->result_status->getCallCount());
        self::assertSame(0, $this->result_assigned_to->getCallCount());
        self::assertSame(0, $this->result_pretty_title->getCallCount());
    }

    public function testItReturnsValuesForAssignedToSemantic(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('assigned_to'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(41)->inTracker($this->first_tracker)->build(),
            ),
            InstantiateRetrievedQueryTrackersStub::withNoTrackers(),
            [
                ['id' => 41, '@assigned_to' => 135],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@assigned_to', CrossTrackerSelectedType::TYPE_USER_LIST),
            $result->selected,
        );
        self::assertSame(0, $this->result_title->getCallCount());
        self::assertSame(0, $this->result_description->getCallCount());
        self::assertSame(0, $this->result_status->getCallCount());
        self::assertSame(1, $this->result_assigned_to->getCallCount());
        self::assertSame(0, $this->result_pretty_title->getCallCount());
    }

    public function testItReturnsValuesForSubmittedOnAlwaysThereField(): void
    {
        $first_date  = new DateTime('2024-06-12 11:30');
        $second_date = new DateTime('2024-06-12 00:00');
        $result      = $this->getSelectedResult(
            new Metadata('submitted_on'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(51)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(52)->inTracker($this->first_tracker)->build(),
            ),
            InstantiateRetrievedQueryTrackersStub::withNoTrackers(),
            [
                ['id' => 51, '@submitted_on' => $first_date->getTimestamp()],
                ['id' => 52, '@submitted_on' => $second_date->getTimestamp()],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@submitted_on', CrossTrackerSelectedType::TYPE_DATE),
            $result->selected,
        );
        self::assertCount(2, $result->values);
        self::assertEqualsCanonicalizing([
            51 => new SelectedValue('@submitted_on', new DateResultRepresentation($first_date->format(DATE_ATOM), true)),
            52 => new SelectedValue('@submitted_on', new DateResultRepresentation($second_date->format(DATE_ATOM), true)),
        ], $result->values);
        self::assertSame(0, $this->result_title->getCallCount());
        self::assertSame(0, $this->result_description->getCallCount());
        self::assertSame(0, $this->result_status->getCallCount());
        self::assertSame(0, $this->result_assigned_to->getCallCount());
        self::assertSame(0, $this->result_pretty_title->getCallCount());
    }

    public function testItReturnsValuesForLastUpdateDateAlwaysThereField(): void
    {
        $first_date  = new DateTime('2024-06-12 11:30');
        $second_date = new DateTime('2024-06-12 00:00');
        $result      = $this->getSelectedResult(
            new Metadata('last_update_date'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(61)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(62)->inTracker($this->first_tracker)->build(),
            ),
            InstantiateRetrievedQueryTrackersStub::withNoTrackers(),
            [
                ['id' => 61, '@last_update_date' => $first_date->getTimestamp()],
                ['id' => 62, '@last_update_date' => $second_date->getTimestamp()],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@last_update_date', CrossTrackerSelectedType::TYPE_DATE),
            $result->selected,
        );
        self::assertCount(2, $result->values);
        self::assertEqualsCanonicalizing([
            61 => new SelectedValue('@last_update_date', new DateResultRepresentation($first_date->format(DATE_ATOM), true)),
            62 => new SelectedValue('@last_update_date', new DateResultRepresentation($second_date->format(DATE_ATOM), true)),
        ], $result->values);
        self::assertSame(0, $this->result_title->getCallCount());
        self::assertSame(0, $this->result_description->getCallCount());
        self::assertSame(0, $this->result_status->getCallCount());
        self::assertSame(0, $this->result_assigned_to->getCallCount());
        self::assertSame(0, $this->result_pretty_title->getCallCount());
    }

    public function testItReturnsValuesForSubmittedByAlwaysThereField(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('submitted_by'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(71)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(72)->inTracker($this->first_tracker)->build(),
            ),
            InstantiateRetrievedQueryTrackersStub::withNoTrackers(),
            [
                ['id' => 71, '@submitted_by' => 135],
                ['id' => 72, '@submitted_by' => 145],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@submitted_by', CrossTrackerSelectedType::TYPE_USER),
            $result->selected,
        );
        self::assertCount(2, $result->values);
        self::assertEqualsCanonicalizing([
            71 => new SelectedValue('@submitted_by', new UserRepresentation('Jean Eude', 'https://example.com/jean', '/users/jean', false)),
            72 => new SelectedValue('@submitted_by', new UserRepresentation('Alice', 'https://example.com/alice', '/users/alice', false)),
        ], $result->values);
        self::assertSame(0, $this->result_title->getCallCount());
        self::assertSame(0, $this->result_description->getCallCount());
        self::assertSame(0, $this->result_status->getCallCount());
        self::assertSame(0, $this->result_assigned_to->getCallCount());
        self::assertSame(0, $this->result_pretty_title->getCallCount());
    }

    public function testItReturnsValuesForLastUpdateByAlwaysThereField(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('last_update_by'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(81)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(82)->inTracker($this->first_tracker)->build(),
            ),
            InstantiateRetrievedQueryTrackersStub::withNoTrackers(),
            [
                ['id' => 81, '@last_update_by' => 135],
                ['id' => 82, '@last_update_by' => 145],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@last_update_by', CrossTrackerSelectedType::TYPE_USER),
            $result->selected,
        );
        self::assertCount(2, $result->values);
        self::assertEqualsCanonicalizing([
            81 => new SelectedValue('@last_update_by', new UserRepresentation('Jean Eude', 'https://example.com/jean', '/users/jean', false)),
            82 => new SelectedValue('@last_update_by', new UserRepresentation('Alice', 'https://example.com/alice', '/users/alice', false)),
        ], $result->values);
        self::assertSame(0, $this->result_title->getCallCount());
        self::assertSame(0, $this->result_description->getCallCount());
        self::assertSame(0, $this->result_status->getCallCount());
        self::assertSame(0, $this->result_assigned_to->getCallCount());
        self::assertSame(0, $this->result_pretty_title->getCallCount());
    }

    public function testItReturnsValuesForArtifactIdAlwaysThereField(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('id'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(91)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(92)->inTracker($this->first_tracker)->build(),
            ),
            InstantiateRetrievedQueryTrackersStub::withNoTrackers(),
            [
                ['id' => 91, '@id' => 91],
                ['id' => 92, '@id' => 92],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@id', CrossTrackerSelectedType::TYPE_NUMERIC),
            $result->selected,
        );
        self::assertCount(2, $result->values);
        self::assertEqualsCanonicalizing([
            91 => new SelectedValue('@id', new NumericResultRepresentation(91)),
            92 => new SelectedValue('@id', new NumericResultRepresentation(92)),
        ], $result->values);
        self::assertSame(0, $this->result_title->getCallCount());
        self::assertSame(0, $this->result_description->getCallCount());
        self::assertSame(0, $this->result_status->getCallCount());
        self::assertSame(0, $this->result_assigned_to->getCallCount());
        self::assertSame(0, $this->result_pretty_title->getCallCount());
    }

    public function testItReturnsValuesForProjectName(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('project.name'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(101)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(102)->inTracker($this->second_tracker)->build(),
            ),
            InstantiateRetrievedQueryTrackersStub::withNoTrackers(),
            [
                ['id' => 101, '@project.name' => 'Project 101', '@project.icon' => null],
                ['id' => 102, '@project.name' => 'Project with icon', '@project.icon' => EmojiCodepointConverter::convertEmojiToStoreFormat('⚔️')],
            ]
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@project.name', CrossTrackerSelectedType::TYPE_PROJECT),
            $result->selected,
        );
        self::assertCount(2, $result->values);
        self::assertEqualsCanonicalizing([
            101 => new SelectedValue('@project.name', new ProjectRepresentation('Project 101', '')),
            102 => new SelectedValue('@project.name', new ProjectRepresentation('Project with icon', '⚔️')),
        ], $result->values);
        self::assertSame(0, $this->result_title->getCallCount());
        self::assertSame(0, $this->result_description->getCallCount());
        self::assertSame(0, $this->result_status->getCallCount());
        self::assertSame(0, $this->result_assigned_to->getCallCount());
        self::assertSame(0, $this->result_pretty_title->getCallCount());
    }

    public function testItReturnsValuesForTrackerName(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('tracker.name'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(111)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(112)->inTracker($this->second_tracker)->build(),
            ),
            InstantiateRetrievedQueryTrackersStub::withNoTrackers(),
            [
                ['id' => 111, '@tracker.name' => 'Tracker 38', '@tracker.color' => 'neon-green'],
                ['id' => 112, '@tracker.name' => 'Tracker 4', '@tracker.color' => 'deep-blue'],
            ]
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@tracker.name', CrossTrackerSelectedType::TYPE_TRACKER),
            $result->selected,
        );
        self::assertCount(2, $result->values);
        self::assertEqualsCanonicalizing([
            111 => new SelectedValue('@tracker.name', new TrackerRepresentation('Tracker 38', 'neon-green')),
            112 => new SelectedValue('@tracker.name', new TrackerRepresentation('Tracker 4', 'deep-blue')),
        ], $result->values);

        self::assertSame(0, $this->result_title->getCallCount());
        self::assertSame(0, $this->result_description->getCallCount());
        self::assertSame(0, $this->result_status->getCallCount());
        self::assertSame(0, $this->result_assigned_to->getCallCount());
        self::assertSame(0, $this->result_pretty_title->getCallCount());
    }

    public function testItReturnsValuesForPrettyTitle(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('pretty_title'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(121)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(122)->inTracker($this->second_tracker)->build(),
                ArtifactTestBuilder::anArtifact(123)->inTracker($this->second_tracker)->build(),
            ),
            InstantiateRetrievedQueryTrackersStub::withNoTrackers(),
            [
                ['id' => 121, '@pretty_title.tracker' => 'tracker_38', '@pretty_title.color' => 'inca-silver', '@pretty_title' => 'title 121', '@pretty_title.format' => 'text'],
                ['id' => 122, '@pretty_title.tracker' => 'tracker_4', '@pretty_title.color' => 'neon-green', '@pretty_title' => 'title 122', '@pretty_title.format' => 'text'],
                ['id' => 123, '@pretty_title.tracker' => 'tracker_4', '@pretty_title.color' => 'neon-green', '@pretty_title' => null, '@pretty_title.format' => null],
            ]
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@pretty_title', CrossTrackerSelectedType::TYPE_PRETTY_TITLE),
            $result->selected,
        );

        self::assertSame(0, $this->result_title->getCallCount());
        self::assertSame(0, $this->result_description->getCallCount());
        self::assertSame(0, $this->result_status->getCallCount());
        self::assertSame(0, $this->result_assigned_to->getCallCount());
        self::assertSame(1, $this->result_pretty_title->getCallCount());
    }

    public function testItReturnsValuesForArtifact(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('artifact'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(131)->withoutLinkedArtifact()->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(132)
                    ->withLinkedArtifact(ArtifactTestBuilder::anArtifact(156)->inTracker($this->first_tracker)->build())
                    ->withLinkedAndReverseArtifact(ArtifactTestBuilder::anArtifact(156)->inTracker($this->first_tracker)->build())
                    ->inTracker($this->second_tracker)
                    ->build(),
            ),
            InstantiateRetrievedQueryTrackersStub::withTrackers($this->first_tracker, $this->second_tracker),
            [
                ['id' => 131],
                ['id' => 132],
            ]
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@artifact', CrossTrackerSelectedType::TYPE_ARTIFACT),
            $result->selected,
        );
        self::assertCount(2, $result->values);
        self::assertEqualsCanonicalizing([
            131 => new SelectedValue('@artifact', new ArtifactRepresentation(131, '/plugins/tracker/?aid=131', 0, 0)),
            132 => new SelectedValue('@artifact', new ArtifactRepresentation(132, '/plugins/tracker/?aid=132', 1, 0)),
        ], $result->values);
        self::assertSame(0, $this->result_title->getCallCount());
        self::assertSame(0, $this->result_description->getCallCount());
        self::assertSame(0, $this->result_status->getCallCount());
        self::assertSame(0, $this->result_assigned_to->getCallCount());
        self::assertSame(0, $this->result_pretty_title->getCallCount());
    }

    public function testItReturnsFilteredValuesOfNumberOfForwardAndReverseLinks(): void
    {
        $query_trackers_retriever = InstantiateRetrievedQueryTrackersStub::withTrackers($this->first_tracker);

        $result = $this->getSelectedResult(
            new Metadata('artifact'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(132)
                    ->withLinkedArtifact(ArtifactTestBuilder::anArtifact(156)->inTracker($this->first_tracker)->build())
                    ->withLinkedAndReverseArtifact(
                        ArtifactTestBuilder::anArtifact(157)->inTracker($this->first_tracker)->build(),
                        ArtifactTestBuilder::anArtifact(158)->inTracker($this->first_tracker)->build(),
                        ArtifactTestBuilder::anArtifact(159)->inTracker($this->second_tracker)->build(),
                    )
                    ->inTracker($this->second_tracker)
                    ->build(),
            ),
            $query_trackers_retriever,
            [
                ['id' => 132],
            ]
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@artifact', CrossTrackerSelectedType::TYPE_ARTIFACT),
            $result->selected,
        );
        self::assertCount(1, $result->values);
        self::assertEqualsCanonicalizing([
            132 => new SelectedValue('@artifact', new ArtifactRepresentation(132, '/plugins/tracker/?aid=132', 1, 1)),
        ], $result->values);
        self::assertSame(0, $this->result_title->getCallCount());
        self::assertSame(0, $this->result_description->getCallCount());
        self::assertSame(0, $this->result_status->getCallCount());
        self::assertSame(0, $this->result_assigned_to->getCallCount());
        self::assertSame(0, $this->result_pretty_title->getCallCount());
    }

    public function testItReturnsValuesForLinkType(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('link_type'),
            RetrieveArtifactStub::withNoArtifact(),
            InstantiateRetrievedQueryTrackersStub::withNoTrackers(),
            []
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@link_type', CrossTrackerSelectedType::LINK_TYPE),
            $result->selected,
        );
        self::assertSame(0, $this->result_title->getCallCount());
        self::assertSame(0, $this->result_description->getCallCount());
        self::assertSame(0, $this->result_status->getCallCount());
        self::assertSame(0, $this->result_assigned_to->getCallCount());
        self::assertSame(0, $this->result_pretty_title->getCallCount());
    }
}
