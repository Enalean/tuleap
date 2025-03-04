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

namespace Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata;

use Codendi_HTMLPurifier;
use DateTime;
use ForgeConfig;
use LogicException;
use PFUser;
use Tracker;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\AlwaysThereField\ArtifactId\ArtifactIdResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Date\MetadataDateResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Semantic\AssignedTo\AssignedToResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Semantic\Status\StatusResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Special\PrettyTitle\PrettyTitleResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Special\ProjectName\ProjectNameResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Special\TrackerName\TrackerNameResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Text\MetadataTextResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\User\MetadataUserResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\ArtifactRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\DateResultRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\NumericResultRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\PrettyTitleRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\ProjectRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\StaticListRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\StaticListValueRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\TextResultRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\TrackerRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\UserListRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\UserRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;
use UserHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MetadataResultBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    private Tracker $first_tracker;
    private Tracker $second_tracker;

    protected function setUp(): void
    {
        ForgeConfig::set(ConfigurationVariables::SERVER_TIMEZONE, 'Europe/Paris');
        $project              = ProjectTestBuilder::aProject()->withId(154)->build();
        $this->first_tracker  = TrackerTestBuilder::aTracker()->withId(38)->withProject($project)->build();
        $this->second_tracker = TrackerTestBuilder::aTracker()->withId(4)->withProject($project)->build();
    }

    private function getSelectedResult(
        Metadata $metadata,
        RetrieveArtifactStub $artifact_retriever,
        array $selected_result,
    ): SelectedValuesCollection {
        $purifier               = Codendi_HTMLPurifier::instance();
        $user_helper            = $this->createMock(UserHelper::class);
        $user_retriever         = RetrieveUserByIdStub::withUsers(
            UserTestBuilder::aUser()->withId(135)->withUserName('jean')->withRealName('Jean Eude')->withAvatarUrl('https://example.com/jean')->build(),
            UserTestBuilder::aUser()->withId(145)->withUserName('alice')->withRealName('Alice')->withAvatarUrl('https://example.com/alice')->build(),
        );
        $text_value_interpreter = new TextValueInterpreter($purifier, CommonMarkInterpreter::build($purifier));
        $builder                = new MetadataResultBuilder(
            new MetadataTextResultBuilder($artifact_retriever, $text_value_interpreter),
            new StatusResultBuilder(),
            new AssignedToResultBuilder($user_retriever, $user_helper),
            new MetadataDateResultBuilder(),
            new MetadataUserResultBuilder($user_retriever, $user_helper),
            new ArtifactIdResultBuilder(),
            new ProjectNameResultBuilder(),
            new TrackerNameResultBuilder(),
            new PrettyTitleResultBuilder(),
            new ArtifactResultBuilder($artifact_retriever),
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
        self::expectException(LogicException::class);
        self::expectExceptionMessage('Unknown metadata type: @not-existing');
        $this->getSelectedResult(
            new Metadata('not-existing'),
            RetrieveArtifactStub::withNoArtifact(),
            [],
        );
    }

    public function testItReturnsValuesForTitleSemantic(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('title'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(11)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(12)->inTracker($this->second_tracker)->build(),
                ArtifactTestBuilder::anArtifact(13)->inTracker($this->second_tracker)->build(),
            ),
            [
                ['id' => 11, '@title' => 'My title', '@title_format' => 'text'],
                ['id' => 12, '@title' => '**Title**', '@title_format' => 'commonmark'],
                ['id' => 13, '@title' => null, '@title_format' => null],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@title', CrossTrackerSelectedType::TYPE_TEXT),
            $result->selected,
        );
        self::assertCount(3, $result->values);
        self::assertEqualsCanonicalizing([
            11 => new SelectedValue('@title', new TextResultRepresentation('My title')),
            12 => new SelectedValue('@title', new TextResultRepresentation(<<<EOL
<p><strong>Title</strong></p>\n
EOL
            )),
            13 => new SelectedValue('@title', new TextResultRepresentation('')),
        ], $result->values);
    }

    public function testItReturnsValuesForDescriptionSemantic(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('description'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(21)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(22)->inTracker($this->second_tracker)->build(),
                ArtifactTestBuilder::anArtifact(23)->inTracker($this->second_tracker)->build(),
            ),
            [
                ['id' => 21, '@description' => 'blablabla', '@description_format' => 'text'],
                ['id' => 22, '@description' => "# Hello\n\nWorld!", '@description_format' => 'commonmark'],
                ['id' => 23, '@description' => null, '@description_format' => null],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@description', CrossTrackerSelectedType::TYPE_TEXT),
            $result->selected,
        );
        self::assertCount(3, $result->values);
        self::assertEqualsCanonicalizing([
            21 => new SelectedValue('@description', new TextResultRepresentation('blablabla')),
            22 => new SelectedValue('@description', new TextResultRepresentation(<<<EOL
<h1>Hello</h1>
<p>World!</p>\n
EOL
            )),
            23 => new SelectedValue('@description', new TextResultRepresentation('')),
        ], $result->values);
    }

    public function testItReturnsValuesStatusSemantic(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('status'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(31)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(32)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(33)->inTracker($this->second_tracker)->build(),
            ),
            [
                ['id' => 31, '@status' => 'Open', '@status_color' => 'neon-green'],
                ['id' => 32, '@status' => 'Closed', '@status_color' => 'fiesta-red'],
                ['id' => 32, '@status' => 'Also open', '@status_color' => null],
                ['id' => 33, '@status' => null, '@status_color' => null],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@status', CrossTrackerSelectedType::TYPE_STATIC_LIST),
            $result->selected,
        );
        self::assertCount(3, $result->values);
        self::assertEqualsCanonicalizing([
            31 => new SelectedValue('@status', new StaticListRepresentation([
                new StaticListValueRepresentation('Open', 'neon-green'),
            ])),
            32 => new SelectedValue('@status', new StaticListRepresentation([
                new StaticListValueRepresentation('Closed', 'fiesta-red'),
                new StaticListValueRepresentation('Also open', null),
            ])),
            33 => new SelectedValue('@status', new StaticListRepresentation([])),
        ], $result->values);
    }

    public function testItReturnsValuesForAssignedToSemantic(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('assigned_to'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(41)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(42)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(43)->inTracker($this->second_tracker)->build(),
            ),
            [
                ['id' => 41, '@assigned_to' => 135],
                ['id' => 42, '@assigned_to' => 135],
                ['id' => 42, '@assigned_to' => 145],
                ['id' => 43, '@assigned_to' => null],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation('@assigned_to', CrossTrackerSelectedType::TYPE_USER_LIST),
            $result->selected,
        );
        self::assertCount(3, $result->values);
        self::assertEqualsCanonicalizing([
            41 => new SelectedValue('@assigned_to', new UserListRepresentation([
                new UserRepresentation('Jean Eude', 'https://example.com/jean', '/users/jean', false),
            ])),
            42 => new SelectedValue('@assigned_to', new UserListRepresentation([
                new UserRepresentation('Jean Eude', 'https://example.com/jean', '/users/jean', false),
                new UserRepresentation('Alice', 'https://example.com/alice', '/users/alice', false),
            ])),
            43 => new SelectedValue('@assigned_to', new UserListRepresentation([])),
        ], $result->values);
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
    }

    public function testItReturnsValuesForSubmittedByAlwaysThereField(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('submitted_by'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(71)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(72)->inTracker($this->first_tracker)->build(),
            ),
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
    }

    public function testItReturnsValuesForLastUpdateByAlwaysThereField(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('last_update_by'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(81)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(82)->inTracker($this->first_tracker)->build(),
            ),
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
    }

    public function testItReturnsValuesForArtifactIdAlwaysThereField(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('id'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(91)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(92)->inTracker($this->first_tracker)->build(),
            ),
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
    }

    public function testItReturnsValuesForProjectName(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('project.name'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(101)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(102)->inTracker($this->second_tracker)->build(),
            ),
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
    }

    public function testItReturnsValuesForTrackerName(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('tracker.name'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(111)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(112)->inTracker($this->second_tracker)->build(),
            ),
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
        self::assertCount(3, $result->values);
        self::assertEqualsCanonicalizing([
            121 => new SelectedValue('@pretty_title', new PrettyTitleRepresentation('tracker_38', 'inca-silver', 121, 'title 121')),
            122 => new SelectedValue('@pretty_title', new PrettyTitleRepresentation('tracker_4', 'neon-green', 122, 'title 122')),
            123 => new SelectedValue('@pretty_title', new PrettyTitleRepresentation('tracker_4', 'neon-green', 123, '')),
        ], $result->values);
    }

    public function testItReturnsValuesForArtifact(): void
    {
        $result = $this->getSelectedResult(
            new Metadata('artifact'),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(131)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(132)->inTracker($this->second_tracker)->build(),
            ),
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
            131 => new SelectedValue('@artifact', new ArtifactRepresentation('/plugins/tracker/?aid=131')),
            132 => new SelectedValue('@artifact', new ArtifactRepresentation('/plugins/tracker/?aid=132')),
        ], $result->values);
    }
}
