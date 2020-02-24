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

namespace Tuleap\Tracker\Workflow\PostAction\Update;

require_once(__DIR__ . "/TransitionFactory.php");

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionUpdater;

class PostActionCollectionUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PostActionCollectionUpdater
     */
    private $collection_updater;

    /**
     * @var MockInterface
     */
    private $post_action_updater1;
    /**
     * @var MockInterface
     */
    private $post_action_updater2;

    /**
     * @before
     */
    public function createUpdater()
    {
        $this->post_action_updater1 = Mockery::mock(PostActionUpdater::class);
        $this->post_action_updater2 = Mockery::mock(PostActionUpdater::class);

        $this->collection_updater = new PostActionCollectionUpdater(
            $this->post_action_updater1,
            $this->post_action_updater2
        );
    }

    public function testUpdateByTransitionDelegatesUpdateToUpdaters()
    {
        $transition = TransitionFactory::buildATransition();

        $action            = new CIBuildValue('http://example.test');
        $action_collection = new PostActionCollection($action);

        $this->post_action_updater1
            ->shouldReceive('updateByTransition')
            ->with($action_collection, $transition);
        $this->post_action_updater2
            ->shouldReceive('updateByTransition')
            ->with($action_collection, $transition);

        $this->collection_updater->updateByTransition($transition, $action_collection);
    }
}
