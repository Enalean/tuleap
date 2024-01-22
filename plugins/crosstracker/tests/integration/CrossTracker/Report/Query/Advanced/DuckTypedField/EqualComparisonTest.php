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

use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_Semantic_ContributorDao;
use Tracker_Semantic_DescriptionDao;
use Tracker_Semantic_StatusDao;
use Tracker_Semantic_TitleDao;
use TrackerFactory;
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
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
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
use Tuleap\Tracker\Report\Query\Advanced\SizeValidatorVisitor;
use Tuleap\Tracker\Report\TrackerReportConfig;
use Tuleap\Tracker\Report\TrackerReportConfigDao;
use UserManager;

final class EqualComparisonTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private int $release_id;
    private int $sprint_id;

    public static function tearDownAfterClass(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('DELETE FROM tracker_artifact');
        $db->run('DELETE FROM tracker_field');
        $db->run('DELETE FROM tracker_field_float');
        $db->run('DELETE FROM tracker_field_int');
        $db->run('DELETE FROM tracker_field_computed');
        $db->run('DELETE FROM tracker_changeset');
        $db->run('DELETE FROM tracker_changeset_value');
        $db->run('DELETE FROM tracker_changeset_value_int');
        $db->run('DELETE FROM tracker_changeset_value_float');
    }

    protected function setUp(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        \ForgeConfig::set("feature_flag_" . SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS, '1');
        $builder = new DatabaseBuilder($db);

        $project_id         = $builder->buildProject();
        $release_tracker_id = $builder->buildTracker($project_id, "Release");
        $sprint_tracker_id  = $builder->buildTracker($project_id, "Sprint");
        $task_tracker_id    = $builder->buildTracker($project_id, "Task");

        $initial_effort_int_field   = $builder->buildIntField($release_tracker_id);
        $initial_effort_float_field = $builder->buildFloatField($sprint_tracker_id);
        $builder->buildComputedField($task_tracker_id);

        $this->release_id = $builder->buildArtifact($release_tracker_id);
        $this->sprint_id  = $builder->buildArtifact($sprint_tracker_id);
        $task             = $builder->buildArtifact($task_tracker_id);

        $release_changeset_id = $builder->buildLastChangeset($this->release_id);
        $sprint_changeset_id  = $builder->buildLastChangeset($this->sprint_id);
        $builder->buildLastChangeset($task);

        $builder->buildIntValue($release_changeset_id, $initial_effort_int_field, 50);
        $builder->buildFloatValue($sprint_changeset_id, $initial_effort_float_field, 50);

        $tracker_factory        = TrackerFactory::instance();
        $this->valid_trackers   =
            [
                $tracker_factory->getTrackerById($release_tracker_id),
                $tracker_factory->getTrackerById($sprint_tracker_id),
            ];
        $this->invalid_trackers =
            [
                $tracker_factory->getTrackerById($release_tracker_id),
                $tracker_factory->getTrackerById($task_tracker_id),
            ];
    }

    public function testEqualNothingComparison(): void
    {
        $report    = new CrossTrackerReport(1, "initial_effort=''", $this->valid_trackers);
        $user      = UserTestBuilder::aUser()->withId(105)->build();
        $artifacts = $this->getFactory()->getArtifactsMatchingReport($report, $user, 5, 0);

        self::assertEmpty($artifacts->getArtifacts());
    }

    public function testEqualComparison(): void
    {
        $report    = new CrossTrackerReport(2, "initial_effort=50", $this->valid_trackers);
        $user      = UserTestBuilder::aUser()->withId(105)->build();
        $artifacts = $this->getFactory()->getArtifactsMatchingReport($report, $user, 5, 0);

        self::assertSame($this->release_id, $artifacts->getArtifacts()[1]->getId());
        self::assertSame($this->sprint_id, $artifacts->getArtifacts()[0]->getId());
    }

    public function testInvalidFieldComparison(): void
    {
        $report = new CrossTrackerReport(3, "initial_effort=''", $this->invalid_trackers);
        $user   = UserTestBuilder::aUser()->withId(105)->build();
        $this->expectException(SearchablesAreInvalidException::class);
        $this->getFactory()->getArtifactsMatchingReport($report, $user, 5, 0);
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
            new Field\EqualComparisonFromWhereBuilder(
                $form_element_factory,
                $form_element_factory,
                new Field\Numeric\EqualComparisonFromWhereBuilder(),
            ),
            new Field\NotEqualComparisonFromWhereBuilder(),
            new Field\GreaterThanComparisonFromWhereBuilder(),
            new Field\GreaterThanOrEqualComparisonFromWhereBuilder(),
            new Field\LesserThanComparisonFromWhereBuilder(),
            new Field\LesserThanOrEqualComparisonFromWhereBuilder(),
            new Field\BetweenComparisonFromWhereBuilder(),
            new Field\InComparisonFromWhereBuilder(),
            new Field\NotInComparisonFromWhereBuilder()
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
