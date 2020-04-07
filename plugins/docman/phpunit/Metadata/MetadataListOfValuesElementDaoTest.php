<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */
declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class MetadataListOfValuesElementDaoTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testUpdate(): void
    {
        // Data
        $metadataId = 1444;
        $valueId = 1125;
        $name = 'love_value';
        $description = 'desc';
        $rank = 12;
        $status = 'A';

         // Setup
        $dao = \Mockery::mock(Docman_MetadataListOfValuesElementDao::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao->shouldReceive('prepareLoveRanking')->andReturns(15);
        $dao->da = \Mockery::spy(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $dao->da->shouldReceive('quoteSmart')->with($name)->andReturns("'$name'");
        $dao->da->shouldReceive('quoteSmart')->with($description)->andReturns("'$description'");
        $dao->da->shouldReceive('quoteSmart')->with($status)->andReturns("'$status'");

        $sql_update = "UPDATE plugin_docman_metadata_love AS love" .
            " SET love.name = '" . $name . "'" .
            "  , love.description = '" . $description . "'" .
            "  , love.rank = 15" .
            "  , love.status = '" . $status . "'" .
            " WHERE love.value_id = " . $valueId;
        $dao->shouldReceive('update')->with($sql_update)->once()->andReturns(true);

        $val = $dao->updateElement($metadataId, $valueId, $name, $description, $rank, $status);
        $this->assertTrue($val);
    }

    public function testDeleteByMetadataId(): void
    {
        // Data
        $metadataId = 1444;

         // Setup
        $dao = \Mockery::mock(Docman_MetadataListOfValuesElementDao::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao->da = \Mockery::spy(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $sql_update = "UPDATE plugin_docman_metadata_love AS love SET status = 'D' WHERE value_id IN (  SELECT value_id   FROM plugin_docman_metadata_love_md AS lovemd   WHERE lovemd.field_id = " . $metadataId . "     AND lovemd.value_id > 100  )";
        $dao->shouldReceive('update')->with($sql_update)->once()->andReturns(true);

        $val = $dao->deleteByMetadataId($metadataId);
        $this->assertTrue($val);
    }
}
