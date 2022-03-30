<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Baseline\Adapter;

require_once __DIR__ . '/../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tuleap\Baseline\Support\CurrentUserContext;

abstract class SemanticValueAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use CurrentUserContext;

    /** @var SemanticValueAdapter */
    protected $adapter;

    /** @var SemanticFieldRepository|MockInterface */
    protected $semantic_field_repository;

    /** @before */
    protected function createInstance(): void
    {
        $this->semantic_field_repository = Mockery::mock(SemanticFieldRepository::class);
        $this->adapter                   = new SemanticValueAdapter($this->semantic_field_repository);
    }

    /** @var Tracker_Artifact_Changeset|MockInterface */
    protected $changeset;

    /** @var Tracker|MockInterface */
    protected $tracker;

    /** @before */
    protected function createEntities(): void
    {
        $this->changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->tracker   = Mockery::mock(Tracker::class);
    }

    /**
     * @return Tracker_Artifact_ChangesetValue|MockInterface
     */
    protected function mockChangesetValue($value): Tracker_Artifact_ChangesetValue
    {
        return Mockery::mock(Tracker_Artifact_ChangesetValue::class)
            ->shouldReceive('getValue')
            ->andReturn($value)
            ->getMock();
    }
}
