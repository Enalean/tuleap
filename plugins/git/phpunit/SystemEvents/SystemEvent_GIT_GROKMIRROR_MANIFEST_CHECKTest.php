<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../bootstrap.php';

class SystemEvent_GIT_GROKMIRROR_MANIFEST_CHECKTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var SystemEvent_GIT_GROKMIRROR_MANIFEST_CHECK */
    private $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manifest_manager = \Mockery::spy(\Git_Mirror_ManifestManager::class);
        $this->event = \Mockery::mock(\SystemEvent_GIT_GROKMIRROR_MANIFEST_CHECK::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $this->event->injectDependencies(
            $this->manifest_manager
        );
    }

    public function testItChecksTheManifest(): void
    {
        $this->manifest_manager->shouldReceive('checkManifestFiles')->once();

        $this->event->process();
    }
}
