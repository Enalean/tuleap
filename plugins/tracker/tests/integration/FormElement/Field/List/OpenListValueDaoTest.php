<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\List;

use Override;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class OpenListValueDaoTest extends TestIntegrationTestCase
{
    private const int    FIELD_ID            = 123;
    private const string VALUE_A_FIRST_VALUE = 'a first value';
    private const string VALUE_TOTO          = 'toto';
    private const string VALUE_CONSIDERABLY  = 'considerably';

    private OpenListValueDao $dao;
    /**
     * @var array<string, int>
     */
    private array $open_values;

    #[Override]
    protected function setUp(): void
    {
        $this->dao = new OpenListValueDao();

        $db      = DBFactory::getMainTuleapDBConnection()->getDB();
        $builder = new TrackerDatabaseBuilder($db);

        $this->open_values = $builder->buildValuesForStaticOpenListField(self::FIELD_ID, [
            self::VALUE_A_FIRST_VALUE,
            self::VALUE_TOTO,
            self::VALUE_CONSIDERABLY,
        ]);
    }

    public function testItCanRetrieveASingleOpenValue(): void
    {
        $row = $this->dao->searchById(self::FIELD_ID, $this->open_values[self::VALUE_A_FIRST_VALUE]);

        self::assertNotNull($row);
        $this->assertEqualOpenValue($row, self::VALUE_A_FIRST_VALUE);

        self::assertNull($this->dao->searchById(self::FIELD_ID, -1));
    }

    public function testItCanRetrieveAllFieldOpenValues(): void
    {
        $result = $this->dao->searchByFieldId(self::FIELD_ID);
        self::assertCount(3, $result);
        $this->assertEqualOpenValue($result[0], self::VALUE_A_FIRST_VALUE);
        $this->assertEqualOpenValue($result[1], self::VALUE_TOTO);
        $this->assertEqualOpenValue($result[2], self::VALUE_CONSIDERABLY);

        self::assertCount(0, $this->dao->searchByFieldId(-1));
    }

    public function testItCanCreateANewOpenValue(): void
    {
        $id  = $this->dao->create(self::FIELD_ID, 'my new value');
        $row = $this->dao->searchById(self::FIELD_ID, $id);

        self::assertIsArray($row);
        self::assertSame($id, $row['id']);
        self::assertSame('my new value', $row['label']);
        self::assertSame(self::FIELD_ID, $row['field_id']);
        self::assertSame(0, $row['is_hidden']);
    }

    public function testItCannotDuplicateOpenValue(): void
    {
        $id = $this->dao->create(self::FIELD_ID, self::VALUE_A_FIRST_VALUE);
        self::assertSame($this->open_values[self::VALUE_A_FIRST_VALUE], $id);

        $result = $this->dao->searchByKeyword(self::FIELD_ID, self::VALUE_A_FIRST_VALUE);
        self::assertCount(1, $result);
        $this->assertEqualOpenValue($result[0], self::VALUE_A_FIRST_VALUE);
    }

    public function testItCanRetrieveAnOpenValueFromApproximateLabel(): void
    {
        $result = $this->dao->searchByKeyword(self::FIELD_ID, 'value');
        self::assertCount(1, $result);

        $this->assertEqualOpenValue($result[0], self::VALUE_A_FIRST_VALUE);
    }

    public function testItCanRetrieveAnOpenValueFromItsLabel(): void
    {
        $row = $this->dao->searchByExactLabel(self::FIELD_ID, self::VALUE_A_FIRST_VALUE);

        self::assertNotNull($row);
        $this->assertEqualOpenValue($row, self::VALUE_A_FIRST_VALUE);

        self::assertNull($this->dao->searchByExactLabel(self::FIELD_ID, 'non existing'));
    }

    public function testItCanUpdateAnOpenValue(): void
    {
        $this->dao->updateOpenValue($this->open_values[self::VALUE_A_FIRST_VALUE], true, 'Welcomed');
        $row = $this->dao->searchById(self::FIELD_ID, $this->open_values[self::VALUE_A_FIRST_VALUE]);

        self::assertIsArray($row);
        self::assertSame($this->open_values[self::VALUE_A_FIRST_VALUE], $row['id']);
        self::assertSame('Welcomed', $row['label']);
        self::assertSame(self::FIELD_ID, $row['field_id']);
        self::assertSame(1, $row['is_hidden']);
    }

    private function assertEqualOpenValue(array $row, string $label): void
    {
        self::assertSame($this->open_values[$label], $row['id']);
        self::assertSame($label, $row['label']);
        self::assertSame(self::FIELD_ID, $row['field_id']);
        self::assertSame(0, $row['is_hidden']);
    }
}
