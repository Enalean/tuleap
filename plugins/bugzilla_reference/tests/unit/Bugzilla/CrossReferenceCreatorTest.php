<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Bugzilla;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class CrossReferenceCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function setUp(): void
    {
        parent::setUp();

        $this->cross_reference_dao = \Mockery::spy(\CrossReferenceDao::class);
        $this->rest_reference_dao  = \Mockery::spy(\Tuleap\Bugzilla\Reference\RESTReferenceCreator::class);

        $this->cross_reference_creator = new CrossReferenceCreator(
            $this->cross_reference_dao,
            $this->rest_reference_dao
        );
    }

    public function testItCreatesReference()
    {
        $this->cross_reference_dao->shouldReceive('fullReferenceExistInDb')->andReturn(false);

        $this->cross_reference_dao->shouldReceive('createDbCrossRef')->once();
        $this->rest_reference_dao->shouldReceive('create')->once();

        $cross_reference    = \Mockery::spy(\CrossReference::class);
        $bugzilla_reference = \Mockery::spy(\Tuleap\Bugzilla\Reference\Reference::class);

        $this->cross_reference_creator->create($cross_reference, $bugzilla_reference);
    }

    public function testItDoesNotCreateReferenceIfAlreadyExisting()
    {
        $this->cross_reference_dao->shouldReceive('fullReferenceExistInDb')->andReturn(true);

        $this->cross_reference_dao->shouldReceive('createDbCrossRef')->never();
        $this->rest_reference_dao->shouldReceive('create')->never();

        $cross_reference    = \Mockery::spy(\CrossReference::class);
        $bugzilla_reference = \Mockery::spy(\Tuleap\Bugzilla\Reference\Reference::class);

        $this->cross_reference_creator->create($cross_reference, $bugzilla_reference);
    }
}
