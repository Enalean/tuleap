<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\FRS\Events;

use FRSRelease;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class GetReleaseNotesLinkTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var FRSRelease */
    private $release;
    /** @var GetReleaseNotesLink */
    private $event;

    protected function setUp(): void
    {
        $this->release = M::mock(FRSRelease::class)->shouldReceive("getReleaseID")->andReturn(124)->getMock();
        $this->event   = new GetReleaseNotesLink($this->release);
    }

    public function testItDefaultsToCoreFRSURL(): void
    {
        $this->assertEquals("/file/shownotes.php?release_id=124", $this->event->getUrl());
    }

    public function testItReturnsURLProvidedByPlugin(): void
    {
        $plugin_url = "/piarhemia.com/124/squatinoidei";
        $this->event->setUrl($plugin_url);
        $this->assertEquals($plugin_url, $this->event->getUrl());
    }
}
