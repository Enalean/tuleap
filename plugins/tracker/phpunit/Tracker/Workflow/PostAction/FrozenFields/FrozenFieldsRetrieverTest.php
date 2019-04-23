<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\FrozenFields;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class FrozenFieldsRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Mockery\MockInterface */
    private $frozen_dao;
    /** @var FrozenFieldsRetriever */
    private $frozen_retriever;

    protected function setUp(): void
    {
        $this->frozen_dao       = Mockery::mock(FrozenFieldsDao::class);
        $this->frozen_retriever = new FrozenFieldsRetriever($this->frozen_dao);
    }

    public function testGetFrozenFieldsReturnsASinglePostAction()
    {
        $this->frozen_dao->shouldReceive('searchByTransitionId')->andReturn(
            [
                ['postaction_id' => 72, 'field_id' => 331],
                ['postaction_id' => 72, 'field_id' => 651],
                ['postaction_id' => 72, 'field_id' => 987]
            ]
        );

        $transition           = Mockery::mock(\Transition::class)->shouldReceive(['getId' => 97])->getMock();
        $expected_post_action = new FrozenFields($transition, 72, [331, 651, 987]);

        $result = $this->frozen_retriever->getFrozenFields($transition);
        $this->assertEquals($expected_post_action, $result);
    }

    public function testGetFrozenFieldsThrowsWhenNoPostAction()
    {
        $this->frozen_dao->shouldReceive('searchByTransitionId')->andReturn([]);

        $transition = Mockery::mock(\Transition::class)->shouldReceive(['getId' => 97])->getMock();

        $this->expectException(NoFrozenFieldsPostActionException::class);
        $this->frozen_retriever->getFrozenFields($transition);
    }
}
