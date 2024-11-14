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

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields;

use PHPUnit\Framework\MockObject\MockObject;
use TestHelper;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListValueDao;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\FormElement\Field\ListFields\Bind\BindVisitorStub;

final class CollectionOfNormalizedBindLabelsExtractorForOpenListTest extends TestCase
{
    private CollectionOfNormalizedBindLabelsExtractorForOpenList $extractor;
    private OpenListValueDao&MockObject $dao;

    protected function setUp(): void
    {
        $bind_labels_extractor = BindVisitorStub::build(['first label']);
        $this->dao             = $this->createMock(OpenListValueDao::class);
        $this->extractor       = new CollectionOfNormalizedBindLabelsExtractorForOpenList(
            $bind_labels_extractor,
            $this->dao,
            new ListFieldBindValueNormalizer(),
        );
    }

    public function testItReturnsMergeOfLabelsBindStatic(): void
    {
        $this->dao->expects(self::once())->method('searchByFieldId')->willReturn(TestHelper::argListToDar([
            ['label' => 'my label'],
        ]));

        $results = $this->extractor->extractCollectionOfNormalizedLabels(
            ListStaticBindBuilder::aStaticBind(
                ListFieldBuilder::aListField(1)->build()
            )->build()->getField()
        );

        self::assertEqualsCanonicalizing(['first label', 'my label'], $results);
    }

    public function testItReturnsMergeOfLabelsBindUsers(): void
    {
        $this->dao->expects(self::once())->method('searchByFieldId')->willReturn(TestHelper::argListToDar([
            ['label' => 'my label'],
        ]));

        $results = $this->extractor->extractCollectionOfNormalizedLabels(
            ListUserBindBuilder::aUserBind(
                ListFieldBuilder::aListField(1)->build()
            )->build()->getField()
        );

        self::assertEqualsCanonicalizing(['first label', 'my label'], $results);
    }

    public function testItReturnsOnlyBindExtractorLabelsBindUserGroups(): void
    {
        $results = $this->extractor->extractCollectionOfNormalizedLabels(
            ListUserGroupBindBuilder::aUserGroupBind(
                ListFieldBuilder::aListField(1)->build()
            )->build()->getField()
        );

        self::assertEqualsCanonicalizing(['first label'], $results);
    }
}
