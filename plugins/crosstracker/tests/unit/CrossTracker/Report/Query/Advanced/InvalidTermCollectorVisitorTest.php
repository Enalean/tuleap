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

namespace Tuleap\CrossTracker\Report\Query\Advanced;

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
use Tuleap\CrossTracker\SearchOnDuckTypedFieldsConfig;
use Tuleap\CrossTracker\Tests\Stub\MetadataCheckerStub;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Logical;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotInComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Parenthesis;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
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
use Tuleap\Tracker\Report\Query\Advanced\InvalidSearchablesCollection;
use Tuleap\Tracker\Test\Builders\TrackerExternalFormElementBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementFloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementIntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

final class InvalidTermCollectorVisitorTest extends TestCase
{
    use ForgeConfigSandbox;

    private const FIELD_NAME = 'a_field';
    private MetadataCheckerStub $metadata_checker;
    private InvalidSearchablesCollection $invalid_searchable_collection;
    private Comparison $comparison;
    private ?Logical $parsed_query;
    private \PFUser $user;
    private \Tracker $first_tracker;
    private \Tracker $second_tracker;
    private RetrieveUsedFieldsStub $fields_retriever;

    protected function setUp(): void
    {
        \ForgeConfig::setFeatureFlag(SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS, '1');

        $this->first_tracker  = TrackerTestBuilder::aTracker()->withId(67)->build();
        $this->second_tracker = TrackerTestBuilder::aTracker()->withId(21)->build();
        $this->user           = UserTestBuilder::buildWithId(443);

        $this->metadata_checker = MetadataCheckerStub::withValidMetadata();
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            TrackerFormElementIntFieldBuilder::anIntField(628)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TrackerFormElementFloatFieldBuilder::aFloatField(274)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );

        $this->comparison   = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper(12));
        $this->parsed_query = null;

        $this->invalid_searchable_collection = new InvalidSearchablesCollection();
    }

    private function check(): void
    {
        $user_manager = $this->createStub(\UserManager::class);

        $date_validator                 = new DateFormatValidator(new EmptyStringForbidden(), DateFormat::DATETIME);
        $list_value_validator           = new ListValueValidator(new EmptyStringAllowed(), $user_manager);
        $list_value_validator_not_empty = new ListValueValidator(new EmptyStringForbidden(), $user_manager);

        $collector = new InvalidTermCollectorVisitor(
            new InvalidSearchableCollectorVisitor(
                $this->metadata_checker,
                new FieldUsageChecker($this->fields_retriever, RetrieveFieldTypeStub::withDetectionOfType())
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
                    $this->createStub(TypeDao::class),
                    $this->createStub(ArtifactLinksUsageDao::class)
                )
            ),
            new InComparisonVisitor(),
            new EqualComparisonVisitor(),
            new LesserThanOrEqualComparisonVisitor(),
            new LesserThanComparisonVisitor(),
            new NotInComparisonVisitor(),
            new GreaterThanComparisonVisitor(),
            new BetweenComparisonVisitor(),
            new GreaterThanOrEqualComparisonVisitor(),
            new NotEqualComparisonVisitor()
        );
        $collector->collectErrors(
            $this->parsed_query ?? new AndExpression($this->comparison),
            $this->invalid_searchable_collection,
            [$this->first_tracker, $this->second_tracker],
            $this->user
        );
    }

    public function testItAddsFieldToInvalidCollectionWhenFFIsOff(): void
    {
        \ForgeConfig::clearFeatureFlag(SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS);
        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getNonexistentSearchables());
    }

    public function testItAddsNotSupportedFieldToInvalidCollection(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            TrackerExternalFormElementBuilder::anExternalField(900)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItAddsFieldNotFoundToInvalidCollection(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withNoFields();

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getNonexistentSearchables());
    }

    public function testItAddsFieldUserCanNotReadToInvalidCollection(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            TrackerFormElementIntFieldBuilder::anIntField(628)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, false)
                ->build()
        );

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getNonexistentSearchables());
    }

    public function testItChecksFieldIsValid(): void
    {
        $this->check();
        self::assertFalse($this->invalid_searchable_collection->hasInvalidSearchable());
    }

    public static function generateInvalidFloatComparisons(): iterable
    {
        $field       = new Field(self::FIELD_NAME);
        $empty_value = new SimpleValueWrapper('');
        $valid_value = new SimpleValueWrapper(10.5);
        $now         = new CurrentDateTimeValueWrapper(null, null);

        $open = new StatusOpenValueWrapper();
        yield ['< empty string' => new LesserThanComparison($field, $empty_value)];
        yield ['<= empty string' => new LesserThanOrEqualComparison($field, $empty_value)];
        yield ['> empty string' => new GreaterThanComparison($field, $empty_value)];
        yield ['>= empty string' => new GreaterThanOrEqualComparison($field, $empty_value)];
        yield ["BETWEEN('', 10.5)" => new BetweenComparison(
            $field,
            new BetweenValueWrapper($empty_value, $valid_value)
        ),
        ];
        yield ["BETWEEN(10.5, '')" => new BetweenComparison(
            $field,
            new BetweenValueWrapper($valid_value, $empty_value)
        ),
        ];
        yield ['= string value' => new EqualComparison($field, new SimpleValueWrapper('string'))];
        yield ['= NOW()' => new EqualComparison($field, $now)];
        yield ['= OPEN()' => new EqualComparison($field, $open)];
        yield ['IN()' => new InComparison($field, new InValueWrapper([$valid_value]))];
        yield ['NOT IN()' => new NotInComparison($field, new InValueWrapper([$valid_value]))];
    }

    /**
     * @dataProvider generateInvalidFloatComparisons
     */
    public function testItRejectsInvalidComparisons(Comparison $comparison): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            TrackerFormElementIntFieldBuilder::anIntField(975)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TrackerFormElementFloatFieldBuilder::aFloatField(659)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );
        $this->comparison       = $comparison;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItRejectsInvalidComparisonToMyself(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            TrackerFormElementIntFieldBuilder::anIntField(975)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TrackerFormElementFloatFieldBuilder::aFloatField(659)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );
        $user_manager           = $this->createStub(\UserManager::class);
        $user_manager->method('getCurrentUser')->willReturn($this->user);

        $this->comparison = new EqualComparison(
            new Field(self::FIELD_NAME),
            new CurrentUserValueWrapper($user_manager)
        );

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }

    public function testItAddsUnknownMetadataToInvalidCollection(): void
    {
        \ForgeConfig::clearFeatureFlag(SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS);
        $this->comparison = new EqualComparison(new Metadata('unknown'), new SimpleValueWrapper(12));

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getNonexistentSearchables());
    }

    public function testItAllowsValidMetadata(): void
    {
        \ForgeConfig::clearFeatureFlag(SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS);
        $this->comparison = new EqualComparison(new Metadata("title"), new SimpleValueWrapper('romeo'));

        $this->check();
        self::assertFalse($this->invalid_searchable_collection->hasInvalidSearchable());
    }

    public function testItAddsInvalidMetadataToCollection(): void
    {
        \ForgeConfig::clearFeatureFlag(SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS);
        $this->comparison       = new EqualComparison(new Metadata("title"), new SimpleValueWrapper('romeo'));
        $this->metadata_checker = MetadataCheckerStub::withInvalidMetadata();

        $this->check();
        self::assertTrue($this->invalid_searchable_collection->hasInvalidSearchable());
    }

    public static function generateNestedExpressions(): iterable
    {
        $valid_comparison   = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper(5));
        $invalid_comparison = new EqualComparison(
            new Field(self::FIELD_NAME),
            new SimpleValueWrapper('string value')
        );
        yield ['AndOperand' => new AndExpression($valid_comparison, new AndOperand($invalid_comparison))];
        yield ['Tail of AndOperand' => new AndExpression(
            $valid_comparison,
            new AndOperand($valid_comparison, new AndOperand($invalid_comparison))
        ),
        ];
        yield ['OrExpression' => new OrExpression(new AndExpression($invalid_comparison))];
        yield ['OrOperand' => new OrExpression(
            new AndExpression($valid_comparison),
            new OrOperand(new AndExpression($invalid_comparison))
        ),
        ];
        yield ['Tail of OrOperand' => new OrExpression(
            new AndExpression($valid_comparison),
            new OrOperand(
                new AndExpression($valid_comparison),
                new OrOperand(new AndExpression($invalid_comparison))
            )
        ),
        ];
        yield ['Parenthesis' => new AndExpression(
            new Parenthesis(
                new OrExpression(new AndExpression($invalid_comparison))
            )
        ),
        ];
    }

    /**
     * @dataProvider generateNestedExpressions
     */
    public function testItAddsInvalidFieldInNestedExpressions(Logical $parsed_query): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            TrackerFormElementIntFieldBuilder::anIntField(893)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TrackerFormElementIntFieldBuilder::anIntField(120)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
        );
        $this->parsed_query     = $parsed_query;

        $this->check();
        self::assertNotEmpty($this->invalid_searchable_collection->getInvalidSearchableErrors());
    }
}
