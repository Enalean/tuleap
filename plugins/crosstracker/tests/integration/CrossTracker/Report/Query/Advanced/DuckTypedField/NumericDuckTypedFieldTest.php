<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField;

use ProjectManager;
use Tracker;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_Semantic_ContributorDao;
use Tracker_Semantic_DescriptionDao;
use Tracker_Semantic_StatusDao;
use Tracker_Semantic_TitleDao;
use Tuleap\CrossTracker\CrossTrackerArtifactReportDao;
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\CrossTracker\Report\CrossTrackerArtifactReportFactory;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchableCollectorVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidTermCollectorVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\ArtifactLink\ForwardLinkFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\ArtifactLink\ReverseLinkFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\CrossTrackerExpertQueryReportDao;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\FromWhereSearchableVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Date;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Users;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\ListValueExtractor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\AssignedTo;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Description;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Status;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Title;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilderVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\Between\BetweenComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\Equal\EqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\GreaterThan\GreaterThanComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\GreaterThan\GreaterThanOrEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\In\InComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\LesserThan\LesserThanComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\LesserThan\LesserThanOrEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ListValueValidator;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\NotEqual\NotEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\NotIn\NotInComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Field\FieldUsageChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\MetadataChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\MetadataUsageChecker;
use Tuleap\CrossTracker\SearchOnDuckTypedFieldsConfig;
use Tuleap\CrossTracker\Tests\Builders\DatabaseBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\ExpertQueryValidator;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Parser;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ArtifactLink\ArtifactLinkTypeChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFormatValidator;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringAllowed;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringForbidden;
use Tuleap\Tracker\Report\Query\Advanced\ParserCacheProxy;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\Report\Query\Advanced\SizeValidatorVisitor;
use Tuleap\Tracker\Report\TrackerReportConfig;
use Tuleap\Tracker\Report\TrackerReportConfigDao;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UserManager;

final class NumericDuckTypedFieldTest extends TestIntegrationTestCase
{
    private DatabaseBuilder $database_builder;
    private Tracker $release_tracker;
    private Tracker $sprint_tracker;
    private Tracker $task_tracker;
    private \PFUser $project_member;
    private int $release_initial_effort_field_id;
    private int $sprint_initial_effort_field_id;
    private \PFUser $outsider_user;
    private Tracker $epic_tracker;

    protected function setUp(): void
    {
        ProjectManager::clearInstance();

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        \ForgeConfig::setFeatureFlag(SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS, '1');
        $this->database_builder = new DatabaseBuilder($db);

        $project    = $this->database_builder->buildProject();
        $project_id = (int) $project->getID();

        $this->release_tracker = $this->database_builder->buildTracker($project_id, "Release");
        $this->release_tracker->setProject($project);
        $this->sprint_tracker = $this->database_builder->buildTracker($project_id, "Sprint");
        $this->sprint_tracker->setProject($project);
        $this->task_tracker = $this->database_builder->buildTracker($project_id, "Task");
        $this->epic_tracker = $this->database_builder->buildTracker($project_id, "Epic");

        $this->release_initial_effort_field_id = $this->database_builder->buildIntField(
            $this->release_tracker->getId(),
            'initial_effort'
        );
        $this->sprint_initial_effort_field_id  = $this->database_builder->buildFloatField(
            $this->sprint_tracker->getId(),
            'initial_effort'
        );
        $initial_effort_computed_field_id      = $this->database_builder->buildComputedField(
            $this->task_tracker->getId(),
            'initial_effort'
        );
        $initial_effort_read_field_id          = $this->database_builder->buildFloatField(
            $this->epic_tracker->getId(),
            'initial_effort'
        );

        $this->outsider_user  = $this->database_builder->buildUser('outsider', 'User OutsideProject', 'outsider@example.com');
        $this->project_member = $this->database_builder->buildUser('janwar', 'Jorge Anwar', 'janwar@example.com');
        $this->database_builder->addUserToProjectMembers((int) $this->project_member->getId(), $project_id);

        $this->database_builder->setReadPermission(
            $this->release_initial_effort_field_id,
            \ProjectUGroup::PROJECT_MEMBERS
        );
        $this->database_builder->setReadPermission(
            $this->sprint_initial_effort_field_id,
            \ProjectUGroup::PROJECT_MEMBERS
        );
        $this->database_builder->setReadPermission(
            $initial_effort_computed_field_id,
            \ProjectUGroup::PROJECT_MEMBERS
        );
        $this->database_builder->setReadPermission(
            $initial_effort_read_field_id,
            \ProjectUGroup::PROJECT_ADMIN
        );

        $this->release_tracker = TrackerTestBuilder::aTracker()
            ->withId($this->release_tracker->getId())
            ->withProject($project)
            ->build();
        $this->sprint_tracker  = TrackerTestBuilder::aTracker()
            ->withId($this->sprint_tracker->getId())
            ->withProject($project)
            ->build();
        $this->task_tracker    = TrackerTestBuilder::aTracker()
            ->withId($this->task_tracker->getId())
            ->withProject($project)
            ->build();
        $this->epic_tracker    = TrackerTestBuilder::aTracker()
            ->withId($this->epic_tracker->getId())
            ->withProject($project)
            ->build();
    }

    protected function tearDown(): void
    {
        \ForgeConfig::clearFeatureFlag(SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS);
    }

    /**
     * @return list<int>
     * @throws SearchablesDoNotExistException
     * @throws SearchablesAreInvalidException
     */
    private function getMatchingArtifactIds(CrossTrackerReport $report, \PFUser $user): array
    {
        $artifacts = $this->getFactory()->getArtifactsMatchingReport($report, $user, 5, 0)->getArtifacts();
        return array_values(array_map(static fn(Artifact $artifact) => $artifact->getId(), $artifacts));
    }

    public function testEqualComparison(): void
    {
        $release_empty_id  = $this->database_builder->buildArtifact($this->release_tracker->getId());
        $sprint_empty_id   = $this->database_builder->buildArtifact($this->sprint_tracker->getId());
        $release_with_5_id = $this->database_builder->buildArtifact($this->release_tracker->getId());
        $sprint_with_5_id  = $this->database_builder->buildArtifact($this->sprint_tracker->getId());

        $this->database_builder->buildLastChangeset($release_empty_id);
        $this->database_builder->buildLastChangeset($sprint_empty_id);
        $release_changeset_id = $this->database_builder->buildLastChangeset($release_with_5_id);
        $sprint_changeset_id  = $this->database_builder->buildLastChangeset($sprint_with_5_id);

        $this->database_builder->buildIntValue($release_changeset_id, $this->release_initial_effort_field_id, 5);
        $this->database_builder->buildFloatValue($sprint_changeset_id, $this->sprint_initial_effort_field_id, 5);

        $empty_artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "initial_effort=''",
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(2, $empty_artifacts);
        self::assertNotContains($release_with_5_id, $empty_artifacts);
        self::assertNotContains($sprint_with_5_id, $empty_artifacts);
        self::assertEqualsCanonicalizing([$release_empty_id, $sprint_empty_id], $empty_artifacts);

        $artifacts_with_value = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                2,
                'initial_effort=5',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts_with_value);
        self::assertNotContains($release_empty_id, $artifacts_with_value);
        self::assertNotContains($sprint_empty_id, $artifacts_with_value);
        self::assertEqualsCanonicalizing([$release_with_5_id, $sprint_with_5_id], $artifacts_with_value);

        $or_artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                3,
                "initial_effort = '' OR initial_effort = 5",
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(4, $or_artifacts);
        self::assertEqualsCanonicalizing(
            [$release_empty_id, $sprint_empty_id, $release_with_5_id, $sprint_with_5_id],
            $or_artifacts
        );
    }

    public function testNotEqualComparison(): void
    {
        $release_empty_id  = $this->database_builder->buildArtifact($this->release_tracker->getId());
        $sprint_empty_id   = $this->database_builder->buildArtifact($this->sprint_tracker->getId());
        $release_with_5_id = $this->database_builder->buildArtifact($this->release_tracker->getId());
        $release_with_3_id = $this->database_builder->buildArtifact($this->release_tracker->getId());
        $sprint_with_3_id  = $this->database_builder->buildArtifact($this->sprint_tracker->getId());

        $this->database_builder->buildLastChangeset($release_empty_id);
        $this->database_builder->buildLastChangeset($sprint_empty_id);
        $release_with_5_changeset_id = $this->database_builder->buildLastChangeset($release_with_5_id);
        $release_with_3_changeset_id = $this->database_builder->buildLastChangeset($release_with_3_id);
        $sprint_changeset_id         = $this->database_builder->buildLastChangeset($sprint_with_3_id);

        $this->database_builder->buildIntValue($release_with_5_changeset_id, $this->release_initial_effort_field_id, 5);
        $this->database_builder->buildIntValue($release_with_3_changeset_id, $this->release_initial_effort_field_id, 3);
        $this->database_builder->buildFloatValue($sprint_changeset_id, $this->sprint_initial_effort_field_id, 3);

        $not_empty_artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "initial_effort != ''",
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(3, $not_empty_artifacts);
        self::assertNotContains($release_empty_id, $not_empty_artifacts);
        self::assertNotContains($sprint_empty_id, $not_empty_artifacts);
        self::assertEqualsCanonicalizing(
            [$release_with_5_id, $release_with_3_id, $sprint_with_3_id],
            $not_empty_artifacts
        );

        $artifacts_with_value_different = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                2,
                'initial_effort != 5',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(4, $artifacts_with_value_different);
        self::assertNotContains($release_with_5_id, $artifacts_with_value_different);
        self::assertEqualsCanonicalizing(
            [$release_empty_id, $sprint_empty_id, $release_with_3_id, $sprint_with_3_id],
            $artifacts_with_value_different
        );

        $or_artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                3,
                "initial_effort != 5 OR initial_effort != ''",
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(5, $or_artifacts);
        self::assertEqualsCanonicalizing(
            [$release_empty_id, $sprint_empty_id, $release_with_5_id, $release_with_3_id, $sprint_with_3_id],
            $or_artifacts
        );
    }

    public function testLesserThanComparison(): void
    {
        $release_with_3_id = $this->database_builder->buildArtifact($this->release_tracker->getId());
        $release_with_5_id = $this->database_builder->buildArtifact($this->release_tracker->getId());
        $sprint_with_3_id  = $this->database_builder->buildArtifact($this->sprint_tracker->getId());
        $sprint_empty_id   = $this->database_builder->buildArtifact($this->sprint_tracker->getId());

        $release_with_3_changeset_id = $this->database_builder->buildLastChangeset($release_with_3_id);
        $release_with_5_changeset_id = $this->database_builder->buildLastChangeset($release_with_5_id);
        $sprint_changeset_id         = $this->database_builder->buildLastChangeset($sprint_with_3_id);
        $this->database_builder->buildLastChangeset($sprint_empty_id);

        $this->database_builder->buildIntValue($release_with_3_changeset_id, $this->release_initial_effort_field_id, 3);
        $this->database_builder->buildIntValue($release_with_5_changeset_id, $this->release_initial_effort_field_id, 5);
        $this->database_builder->buildFloatValue($sprint_changeset_id, $this->sprint_initial_effort_field_id, 3);

        $lesser_than_artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                'initial_effort < 5',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(2, $lesser_than_artifacts);
        self::assertNotContains($release_with_5_id, $lesser_than_artifacts);
        self::assertNotContains($sprint_empty_id, $lesser_than_artifacts);
        self::assertEqualsCanonicalizing([$release_with_3_id, $sprint_with_3_id], $lesser_than_artifacts);

        $lesser_than_or_equals_artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                2,
                'initial_effort <= 5',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(3, $lesser_than_or_equals_artifacts);
        self::assertNotContains($sprint_empty_id, $lesser_than_or_equals_artifacts);
        self::assertEqualsCanonicalizing(
            [$release_with_5_id, $release_with_3_id, $sprint_with_3_id],
            $lesser_than_or_equals_artifacts
        );

        $or_artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                3,
                'initial_effort < 5 OR initial_effort < 8',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(3, $or_artifacts);
        self::assertNotContains($sprint_empty_id, $or_artifacts);
        self::assertEqualsCanonicalizing([$release_with_5_id, $release_with_3_id, $sprint_with_3_id], $or_artifacts);

        $or_equals_artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                4,
                'initial_effort <= 5 OR initial_effort <= 8',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(3, $or_equals_artifacts);
        self::assertNotContains($sprint_empty_id, $or_equals_artifacts);
        self::assertEqualsCanonicalizing([$release_with_5_id, $release_with_3_id, $sprint_with_3_id], $or_equals_artifacts);
    }

    public function testGreaterThanComparison(): void
    {
        $release_with_8_id = $this->database_builder->buildArtifact($this->release_tracker->getId());
        $release_with_5_id = $this->database_builder->buildArtifact($this->release_tracker->getId());
        $sprint_with_8_id  = $this->database_builder->buildArtifact($this->sprint_tracker->getId());
        $sprint_empty_id   = $this->database_builder->buildArtifact($this->sprint_tracker->getId());

        $release_with_8_changeset_id = $this->database_builder->buildLastChangeset($release_with_8_id);
        $release_with_5_changeset_id = $this->database_builder->buildLastChangeset($release_with_5_id);
        $sprint_changeset_id         = $this->database_builder->buildLastChangeset($sprint_with_8_id);
        $this->database_builder->buildLastChangeset($sprint_empty_id);

        $this->database_builder->buildIntValue($release_with_8_changeset_id, $this->release_initial_effort_field_id, 8);
        $this->database_builder->buildIntValue($release_with_5_changeset_id, $this->release_initial_effort_field_id, 5);
        $this->database_builder->buildFloatValue($sprint_changeset_id, $this->sprint_initial_effort_field_id, 8);

        $greater_than_artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                'initial_effort > 5',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(2, $greater_than_artifacts);
        self::assertNotContains($release_with_5_id, $greater_than_artifacts);
        self::assertNotContains($sprint_empty_id, $greater_than_artifacts);
        self::assertEqualsCanonicalizing([$release_with_8_id, $sprint_with_8_id], $greater_than_artifacts);

        $greater_than_or_equals_artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                2,
                'initial_effort >= 5',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(3, $greater_than_or_equals_artifacts);
        self::assertNotContains($sprint_empty_id, $greater_than_or_equals_artifacts);
        self::assertEqualsCanonicalizing([$release_with_5_id, $release_with_8_id, $sprint_with_8_id], $greater_than_or_equals_artifacts);

        $or_artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                3,
                'initial_effort > 3 OR initial_effort > 5',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(3, $or_artifacts);
        self::assertNotContains($sprint_empty_id, $or_artifacts);
        self::assertEqualsCanonicalizing([$release_with_5_id, $release_with_8_id, $sprint_with_8_id], $or_artifacts);

        $or_equals_artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                4,
                'initial_effort >= 3 OR initial_effort >= 5',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(3, $or_equals_artifacts);
        self::assertNotContains($sprint_empty_id, $or_equals_artifacts);
        self::assertEqualsCanonicalizing([$release_with_5_id, $release_with_8_id, $sprint_with_8_id], $or_equals_artifacts);
    }

    public function testInvalidFieldComparison(): void
    {
        $this->expectException(SearchablesAreInvalidException::class);
        $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                3,
                "initial_effort=''",
                [$this->release_tracker, $this->task_tracker]
            ),
            $this->project_member
        );
    }

    public function testUserCanNotReadAnyField(): void
    {
        $this->expectException(SearchablesDoNotExistException::class);
        $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                3,
                "initial_effort=''",
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->outsider_user
        );
    }

    public function testUserCanNotReadEpicField(): void
    {
        $release_empty_id = $this->database_builder->buildArtifact($this->release_tracker->getId());
        $this->database_builder->buildLastChangeset($release_empty_id);

        $epic_empty_id = $this->database_builder->buildArtifact($this->epic_tracker->getId());
        $this->database_builder->buildLastChangeset($epic_empty_id);

        $artifact_user_can_read = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                3,
                "initial_effort=''",
                [$this->epic_tracker, $this->release_tracker]
            ),
            $this->project_member
        );

        self::assertCount(1, $artifact_user_can_read);
        self::assertEqualsCanonicalizing([$release_empty_id], $artifact_user_can_read);
    }

    private function getFactory(): CrossTrackerArtifactReportFactory
    {
        $user_manager = UserManager::instance();

        $report_config = new TrackerReportConfig(
            new TrackerReportConfigDao()
        );

        $parser = new ParserCacheProxy(new Parser());

        $validator = new ExpertQueryValidator(
            $parser,
            new SizeValidatorVisitor($report_config->getExpertQueryLimit())
        );

        $date_validator                 = new DateFormatValidator(new EmptyStringForbidden(), DateFormat::DATETIME);
        $list_value_validator           = new ListValueValidator(new EmptyStringAllowed(), $user_manager);
        $list_value_validator_not_empty = new ListValueValidator(new EmptyStringForbidden(), $user_manager);

        $form_element_factory          = Tracker_FormElementFactory::instance();
        $invalid_comparisons_collector = new InvalidTermCollectorVisitor(
            new InvalidSearchableCollectorVisitor(
                new MetadataChecker(
                    new MetadataUsageChecker(
                        $form_element_factory,
                        new Tracker_Semantic_TitleDao(),
                        new Tracker_Semantic_DescriptionDao(),
                        new Tracker_Semantic_StatusDao(),
                        new Tracker_Semantic_ContributorDao()
                    )
                ),
                new FieldUsageChecker($form_element_factory, $form_element_factory),
            ),
            new EqualComparisonChecker($date_validator, $list_value_validator),
            new NotEqualComparisonChecker($date_validator, $list_value_validator),
            new GreaterThanComparisonChecker($date_validator, $list_value_validator),
            new GreaterThanOrEqualComparisonChecker($date_validator, $list_value_validator),
            new LesserThanComparisonChecker($date_validator, $list_value_validator),
            new LesserThanOrEqualComparisonChecker($date_validator, $list_value_validator),
            new BetweenComparisonChecker($date_validator, $list_value_validator),
            new InComparisonChecker($date_validator, $list_value_validator_not_empty),
            new NotInComparisonChecker($date_validator, $list_value_validator_not_empty),
            new ArtifactLinkTypeChecker(
                new TypePresenterFactory(
                    new TypeDao(),
                    new ArtifactLinksUsageDao(),
                ),
            ),
        );

        $submitted_on_alias_field     = 'tracker_artifact.submitted_on';
        $last_update_date_alias_field = 'last_changeset.submitted_on';
        $submitted_by_alias_field     = 'tracker_artifact.submitted_by';
        $last_update_by_alias_field   = 'last_changeset.submitted_by';

        $date_value_extractor    = new Date\DateValueExtractor();
        $date_time_value_rounder = new DateTimeValueRounder();
        $list_value_extractor    = new ListValueExtractor();
        $artifact_factory        = Tracker_ArtifactFactory::instance();
        $query_builder_visitor   = new QueryBuilderVisitor(
            new FromWhereSearchableVisitor(),
            new Metadata\EqualComparisonFromWhereBuilder(
                new Title\EqualComparisonFromWhereBuilder(),
                new Description\EqualComparisonFromWhereBuilder(),
                new Status\EqualComparisonFromWhereBuilder(),
                new Date\EqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\EqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                ),
                new Users\EqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager,
                    $submitted_by_alias_field
                ),
                new Users\EqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager,
                    $last_update_by_alias_field
                ),
                new Metadata\Semantic\AssignedTo\EqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager
                )
            ),
            new Metadata\NotEqualComparisonFromWhereBuilder(
                new Title\NotEqualComparisonFromWhereBuilder(),
                new Description\NotEqualComparisonFromWhereBuilder(),
                new Status\NotEqualComparisonFromWhereBuilder(),
                new Date\NotEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\NotEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                ),
                new Users\NotEqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager,
                    $submitted_by_alias_field
                ),
                new Users\NotEqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager,
                    $last_update_by_alias_field
                ),
                new AssignedTo\NotEqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager
                )
            ),
            new Metadata\GreaterThanComparisonFromWhereBuilder(
                new Date\GreaterThanComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\GreaterThanComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                )
            ),
            new Metadata\GreaterThanOrEqualComparisonFromWhereBuilder(
                new Date\GreaterThanOrEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\GreaterThanOrEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                )
            ),
            new Metadata\LesserThanComparisonFromWhereBuilder(
                new Date\LesserThanComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\LesserThanComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                )
            ),
            new Metadata\LesserThanOrEqualComparisonFromWhereBuilder(
                new Date\LesserThanOrEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\LesserThanOrEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                )
            ),
            new Metadata\BetweenComparisonFromWhereBuilder(
                new Date\BetweenComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\BetweenComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                )
            ),
            new Metadata\InComparisonFromWhereBuilder(
                new Users\InComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager,
                    $submitted_by_alias_field
                ),
                new Users\InComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager,
                    $last_update_by_alias_field
                ),
                new AssignedTo\InComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager
                )
            ),
            new Metadata\NotInComparisonFromWhereBuilder(
                new Users\NotInComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager,
                    $submitted_by_alias_field
                ),
                new Users\NotInComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager,
                    $last_update_by_alias_field
                ),
                new AssignedTo\NotInComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager
                )
            ),
            new ReverseLinkFromWhereBuilder($artifact_factory),
            new ForwardLinkFromWhereBuilder($artifact_factory),
            new Field\FieldFromWhereBuilder(
                $form_element_factory,
                $form_element_factory,
                new Field\Numeric\NumericFromWhereBuilder()
            ),
        );

        return new CrossTrackerArtifactReportFactory(
            new CrossTrackerArtifactReportDao(),
            $artifact_factory,
            $validator,
            $query_builder_visitor,
            $parser,
            new CrossTrackerExpertQueryReportDao(),
            $invalid_comparisons_collector
        );
    }
}
