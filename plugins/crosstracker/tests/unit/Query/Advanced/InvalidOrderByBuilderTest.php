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

namespace Tuleap\CrossTracker\Query\Advanced;

use BaseLanguageFactory;
use TestHelper;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\DuckTypedField\DuckTypedFieldChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\ArtifactIdMetadataChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\ArtifactSubmitterChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\AssignedToChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\InvalidMetadataChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\MetadataChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\StatusChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\SubmissionDateChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\TextSemanticChecker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\LegacyTabTranslationsSupport;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\List\OpenListValueDao;
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
use Tuleap\Tracker\Semantic\Contributor\TrackerSemanticContributorFactory;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\MultiSelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Permission\RetrieveUserPermissionOnFieldsStub;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveSemanticStatusFieldStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Tracker;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InvalidOrderByBuilderTest extends TestCase
{
    use LegacyTabTranslationsSupport;

    /**
     * @var Tracker[]
     */
    private array $trackers = [];
    private RetrieveUsedFields $used_field_retriever;
    private RetrieveSemanticStatusFieldStub $status_field_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->used_field_retriever   = RetrieveUsedFieldsStub::withNoFields();
        $this->status_field_retriever = RetrieveSemanticStatusFieldStub::build();
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
                new InvalidMetadataChecker(
                    new TextSemanticChecker(),
                    new StatusChecker(),
                    new AssignedToChecker($user_manager),
                    new ArtifactSubmitterChecker($user_manager),
                    new SubmissionDateChecker(),
                    new ArtifactIdMetadataChecker(),
                ),
                new InvalidOrderByListChecker(
                    $this->status_field_retriever,
                    new ContributorFieldRetriever(TrackerSemanticContributorFactory::instance()),
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

    public function testItReturnsErrorIfSemanticListWithMultipleValues(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(646)->build();
        $this->status_field_retriever->withField(
            MultiSelectboxFieldBuilder::aMultiSelectboxField(102)
                ->inTracker($tracker)
                ->build()
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

    public function testItReturnsNothingWhenFieldIsNotFoundInAnyTracker(): void
    {
        $result = $this->checkOrderBy(new OrderBy(new Field('my_field'), OrderByDirection::ASCENDING));
        self::assertNull($result);
    }

    public function testItReturnsErrorIfFieldIsMultipleValueList(): void
    {
        $tracker                    = TrackerTestBuilder::aTracker()->withId(1)->build();
        $this->trackers             = [$tracker];
        $this->used_field_retriever = RetrieveUsedFieldsStub::withFields(
            ListStaticBindBuilder::aStaticBind(
                MultiSelectboxFieldBuilder::aMultiSelectboxField(102)
                    ->inTracker($tracker)
                    ->withName('my_field')
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
                SelectboxFieldBuilder::aSelectboxField(102)
                    ->inTracker($tracker)
                    ->withName('my_field')
                    ->build()
            )->build()->getField()
        );
        $result                     = $this->checkOrderBy(new OrderBy(new Field('my_field'), OrderByDirection::ASCENDING));
        self::assertNull($result);
    }
}
