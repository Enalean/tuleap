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

namespace Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field;

use Codendi_HTMLPurifier;
use DateTime;
use ForgeConfig;
use PFUser;
use ProjectUGroup;
use Tracker;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\CrossTracker\Field\ReadableFieldRetriever;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\Date\DateResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\Numeric\NumericResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\StaticList\StaticListResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\Text\TextResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\UGroupList\UGroupListResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\UserList\UserListResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\DateResultRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\NumericResultRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\StaticListRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\StaticListValueRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\TextResultRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\UGroupListRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\UGroupListValueRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\UserListRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\UserRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\Test\Stubs\RetrieveUserByEmailStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Test\Stubs\UGroupRetrieverStub;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Permission\TrackersPermissionsRetriever;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ExternalFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\OpenListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Permission\RetrieveUserPermissionOnFieldsStub;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\User\UserGroup\NameTranslator;
use UserHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldResultBuilderTest extends TestCase
{
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    private const FIELD_NAME      = 'my_field';
    private const FIRST_FIELD_ID  = 134;
    private const SECOND_FIELD_ID = 334;
    private string $field_hash;
    private PFUser $user;
    private Tracker $first_tracker;
    private Tracker $second_tracker;

    protected function setUp(): void
    {
        ForgeConfig::setFeatureFlag(TrackersPermissionsRetriever::FEATURE_FLAG, 1);
        ForgeConfig::set(ConfigurationVariables::SERVER_TIMEZONE, 'Europe/Paris');
        $this->field_hash     = md5('my_field');
        $this->user           = UserTestBuilder::buildWithId(133);
        $project              = ProjectTestBuilder::aProject()->withId(154)->build();
        $this->first_tracker  = TrackerTestBuilder::aTracker()->withId(38)->withProject($project)->build();
        $this->second_tracker = TrackerTestBuilder::aTracker()->withId(4)->withProject($project)->build();
    }

    private function getSelectedResult(
        RetrieveUsedFieldsStub $fields_retriever,
        RetrieveArtifactStub $artifact_retriever,
        array $selected_result,
    ): SelectedValuesCollection {
        $purifier        = Codendi_HTMLPurifier::instance();
        $user_helper     = $this->createMock(UserHelper::class);
        $field_retriever = new ReadableFieldRetriever(
            $fields_retriever,
            RetrieveUserPermissionOnFieldsStub::build()->withPermissionOn(
                [self::FIRST_FIELD_ID, self::SECOND_FIELD_ID],
                FieldPermissionType::PERMISSION_READ,
            )
        );
        $builder         = new FieldResultBuilder(
            new FieldTypeRetrieverWrapper(RetrieveFieldTypeStub::withDetectionOfType()),
            new DateResultBuilder($artifact_retriever, $fields_retriever),
            new TextResultBuilder(
                $artifact_retriever,
                new TextValueInterpreter(
                    $purifier,
                    CommonMarkInterpreter::build($purifier),
                ),
            ),
            new NumericResultBuilder(),
            new StaticListResultBuilder(),
            new UGroupListResultBuilder($artifact_retriever, UGroupRetrieverStub::buildWithUserGroups(
                new ProjectUGroup(['name' => NameTranslator::PROJECT_MEMBERS]),
                new ProjectUGroup(['name' => NameTranslator::PROJECT_ADMINS]),
                new ProjectUGroup(['name' => 'Custom_User_Group']),
            )),
            new UserListResultBuilder(
                RetrieveUserByIdStub::withUsers(
                    UserTestBuilder::aUser()->withId(131)->withRealName('Fabrice')->withUserName('fabDu38')->withAvatarUrl('https://example.com/fabrice')->build(),
                    UserTestBuilder::aUser()->withId(132)->withRealName('Eugénie')->withUserName('gege')->withAvatarUrl('https://example.com/eugenie')->build(),
                ),
                RetrieveUserByEmailStub::withNoUser(),
                ProvideAndRetrieveUserStub::build(UserTestBuilder::buildWithDefaults()),
                $user_helper,
            ),
            $field_retriever
        );

        $user_helper->method('getDisplayNameFromUser')->willReturnCallback(static fn(PFUser $user) => $user->isAnonymous() ? $user->getEmail() : $user->getRealName());

        return $builder->getResult(
            new Field(self::FIELD_NAME),
            $this->user,
            [$this->first_tracker, $this->second_tracker],
            $selected_result,
        );
    }

    public function testItReturnsEmptyAsNothingHasBeenImplemented(): void
    {
        $result = $this->getSelectedResult(
            RetrieveUsedFieldsStub::withFields(
                ExternalFieldBuilder::anExternalField(self::FIRST_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->build(),
                ArtifactLinkFieldBuilder::anArtifactLinkField(self::SECOND_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->build()
            ),
            RetrieveArtifactStub::withNoArtifact(),
            [],
        );

        self::assertNull($result->selected);
        self::assertEmpty($result->values);
    }

    public function testItReturnsValuesForDateField(): void
    {
        $first_date  = new DateTime('2024-06-12 11:30');
        $second_date = new DateTime('2024-06-12 00:00');
        $result      = $this->getSelectedResult(
            RetrieveUsedFieldsStub::withFields(
                DateFieldBuilder::aDateField(self::FIRST_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->withTime()
                    ->inTracker($this->first_tracker)
                    ->build(),
                DateFieldBuilder::aDateField(self::SECOND_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->build(),
            ),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(11)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(12)->inTracker($this->second_tracker)->build(),
            ),
            [
                ['id' => 11, $this->field_hash => $first_date->getTimestamp()],
                ['id' => 12, $this->field_hash => $second_date->getTimestamp()],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation(self::FIELD_NAME, CrossTrackerSelectedType::TYPE_DATE),
            $result->selected,
        );
        self::assertCount(2, $result->values);
        self::assertEqualsCanonicalizing([
            11 => new SelectedValue(self::FIELD_NAME, new DateResultRepresentation($first_date->format(DATE_ATOM), true)),
            12 => new SelectedValue(self::FIELD_NAME, new DateResultRepresentation($second_date->format(DATE_ATOM), false)),
        ], $result->values);
    }

    public function testItReturnsValuesForTextField(): void
    {
        $result = $this->getSelectedResult(
            RetrieveUsedFieldsStub::withFields(
                TextFieldBuilder::aTextField(self::FIRST_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->build(),
                TextFieldBuilder::aTextField(self::SECOND_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->build(),
            ),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(21)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(22)->inTracker($this->second_tracker)->build(),
                ArtifactTestBuilder::anArtifact(23)->inTracker($this->second_tracker)->build(),
            ),
            [
                ['id' => 21, $this->field_hash => '499P', "format_$this->field_hash" => 'text'],
                ['id' => 22, $this->field_hash => 'V-Series.R', "format_$this->field_hash" => 'commonmark'],
                ['id' => 23, $this->field_hash => null, "format_$this->field_hash" => null],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation(self::FIELD_NAME, CrossTrackerSelectedType::TYPE_TEXT),
            $result->selected,
        );
        self::assertCount(3, $result->values);
        self::assertEqualsCanonicalizing([
            21 => new SelectedValue(self::FIELD_NAME, new TextResultRepresentation('499P')),
            22 => new SelectedValue(self::FIELD_NAME, new TextResultRepresentation(<<<EOL
<p>V-Series.R</p>\n
EOL
            )),
            23 => new SelectedValue(self::FIELD_NAME, new TextResultRepresentation('')),
        ], $result->values);
    }

    public function testItReturnsValuesForNumericField(): void
    {
        $result = $this->getSelectedResult(
            RetrieveUsedFieldsStub::withFields(
                IntFieldBuilder::anIntField(self::FIRST_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->build(),
                FloatFieldBuilder::aFloatField(self::SECOND_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->build(),
            ),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(31)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(32)->inTracker($this->second_tracker)->build(),
                ArtifactTestBuilder::anArtifact(33)->inTracker($this->second_tracker)->build(),
            ),
            [
                ['id' => 31, "int_$this->field_hash" => 6, "float_$this->field_hash" => null],
                ['id' => 32, "int_$this->field_hash" => null, "float_$this->field_hash" => 3.1415],
                ['id' => 33, "int_$this->field_hash" => null, "float_$this->field_hash" => null],
            ]
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation(self::FIELD_NAME, CrossTrackerSelectedType::TYPE_NUMERIC),
            $result->selected,
        );
        self::assertCount(3, $result->values);
        self::assertEqualsCanonicalizing([
            31 => new SelectedValue(self::FIELD_NAME, new NumericResultRepresentation(6)),
            32 => new SelectedValue(self::FIELD_NAME, new NumericResultRepresentation(3.1415)),
            33 => new SelectedValue(self::FIELD_NAME, new NumericResultRepresentation(null)),
        ], $result->values);
    }

    public function testItReturnsValuesForStaticListField(): void
    {
        $result = $this->getSelectedResult(
            RetrieveUsedFieldsStub::withFields(
                ListStaticBindBuilder::aStaticBind(
                    ListFieldBuilder::aListField(self::FIRST_FIELD_ID)
                        ->withName(self::FIELD_NAME)
                        ->inTracker($this->first_tracker)
                        ->build()
                )->build()->getField(),
                ListStaticBindBuilder::aStaticBind(
                    OpenListFieldBuilder::anOpenListField()
                        ->withId(self::SECOND_FIELD_ID)
                        ->withName(self::FIELD_NAME)
                        ->withTracker($this->second_tracker)
                        ->build()
                )->build()->getField(),
            ),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(41)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(42)->inTracker($this->second_tracker)->build(),
                ArtifactTestBuilder::anArtifact(43)->inTracker($this->second_tracker)->build(),
            ),
            [
                [
                    'id'                                  => 41,
                    "static_list_value_$this->field_hash" => 'electrophobia',
                    "static_list_open_$this->field_hash"  => null,
                    "static_list_color_$this->field_hash" => 'fiesta-red',
                ],
                [
                    'id'                                  => 42,
                    "static_list_value_$this->field_hash" => null,
                    "static_list_open_$this->field_hash"  => 'abnormalised',
                    "static_list_color_$this->field_hash" => null,
                ],
                [
                    'id'                                  => 42,
                    "static_list_value_$this->field_hash" => 'disbenchment',
                    "static_list_open_$this->field_hash"  => null,
                    "static_list_color_$this->field_hash" => null,
                ],
                [
                    'id'                                  => 43,
                    "static_list_value_$this->field_hash" => null,
                    "static_list_open_$this->field_hash"  => null,
                    "static_list_color_$this->field_hash" => null,
                ],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation(self::FIELD_NAME, CrossTrackerSelectedType::TYPE_STATIC_LIST),
            $result->selected,
        );
        $values = $result->values;
        self::assertCount(3, $values);
        self::assertEqualsCanonicalizing([
            41 => new SelectedValue(self::FIELD_NAME, new StaticListRepresentation([
                new StaticListValueRepresentation('electrophobia', 'fiesta-red'),
            ])),
            42 => new SelectedValue(self::FIELD_NAME, new StaticListRepresentation([
                new StaticListValueRepresentation('abnormalised', null),
                new StaticListValueRepresentation('disbenchment', null),
            ])),
            43 => new SelectedValue(self::FIELD_NAME, new StaticListRepresentation([])),
        ], $result->values);
    }

    public function testItReturnsValuesForUserGroupListField(): void
    {
        $GLOBALS['Language']->expects(self::exactly(2))->method('getText')
            ->willReturnCallback(static fn(string $pagename, string $category) => match ($category) {
                'ugroup_project_members' => 'Project members',
                'ugroup_project_admins'  => 'Project admins',
            });

        $result = $this->getSelectedResult(
            RetrieveUsedFieldsStub::withFields(
                ListUserGroupBindBuilder::aUserGroupBind(
                    ListFieldBuilder::aListField(self::FIRST_FIELD_ID)
                        ->withName(self::FIELD_NAME)
                        ->inTracker($this->first_tracker)
                        ->build()
                )->build()->getField(),
                ListUserGroupBindBuilder::aUserGroupBind(
                    OpenListFieldBuilder::anOpenListField()
                        ->withId(self::SECOND_FIELD_ID)
                        ->withName(self::FIELD_NAME)
                        ->withTracker($this->second_tracker)
                        ->build()
                )->build()->getField(),
            ),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(51)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(52)->inTracker($this->first_tracker)->build(),
                ArtifactTestBuilder::anArtifact(53)->inTracker($this->first_tracker)->build(),
            ),
            [
                [
                    'id'                                      => 51,
                    "user_group_list_value_$this->field_hash" => 'ugroup_project_members_name_key',
                    "user_group_list_open_$this->field_hash"  => null,
                ],
                [
                    'id'                                      => 52,
                    "user_group_list_value_$this->field_hash" => 'ugroup_project_admins_name_key',
                    "user_group_list_open_$this->field_hash"  => null,
                ],
                [
                    'id'                                      => 52,
                    "user_group_list_value_$this->field_hash" => null,
                    "user_group_list_open_$this->field_hash"  => 'Custom_User_Group',
                ],
                [
                    'id'                                      => 53,
                    "user_group_list_value_$this->field_hash" => null,
                    "user_group_list_open_$this->field_hash"  => null,
                ],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation(self::FIELD_NAME, CrossTrackerSelectedType::TYPE_USER_GROUP_LIST),
            $result->selected,
        );
        $values = $result->values;
        self::assertCount(3, $values);
        self::assertEqualsCanonicalizing([
            51 => new SelectedValue(self::FIELD_NAME, new UGroupListRepresentation([
                new UGroupListValueRepresentation('Project members'),
            ])),
            52 => new SelectedValue(self::FIELD_NAME, new UGroupListRepresentation([
                new UGroupListValueRepresentation('Project admins'),
                new UGroupListValueRepresentation('Custom_User_Group'),
            ])),
            53 => new SelectedValue(self::FIELD_NAME, new UGroupListRepresentation([])),
        ], $values);
    }

    public function testItReturnsValuesForUserListField(): void
    {
        $result = $this->getSelectedResult(
            RetrieveUsedFieldsStub::withFields(
                ListUserBindBuilder::aUserBind(
                    ListFieldBuilder::aListField(self::FIRST_FIELD_ID)
                        ->withName(self::FIELD_NAME)
                        ->inTracker($this->first_tracker)
                        ->build()
                )->build()->getField(),
                ListUserBindBuilder::aUserBind(
                    OpenListFieldBuilder::anOpenListField()
                        ->withId(self::SECOND_FIELD_ID)
                        ->withName(self::FIELD_NAME)
                        ->withTracker($this->second_tracker)
                        ->build()
                )->build()->getField(),
            ),
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(61)->build(),
                ArtifactTestBuilder::anArtifact(62)->build(),
                ArtifactTestBuilder::anArtifact(63)->build(),
            ),
            [
                [
                    'id'                                => 61,
                    "user_list_value_$this->field_hash" => 131,
                    "user_list_open_$this->field_hash"  => null,
                ],
                [
                    'id'                                => 62,
                    "user_list_value_$this->field_hash" => 132,
                    "user_list_open_$this->field_hash"  => null,
                ],
                [
                    'id'                                => 62,
                    "user_list_value_$this->field_hash" => null,
                    "user_list_open_$this->field_hash"  => 'windmill@example.com',
                ],
                [
                    'id'                                => 63,
                    "user_list_value_$this->field_hash" => null,
                    "user_list_open_$this->field_hash"  => null,
                ],
            ],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation(self::FIELD_NAME, CrossTrackerSelectedType::TYPE_USER_LIST),
            $result->selected,
        );
        $values = $result->values;
        self::assertCount(3, $values);
        self::assertEqualsCanonicalizing([
            61 => new SelectedValue(self::FIELD_NAME, new UserListRepresentation([
                new UserRepresentation('Fabrice', 'https://example.com/fabrice', '/users/fabDu38', false),
            ])),
            62 => new SelectedValue(self::FIELD_NAME, new UserListRepresentation([
                new UserRepresentation('Eugénie', 'https://example.com/eugenie', '/users/gege', false),
                new UserRepresentation('windmill@example.com', 'https://' . PFUser::DEFAULT_AVATAR_URL, null, true),
            ])),
            63 => new SelectedValue(self::FIELD_NAME, new UserListRepresentation([])),
        ], $values);
    }
}
