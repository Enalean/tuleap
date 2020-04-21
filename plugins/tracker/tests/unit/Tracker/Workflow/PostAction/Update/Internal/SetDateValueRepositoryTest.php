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
use Transition_PostAction_Field_DateDao;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Workflow\PostAction\Update\SetDateValue;
use Tuleap\Tracker\Workflow\PostAction\Update\TransitionFactory;

class SetDateValueRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var SetDateValueRepository
     */
    private $set_date_value_repository;

    /**
     * @var MockInterface
     */
    private $set_date_value_dao;

    /**
     * @before
     */
    public function createRepository()
    {
        $this->set_date_value_dao = Mockery::mock(Transition_PostAction_Field_DateDao::class);

        $this->set_date_value_repository = new SetDateValueRepository(
            $this->set_date_value_dao,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testCreateCreatesGivenSetDateValueOnGivenTransition()
    {
        $this->set_date_value_dao->shouldReceive('create')
            ->with(1)
            ->andReturn(9);
        $this->set_date_value_dao->shouldReceive('updatePostAction')
            ->with(9, 43, 1);

        $transition = TransitionFactory::buildATransitionWithId(1);
        $set_date_value = new SetDateValue(43, 1);

        $this->set_date_value_repository->create($transition, $set_date_value);
    }

    public function testCreateThrowsWhenCreationFail()
    {
        $this->set_date_value_dao->shouldReceive('create')
            ->andReturn(false);

        $transition = TransitionFactory::buildATransition();
        $set_date_value = new SetDateValue(43, 1);

        $this->expectException(DataAccessQueryException::class);

        $this->set_date_value_repository->create($transition, $set_date_value);
    }

    public function testDeleteDeletesByTransitionId()
    {
        $this->set_date_value_dao->shouldReceive('deletePostActionsByTransitionId')
            ->with(1)
            ->andReturn(true);

        $transition = TransitionFactory::buildATransitionWithId(1);

        $this->set_date_value_repository->deleteAllByTransition($transition);
    }


    public function testDeleteThrowsWhenDeletionFails()
    {
        $this->set_date_value_dao->shouldReceive('deletePostActionsByTransitionId')
            ->with(1)
            ->andReturn(false);

        $transition = TransitionFactory::buildATransitionWithId(1);

        $this->expectException(DataAccessQueryException::class);

        $this->set_date_value_repository->deleteAllByTransition($transition);
    }
}
