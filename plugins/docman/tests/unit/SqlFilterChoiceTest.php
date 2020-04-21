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

namespace Tuleap\Docman;

use CodendiDataAccess;
use Docman_SqlFilter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SqlFilterChoiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function setUp(): void
    {
        parent::setUp();
        $data_access = \Mockery::mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $data_access->shouldReceive('quoteLikeValueSurround')->with('codex')->andReturns('"%codex%"');
        $data_access->shouldReceive('quoteLikeValueSurround')->with('c*od*ex')->andReturns('"%c*od*ex%"');
        $data_access->shouldReceive('quoteLikeValuePrefix')->with('codex')->andReturns('"%codex"');
        $data_access->shouldReceive('quoteLikeValuePrefix')->with('*codex')->andReturns('"%*codex"');
        CodendiDataAccess::setInstance($data_access);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        CodendiDataAccess::clearInstance();
    }

    public function testItTestSqlFilterChoicePerPattern(): void
    {
        $docmanSf = \Mockery::mock(Docman_SqlFilter::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $this->assertEquals($docmanSf->getSearchType('*codex*'), array('like' => true, 'pattern' => '"%codex%"'));
        $this->assertEquals($docmanSf->getSearchType('*c*od*ex*'), array('like' => true, 'pattern' => '"%c*od*ex%"'));
        $this->assertEquals($docmanSf->getSearchType('*codex'), array('like' => true, 'pattern' => '"%codex"'));
        $this->assertEquals($docmanSf->getSearchType('**codex'), array('like' => true, 'pattern' => '"%*codex"'));
        $this->assertEquals($docmanSf->getSearchType('codex*'), array('like' => false));
        $this->assertEquals($docmanSf->getSearchType('cod*ex*'), array('like' => false));
        $this->assertEquals($docmanSf->getSearchType('codex'), array('like' => false));
    }
}
