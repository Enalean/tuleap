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
use ForgeConfig;
use Tracker_Semantic_ContributorFactory;
use Tracker_Semantic_StatusFactory;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\DuckTypedField\DuckTypedFieldChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\ArtifactIdMetadataChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\AssignedToChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\InvalidMetadataChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\MetadataChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\StatusChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\SubmissionDateChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\TextSemanticChecker;
use Tuleap\CrossTracker\Query\CrossTrackerArtifactQueryFactory;
use Tuleap\CrossTracker\Tests\Stub\Query\Advanced\QueryValidation\Metadata\MetadataCheckerStub;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\LegacyTabTranslationsSupport;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListValueDao;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\File\FileFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FloatFields\FloatFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Integer\IntegerFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ArtifactSubmitterChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractorForOpenList;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Text\TextFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesMustBeUniqueException;
use Tuleap\Tracker\Report\Query\Advanced\SelectLimitExceededException;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use Tuleap\Tracker\Semantic\Contributor\ContributorFieldRetriever;
use Tuleap\Tracker\Semantic\Status\StatusFieldRetriever;
use Tuleap\Tracker\Test\Stub\Permission\RetrieveUserPermissionOnFieldsStub;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InvalidSelectablesCollectionBuilderTest extends TestCase
{
    use LegacyTabTranslationsSupport;
    use ForgeConfigSandbox;

    private InvalidSelectablesCollectionBuilder $builder;

    public function setUp(): void
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

        $used_fields   = RetrieveUsedFieldsStub::withNoFields();
        $this->builder = new InvalidSelectablesCollectionBuilder(
            new InvalidSelectablesCollectorVisitor(new DuckTypedFieldChecker(
                $used_fields,
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
                            new OpenListValueDao(),
                            $list_field_bind_value_normalizer,
                        ),
                        $ugroup_label_converter
                    ),
                    new ArtifactSubmitterChecker(UserManager::instance()),
                    true,
                ),
                new ReadableFieldRetriever(
                    $used_fields,
                    RetrieveUserPermissionOnFieldsStub::build(),
                )
            ), new MetadataChecker(
                MetadataCheckerStub::withValidMetadata(),
                new InvalidMetadataChecker(
                    new TextSemanticChecker(),
                    new StatusChecker(),
                    new AssignedToChecker(ProvideAndRetrieveUserStub::build(UserTestBuilder::buildWithDefaults())),
                    new \Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\ArtifactSubmitterChecker(ProvideAndRetrieveUserStub::build(UserTestBuilder::buildWithDefaults())),
                    new SubmissionDateChecker(),
                    new ArtifactIdMetadataChecker(),
                ),
                new InvalidOrderByListChecker(
                    new StatusFieldRetriever(Tracker_Semantic_StatusFactory::instance()),
                    new ContributorFieldRetriever(Tracker_Semantic_ContributorFactory::instance()),
                ),
            )),
            [],
            UserTestBuilder::buildWithDefaults()
        );
    }

    public function testItThrowsIfSelectSameFieldMultipleTimes(): void
    {
        self::expectException(SelectablesMustBeUniqueException::class);
        $this->builder->buildCollectionOfInvalidSelectables([
            new Field('a'), new Field('b'), new Metadata('meta'), new Field('b'),
        ]);
    }

    public function testItThrowsIfSelectSameMetadataMultipleTimes(): void
    {
        self::expectException(SelectablesMustBeUniqueException::class);
        $this->builder->buildCollectionOfInvalidSelectables([
            new Metadata('meta'), new Metadata('meta'),
        ]);
    }

    public function testItThrowIfSelectSizeExceedLimit(): void
    {
        ForgeConfig::set(CrossTrackerArtifactQueryFactory::MAX_SELECT, 1);
        self::expectException(SelectLimitExceededException::class);
        $this->builder->buildCollectionOfInvalidSelectables([
            new Field('a'), new Field('b'), new Metadata('meta'),
        ]);
    }

    public function testItUsesVisitorIfAllIsGood(): void
    {
        self::expectNotToPerformAssertions();
        $this->builder->buildCollectionOfInvalidSelectables([
            new Field('a'), new Field('b'), new Metadata('meta'),
        ]);
    }
}
