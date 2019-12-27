<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

require_once(__DIR__ . "/../TransitionFactory.php");

use DataAccessQueryException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Transition_PostAction_Field_FloatDao;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Workflow\PostAction\Update\SetFloatValue;
use Tuleap\Tracker\Workflow\PostAction\Update\TransitionFactory;

class SetFloatValueRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var SetFloatValueRepository
     */
    private $set_float_value_repository;

    /**
     * @var MockInterface
     */
    private $set_float_value_dao;

    /**
     * @before
     */
    public function createRepository()
    {
        $this->set_float_value_dao = Mockery::mock(Transition_PostAction_Field_FloatDao::class);

        $this->set_float_value_repository = new SetFloatValueRepository(
            $this->set_float_value_dao,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testCreateCreatesGivenSetFloatValueOnGivenTransition()
    {
        $this->set_float_value_dao->shouldReceive('create')
            ->with(1)
            ->andReturn(9);
        $this->set_float_value_dao->shouldReceive('updatePostAction')
            ->with(9, 43, 1.23);

        $transition      = TransitionFactory::buildATransitionWithId(1);
        $set_float_value = new SetFloatValue(43, 1.23);

        $this->set_float_value_repository->create($transition, $set_float_value);
    }

    public function testCreateThrowsWhenCreationFail()
    {
        $this->set_float_value_dao->shouldReceive('create')
            ->andReturn(false);

        $transition      = TransitionFactory::buildATransition();
        $set_float_value = new SetFloatValue(43, 1.23);

        $this->expectException(DataAccessQueryException::class);

        $this->set_float_value_repository->create($transition, $set_float_value);
    }

    public function testDeleteAllByTransitionDeletesExpectedTransitions()
    {
        $this->set_float_value_dao
            ->shouldReceive('deletePostActionsByTransitionId')
            ->with(1)
            ->andReturn(true);
        $transition = TransitionFactory::buildATransitionWithId(1);
        $this->set_float_value_repository->deleteAllByTransition($transition);
    }

    public function testDeleteAllByTransitionThrowsIfDeleteFail()
    {
        $this->set_float_value_dao
            ->shouldReceive('deletePostActionsByTransitionId')
            ->andReturn(false);
        $transition = TransitionFactory::buildATransition();

        $this->expectException(DataAccessQueryException::class);

        $this->set_float_value_repository->deleteAllByTransition($transition);
    }
}
