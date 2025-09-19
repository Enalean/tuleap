<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field;

use Codendi_HTMLPurifier;
use DateTimeImmutable;
use Override;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use ProjectUGroup;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_ArtifactLinkInfo;
use Tuleap\Artidoc\Document\Field\ArtifactLink\ArtifactLinkFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\Date\DateFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\List\ListFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\List\StaticListFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\List\UserGroupListWithValueBuilder;
use Tuleap\Artidoc\Document\Field\List\UserListFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\Numeric\NumericFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\Permissions\PermissionsOnArtifactFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\StepsDefinition\StepsDefinitionFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\StepsExecution\StepsExecutionFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\User\UserFieldWithValueBuilder;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\ArtifactLinkFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\ArtifactLinkProject;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\ArtifactLinkStatusValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\ArtifactLinkType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\ArtifactLinkValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\DateFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\FieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\NumericFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\PermissionsOnArtifactFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StaticListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StaticListValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StepResultValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StepsDefinitionFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StepsExecutionFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StepValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\TextFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupsListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserValue;
use Tuleap\Color\ColorName;
use Tuleap\GlobalLanguageMock;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Option\Option;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\AnonymousUserTestProvider;
use Tuleap\Test\Stubs\BuildDisplayNameStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideDefaultUserAvatarUrlStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\TestManagement\Step\Execution\StepResult;
use Tuleap\TestManagement\Step\Step;
use Tuleap\TestManagement\Test\Builders\ChangesetValueStepsDefinitionTestBuilder;
use Tuleap\TestManagement\Test\Builders\ChangesetValueStepsExecutionTestBuilder;
use Tuleap\TestManagement\Test\Builders\StepsDefinitionFieldBuilder;
use Tuleap\TestManagement\Test\Builders\StepsExecutionFieldBuilder;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\LinkDirection;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildPresenter;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueArtifactLinkTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueDateTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueIntegerTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValuePermissionsOnArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueStringTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueTextTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\LastUpdateByFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\MultiSelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\PermissionsOnArtifactFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Artifact\Dao\SearchArtifactGlobalRankStub;
use Tuleap\Tracker\Test\Stub\FormElement\Field\ArtifactLink\Type\RetrieveTypeFromShortnameStub;
use Tuleap\Tracker\Test\Stub\Semantic\Status\RetrieveSemanticStatusStub;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;
use Tuleap\Tracker\Tracker;
use Tuleap\User\UserGroup\NameTranslator;

#[DisableReturnValueGenerationForTestDoubles]
final class FieldsWithValuesBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private const int TRACKER_ID = 66;
    private const int BOB_ID     = 125;
    private const int ALICE_ID   = 126;

    private Tracker $tracker;
    private Tracker $linked_tracker;
    private Tracker_Artifact_Changeset $changeset;
    private PFUser $current_user;

    #[Override]
    protected function setUp(): void
    {
        $this->current_user   = UserTestBuilder::anActiveUser()->withTimezone('Europe/Paris')->build();
        $project              = ProjectTestBuilder::aProject()->withId(168)->withPublicName('My awesome project')->withIcon('💩')->build();
        $this->tracker        = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->withProject($project)->build();
        $this->linked_tracker = TrackerTestBuilder::aTracker()->withId(67)->withProject($project)->withColor(ColorName::RED_WINE)->build();
        $artifact             = ArtifactTestBuilder::anArtifact(78)->inTracker($this->tracker)->build();
        $this->changeset      = ChangesetTestBuilder::aChangeset(1263)
            ->ofArtifact($artifact)
            ->submittedBy(self::BOB_ID)
            ->build();

        $GLOBALS['Language']->method('getText')->willReturnCallback(static fn(string $page, string $key) => match ($key) {
            'ugroup_project_members' => 'Project Members',
        });
    }

    /**
     * @param list<ConfiguredField> $fields
     * @return list<FieldWithValue>
     */
    private function getFields(array $fields): array
    {
        $bob                             = UserTestBuilder::anActiveUser()
            ->withId(self::BOB_ID)
            ->withRealName('Bob')
            ->withUserName('bob')
            ->build();
        $alice                           = UserTestBuilder::anActiveUser()
            ->withId(self::ALICE_ID)
            ->withRealName('Alice')
            ->withUserName('alice')
            ->build();
        $provide_user_avatar_url         = ProvideUserAvatarUrlStub::build()
            ->withUserAvatarUrl($bob, 'bob_avatar.png')
            ->withUserAvatarUrl($alice, 'alice_avatar.png');
        $provide_default_user_avatar_url = ProvideDefaultUserAvatarUrlStub::build();
        $retrieve_user_by_id             = RetrieveUserByIdStub::withUsers($bob, $alice);
        $purifier                        = Codendi_HTMLPurifier::instance();
        $text_value_interpreter          = new TextValueInterpreter($purifier, CommonMarkInterpreter::build($purifier));
        $builder                         = new FieldsWithValuesBuilder(
            new ConfiguredFieldCollection([self::TRACKER_ID => $fields]),
            new ListFieldWithValueBuilder(
                new UserListFieldWithValueBuilder(
                    $retrieve_user_by_id,
                    $provide_user_avatar_url,
                    $provide_default_user_avatar_url,
                ),
                new StaticListFieldWithValueBuilder(),
                new UserGroupListWithValueBuilder(),
            ),
            new ArtifactLinkFieldWithValueBuilder(
                $this->current_user,
                RetrieveSemanticTitleFieldStub::build(),
                RetrieveSemanticStatusStub::build()->withSemanticStatus(new TrackerSemanticStatus($this->linked_tracker, null)),
                RetrieveTypeFromShortnameStub::build()
                    ->withTypePresenter(ArtifactLinkField::TYPE_IS_CHILD, new TypeIsChildPresenter()),
            ),
            new NumericFieldWithValueBuilder(SearchArtifactGlobalRankStub::build()),
            new UserFieldWithValueBuilder(
                $retrieve_user_by_id,
                new AnonymousUserTestProvider(),
                $provide_user_avatar_url,
                $provide_default_user_avatar_url,
                BuildDisplayNameStub::build(),
            ),
            new DateFieldWithValueBuilder($this->current_user),
            new PermissionsOnArtifactFieldWithValueBuilder(),
            new StepsDefinitionFieldWithValueBuilder($text_value_interpreter),
            new StepsExecutionFieldWithValueBuilder($text_value_interpreter),
        );
        return $builder->getFieldsWithValues($this->changeset);
    }

    public function testItReturnsEmpty(): void
    {
        self::assertSame([], $this->getFields([]));
    }

    public function testItBuildsStringAndTextFields(): void
    {
        $first_string_field  = StringFieldBuilder::aStringField(268)
            ->withLabel('naphthalol')
            ->inTracker($this->tracker)
            ->build();
        $second_string_field = StringFieldBuilder::aStringField(255)
            ->withLabel('dictator')
            ->inTracker($this->tracker)
            ->build();
        $text_field          = TextFieldBuilder::aTextField(274)
            ->withLabel('reframe')
            ->inTracker($this->tracker)
            ->build();

        $this->changeset->setFieldValue(
            $first_string_field,
            ChangesetValueStringTestBuilder::aValue(948, $this->changeset, $first_string_field)
                ->withValue('pleurogenic')
                ->build()
        );
        $this->changeset->setFieldValue($second_string_field, null);
        $this->changeset->setFieldValue(
            $text_field,
            ChangesetValueTextTestBuilder::aValue(949, $this->changeset, $text_field)
                ->withValue('**Hello!**', Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT)
                ->build(),
        );

        self::assertEquals([
            new TextFieldWithValue('naphthalol', DisplayType::COLUMN, 'pleurogenic'),
            new TextFieldWithValue('dictator', DisplayType::BLOCK, ''),
            new TextFieldWithValue('reframe', DisplayType::BLOCK, <<<HTML
                <p><strong>Hello!</strong></p>\n
                HTML
            ),
        ], $this->getFields([
            new ConfiguredField($first_string_field, DisplayType::COLUMN),
            new ConfiguredField($second_string_field, DisplayType::BLOCK),
            new ConfiguredField($text_field, DisplayType::BLOCK),
        ]));
    }

    public function testItBuildsUserGroupListFieldsWithValues(): void
    {
        $user_group_list_value1 = ProjectUGroupTestBuilder::buildProjectMembers();
        $user_group_list_value2 = ProjectUGroupTestBuilder::aCustomUserGroup(919)->withName('Reviewers')->build();
        $user_group_list_field  = ListUserGroupBindBuilder::aUserGroupBind(
            MultiSelectboxFieldBuilder::aMultiSelectboxField(480)
                ->withLabel('trionychoidean')
                ->inTracker($this->tracker)
                ->build()
        )->withUserGroups(
            [
                $user_group_list_value1,
                $user_group_list_value2,
                ProjectUGroupTestBuilder::aCustomUserGroup(794)->withName('Mentlegen')->build(),
            ]
        )->build()->getField();

        $this->changeset->setFieldValue(
            $user_group_list_field,
            ChangesetValueListTestBuilder::aListOfValue(934, $this->changeset, $user_group_list_field)
                ->withValues([
                    ListUserGroupValueBuilder::aUserGroupValue($user_group_list_value1)->build(),
                    ListUserGroupValueBuilder::aUserGroupValue($user_group_list_value2)->build(),
                ])
                ->build()
        );

        $expected_field_with_value = new UserGroupsListFieldWithValue('trionychoidean', DisplayType::COLUMN, [
            new UserGroupValue('Project Members'),
            new UserGroupValue('Reviewers'),
        ]);

        self::assertEquals([$expected_field_with_value], $this->getFields([
            new ConfiguredField($user_group_list_field, DisplayType::COLUMN),
        ]));
    }

    public function testItBuildsStaticListFieldWithValues(): void
    {
        $static_list_field = ListStaticBindBuilder::aStaticBind(
            SelectboxFieldBuilder::aSelectboxField(123)->inTracker($this->tracker)->withLabel('static list field')->build(),
        )->withBuildStaticValues([
            ListStaticValueBuilder::aStaticValue('Something')->build(),
        ])->build()->getField();

        $this->changeset->setFieldValue(
            $static_list_field,
            ChangesetValueListTestBuilder::aListOfValue(934, $this->changeset, $static_list_field)
                ->withValues([ListStaticValueBuilder::aStaticValue('Something')->build()])
                ->build()
        );

        $expected_field_with_value = new StaticListFieldWithValue('static list field', DisplayType::BLOCK, [
            new StaticListValue('Something', Option::nothing(ColorName::class)),
        ]);

        self::assertEquals([$expected_field_with_value], $this->getFields([
            new ConfiguredField($static_list_field, DisplayType::BLOCK),
        ]));
    }

    public function testItBuildsUserListFieldsWithValue(): void
    {
        $user_list_field = ListUserBindBuilder::aUserBind(
            SelectboxFieldBuilder::aSelectboxField(123)->withLabel('user list field')->build()
        )->build()->getField();

        $expected_list_field_with_value = new UserListFieldWithValue(
            $user_list_field->getLabel(),
            DisplayType::BLOCK,
            [
                new UserValue('Bob', 'bob_avatar.png'),
                new UserValue('Alice', 'alice_avatar.png'),
            ]
        );

        $this->changeset->setFieldValue(
            $user_list_field,
            ChangesetValueListTestBuilder::aListOfValue(407, $this->changeset, $user_list_field)
                ->withValues([
                    ListUserValueBuilder::aUserWithId(self::BOB_ID)->withDisplayedName('Bob')->build(),
                    ListUserValueBuilder::aUserWithId(self::ALICE_ID)->withDisplayedName('Alice')->build(),
                ])->build(),
        );

        self::assertEquals([$expected_list_field_with_value], $this->getFields([
            new ConfiguredField($user_list_field, DisplayType::BLOCK),
        ]));
    }

    public function testItBuildsLinkFieldsWithValue(): void
    {
        $link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(123)->build();

        $linked_artifact                = ArtifactTestBuilder::anArtifact(33)
            ->inTracker($this->linked_tracker)
            ->withChangesets(ChangesetTestBuilder::aChangeset(1015)->build())
            ->userCanView($this->current_user)
            ->build();
        $expected_link_field_with_value = new ArtifactLinkFieldWithValue(
            $link_field->getLabel(),
            DisplayType::BLOCK,
            [
                new ArtifactLinkValue(
                    new ArtifactLinkType(
                        'is Child of',
                        ArtifactLinkField::TYPE_IS_CHILD,
                        LinkDirection::REVERSE,
                    ),
                    'irrelevant',
                    ColorName::RED_WINE,
                    new ArtifactLinkProject(168, 'My awesome project', '💩'),
                    33,
                    '',
                    '/plugins/tracker/?aid=33',
                    Option::nothing(ArtifactLinkStatusValue::class),
                ),
            ],
        );

        $this->changeset->setFieldValue(
            $link_field,
            ChangesetValueArtifactLinkTestBuilder::aValue(1, $this->changeset, $link_field)
                ->withReverseLinks([
                    Tracker_ArtifactLinkInfo::buildFromArtifact($linked_artifact, ArtifactLinkField::TYPE_IS_CHILD),
                ])
                ->build(),
        );

        self::assertEquals([$expected_link_field_with_value], $this->getFields([
            new ConfiguredField($link_field, DisplayType::BLOCK),
        ]));
    }

    public function testItBuildsNumericFieldsWithValue(): void
    {
        $int_field = IntegerFieldBuilder::anIntField(123)->build();

        $expected_int_field_with_value = new NumericFieldWithValue(
            $int_field->getLabel(),
            DisplayType::BLOCK,
            Option::fromValue(23),
        );

        $this->changeset->setFieldValue(
            $int_field,
            ChangesetValueIntegerTestBuilder::aValue(1, $this->changeset, $int_field)->withValue(23)->build(),
        );

        self::assertEquals([$expected_int_field_with_value], $this->getFields([
            new ConfiguredField($int_field, DisplayType::BLOCK),
        ]));
    }

    public function testItBuildsUserFieldsWithValue(): void
    {
        $user_field = LastUpdateByFieldBuilder::aLastUpdateByField(123)->build();

        $expected_user_field_with_value = new UserFieldWithValue(
            $user_field->getLabel(),
            DisplayType::BLOCK,
            new UserValue('Bob (bob)', 'bob_avatar.png'),
        );

        $this->changeset->setFieldValue($user_field, null);

        self::assertEquals([$expected_user_field_with_value], $this->getFields([
            new ConfiguredField($user_field, DisplayType::BLOCK),
        ]));
    }

    public function testItBuildsDateFieldsWithValue(): void
    {
        $date_field = DateFieldBuilder::aDateField(123)->build();
        $timestamp  = 1753660800;

        $expected_date_field_with_value = new DateFieldWithValue(
            $date_field->getLabel(),
            DisplayType::BLOCK,
            Option::fromValue(DateTimeImmutable::createFromTimestamp($timestamp)),
            false,
        );

        $this->changeset->setFieldValue(
            $date_field,
            ChangesetValueDateTestBuilder::aValue(54, $this->changeset, $date_field)->withTimestamp($timestamp)->build(),
        );

        self::assertEquals([$expected_date_field_with_value], $this->getFields([
            new ConfiguredField($date_field, DisplayType::BLOCK),
        ]));
    }

    public function testItBuildsPermissionsOnArtifactFieldWithValue(): void
    {
        $permissions_field = PermissionsOnArtifactFieldBuilder::aPermissionsOnArtifactField(123)->build();

        $expected_permissions_field_with_value = new PermissionsOnArtifactFieldWithValue(
            $permissions_field->getLabel(),
            DisplayType::BLOCK,
            [new UserGroupValue('Project Members')],
        );

        $this->changeset->setFieldValue(
            $permissions_field,
            ChangesetValuePermissionsOnArtifactTestBuilder::aListOfPermissions(54, $this->changeset, $permissions_field)
                ->withAllowedUserGroups([
                    ProjectUGroup::PROJECT_MEMBERS => NameTranslator::PROJECT_MEMBERS,
                ])
                ->build(),
        );

        self::assertEquals([$expected_permissions_field_with_value], $this->getFields([
            new ConfiguredField($permissions_field, DisplayType::BLOCK),
        ]));
    }

    public function testItBuildsStepsDefinitionFieldWithValue(): void
    {
        $steps_definition_field = StepsDefinitionFieldBuilder::aStepsDefinitionField(123)
            ->inTracker($this->tracker)
            ->build();

        $expected_steps_definition_field_with_value = new StepsDefinitionFieldWithValue(
            $steps_definition_field->getLabel(),
            DisplayType::BLOCK,
            [
                new StepValue(
                    'Some description',
                    Option::nothing(\Psl\Type\string()),
                ),
            ],
        );

        $this->changeset->setFieldValue(
            $steps_definition_field,
            ChangesetValueStepsDefinitionTestBuilder::aValue(54, $this->changeset, $steps_definition_field)
                ->withSteps([
                    new Step(
                        12,
                        'Some description',
                        Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
                        null,
                        Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
                        1,
                    ),
                ])
                ->build(),
        );

        self::assertEquals([$expected_steps_definition_field_with_value], $this->getFields([
            new ConfiguredField($steps_definition_field, DisplayType::BLOCK),
        ]));
    }

    public function testItBuildsStepsExecutionFieldWithValue(): void
    {
        $steps_execution_field = StepsExecutionFieldBuilder::aStepsExecutionField(123)
            ->inTracker($this->tracker)
            ->build();

        $expected_steps_execution_field_with_value = new StepsExecutionFieldWithValue(
            $steps_execution_field->getLabel(),
            DisplayType::BLOCK,
            [
                new StepResultValue(
                    'Some description',
                    Option::nothing(\Psl\Type\string()),
                    'passed'
                ),
            ],
        );

        $this->changeset->setFieldValue(
            $steps_execution_field,
            ChangesetValueStepsExecutionTestBuilder::aValue(54, $this->changeset, $steps_execution_field)
                ->withStepsResults([
                    new StepResult(
                        new Step(
                            12,
                            'Some description',
                            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
                            null,
                            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
                            1,
                        ),
                        'passed',
                    ),
                ])
                ->build(),
        );

        self::assertEquals([$expected_steps_execution_field_with_value], $this->getFields([
            new ConfiguredField($steps_execution_field, DisplayType::BLOCK),
        ]));
    }
}
