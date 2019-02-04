<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Hudson;

require_once __DIR__ . '/bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class HudsonJobLazyExceptionHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testHudsonJobIsRetrievedWhenNoErrorExists()
    {
        $hudson_job = \Mockery::mock(\HudsonJob::class);

        $hudson_job_lazy_exception = new HudsonJobLazyExceptionHandler($hudson_job, null);

        $this->assertSame($hudson_job_lazy_exception->getHudsonJob(), $hudson_job);
    }

    public function testExceptionIsThrownWhenErrorExist()
    {
        $exception = \Mockery::mock(\Exception::class);

        $hudson_job_lazy_exception = new HudsonJobLazyExceptionHandler(null, $exception);

        $this->expectException(\Exception::class);

        $hudson_job_lazy_exception->getHudsonJob();
    }

    public function testRuntimeExceptionIsThrownWhenObjectIsIncorrectlyInitializedByDeveloper()
    {
        $hudson_job_lazy_exception = new HudsonJobLazyExceptionHandler(null, null);

        $this->expectException(\RuntimeException::class);

        $hudson_job_lazy_exception->getHudsonJob();
    }
}
