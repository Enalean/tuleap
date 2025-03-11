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

namespace Tuleap\CrossTracker\Report\Query\Advanced;

use BaseLanguageFactory;
use TestHelper;
use Tracker;
use Tracker_Semantic_ContributorFactory;
use Tracker_Semantic_Status;
use Tracker_Semantic_StatusFactory;
use Tuleap\CrossTracker\Field\ReadableFieldRetriever;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\DuckTypedField\DuckTypedFieldChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\ArtifactIdMetadataChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\ArtifactSubmitterChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\AssignedToChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\CheckMetadataUsage;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\InvalidMetadataChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\MetadataChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\StatusChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\SubmissionDateChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\TextSemanticChecker;
use Tuleap\CrossTracker\Tests\Stub\MetadataCheckerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\LegacyTabTranslationsSupport;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListValueDao;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrderBy;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrderByDirection;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\File\FileFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FloatFields\FloatFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Integer\IntegerFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractorForOpenList;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Text\TextFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidOrderBy;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use Tuleap\Tracker\Semantic\Contributor\ContributorFieldRetriever;
use Tuleap\Tracker\Semantic\Status\StatusFieldRetriever;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Permission\RetrieveUserPermissionOnFieldsStub;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InvalidOrderByBuilderTest extends TestCase
{
    use LegacyTabTranslationsSupport;

    private CheckMetadataUsage $metadata_checker;
    /**
     * @var Tracker[]
     */
    private array $trackers = [];
    private RetrieveUsedFields $used_field_retriever;

    protected function setUp(): void
    {
        $this->metadata_checker     = MetadataCheckerStub::withValidMetadata();
        $this->used_field_retriever = RetrieveUsedFieldsStub::withNoFields();
    }

    protected function tearDown(): void
    {
        Tracker_Semantic_Status::clearInstances();
    }

    private function checkOrderBy(OrderBy $order_by): ?InvalidOrderBy
    {
        $list_field_bind_value_normalizer = new ListFieldBindValueNormalizer();
        $ugroup_label_converter           = new UgroupLabelConverter(
            $list_field_bind_value_normalizer,
            new BaseLanguageFactory()
        );
        $bind_labels_extractor            = new CollectionOfNormalizedBindLabelsExtractor(
            $list_field_bind_value_normalizer,
            $ugroup_label_converter
        );
        $open_list_value_dao              = $this->createMock(OpenListValueDao::class);
        $open_list_value_dao->method('searchByFieldId')->willReturn(TestHelper::emptyDar());
        $user_manager = $this->createStub(UserManager::class);
        $builder      = new InvalidOrderByBuilder(
            new DuckTypedFieldChecker(
                $this->used_field_retriever,
                RetrieveFieldTypeStub::withDetectionOfType(),
                new InvalidFieldChecker(
                    new FloatFieldChecker(),
                    new IntegerFieldChecker(),
                    new TextFieldChecker(),
                    new DateFieldChecker(),
                    new FileFieldChecker(),
                    new ListFieldChecker(
                        $list_field_bind_value_normalizer,
                        $bind_labels_extractor,
                        $ugroup_label_converter
                    ),
                    new ListFieldChecker(
                        $list_field_bind_value_normalizer,
                        new CollectionOfNormalizedBindLabelsExtractorForOpenList(
                            $bind_labels_extractor,
                            $open_list_value_dao,
                            $list_field_bind_value_normalizer,
                        ),
                        $ugroup_label_converter
                    ),
                    new \Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ArtifactSubmitterChecker($user_manager),
                    true,
                ),
                new ReadableFieldRetriever(
                    $this->used_field_retriever,
                    RetrieveUserPermissionOnFieldsStub::build()->withPermissionOn([102], FieldPermissionType::PERMISSION_READ),
                )
            ),
            new MetadataChecker(
                $this->metadata_checker,
                new InvalidMetadataChecker(
                    new TextSemanticChecker(),
                    new StatusChecker(),
                    new AssignedToChecker($user_manager),
                    new ArtifactSubmitterChecker($user_manager),
                    new SubmissionDateChecker(),
                    new ArtifactIdMetadataChecker(),
                ),
                new InvalidOrderByListChecker(
                    new StatusFieldRetriever(Tracker_Semantic_StatusFactory::instance()),
                    new ContributorFieldRetriever(Tracker_Semantic_ContributorFactory::instance()),
                ),
            ),
            $this->trackers,
            UserTestBuilder::buildWithDefaults(),
        );
        return $builder->buildInvalidOrderBy($order_by);
    }

    public function testItReturnsErrorIfSortOnNotAllowedMetadata(): void
    {
        $result = $this->checkOrderBy(new OrderBy(new Metadata('blabla'), OrderByDirection::ASCENDING));
        self::assertNotNull($result);
        self::assertSame('Sorting artifacts by @blabla is not allowed. Please refine your query or check the configuration of the trackers.', $result->message);
    }

    public function testItReturnsErrorIfSortOnSemanticNotDefined(): void
    {
        $this->metadata_checker = MetadataCheckerStub::withInvalidMetadata();
        $result                 = $this->checkOrderBy(new OrderBy(new Metadata('title'), OrderByDirection::ASCENDING));
        self::assertNotNull($result);
        self::assertSame(
            'All trackers involved in the query do not have the semantic title defined. Please refine your query or check the configuration of the trackers.',
            $result->message,
        );
    }

    public function testItReturnsErrorIfSemanticListWithMultipleValues(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        Tracker_Semantic_Status::setInstance(
            new Tracker_Semantic_Status($tracker, ListFieldBuilder::aListField(102)->withMultipleValues()->build()),
            $tracker
        );
        $this->trackers = [$tracker];
        $result         = $this->checkOrderBy(new OrderBy(new Metadata('status'), OrderByDirection::ASCENDING));
        self::assertNotNull($result);
        self::assertSame('@status is a list with multiple values, sorting artifacts by it is not allowed. Please refine your query or check the configuration of the trackers.', $result->message);
    }

    public function testItReturnsNothingIfMetadataIsValid(): void
    {
        $result = $this->checkOrderBy(new OrderBy(new Metadata('status'), OrderByDirection::ASCENDING));
        self::assertNull($result);
    }

    public function testItReturnsErrorIfFieldNotFound(): void
    {
        $result = $this->checkOrderBy(new OrderBy(new Field('my_field'), OrderByDirection::ASCENDING));
        self::assertNotNull($result);
        self::assertSame('The field could not be found in any of the given trackers', $result->message);
    }

    public function testItReturnsErrorIfFieldIsMultipleValueList(): void
    {
        $tracker                    = TrackerTestBuilder::aTracker()->withId(1)->build();
        $this->trackers             = [$tracker];
        $this->used_field_retriever = RetrieveUsedFieldsStub::withFields(
            ListStaticBindBuilder::aStaticBind(
                ListFieldBuilder::aListField(102)
                    ->inTracker($tracker)
                    ->withName('my_field')
                    ->withMultipleValues()
                    ->build()
            )->build()->getField()
        );
        $result                     = $this->checkOrderBy(new OrderBy(new Field('my_field'), OrderByDirection::ASCENDING));
        self::assertNotNull($result);
        self::assertSame('You cannot sort artifacts by my_field, the field is a list with multiple values', $result->message);
    }

    public function testItReturnsNullIfFieldIsValid(): void
    {
        $tracker                    = TrackerTestBuilder::aTracker()->withId(1)->build();
        $this->trackers             = [$tracker];
        $this->used_field_retriever = RetrieveUsedFieldsStub::withFields(
            ListStaticBindBuilder::aStaticBind(
                ListFieldBuilder::aListField(102)
                    ->inTracker($tracker)
                    ->withName('my_field')
                    ->build()
            )->build()->getField()
        );
        $result                     = $this->checkOrderBy(new OrderBy(new Field('my_field'), OrderByDirection::ASCENDING));
        self::assertNull($result);
    }
}
