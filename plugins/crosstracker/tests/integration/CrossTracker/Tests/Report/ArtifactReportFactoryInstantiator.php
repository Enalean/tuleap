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

namespace Tuleap\CrossTracker\Tests\Report;

use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_Semantic_ContributorDao;
use Tracker_Semantic_DescriptionDao;
use Tracker_Semantic_StatusDao;
use Tracker_Semantic_TitleDao;
use Tuleap\CrossTracker\CrossTrackerArtifactReportDao;
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
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\ExpertQueryValidator;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Parser;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ArtifactLink\ArtifactLinkTypeChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\BetweenComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFormatValidator;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringAllowed;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringForbidden;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\GreaterThanComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\GreaterThanOrEqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\LesserThanComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\LesserThanOrEqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\NotEqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\NotInComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\ParserCacheProxy;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;
use Tuleap\Tracker\Report\Query\Advanced\SizeValidatorVisitor;
use Tuleap\Tracker\Report\TrackerReportConfig;
use Tuleap\Tracker\Report\TrackerReportConfigDao;
use UserManager;

final class ArtifactReportFactoryInstantiator
{
    public function getFactory(): CrossTrackerArtifactReportFactory
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
            new InComparisonVisitor(),
            new EqualComparisonVisitor(),
            new LesserThanOrEqualComparisonVisitor(),
            new LesserThanComparisonVisitor(),
            new NotInComparisonVisitor(),
            new GreaterThanComparisonVisitor(),
            new BetweenComparisonVisitor(),
            new GreaterThanOrEqualComparisonVisitor(),
            new NotEqualComparisonVisitor(),
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
