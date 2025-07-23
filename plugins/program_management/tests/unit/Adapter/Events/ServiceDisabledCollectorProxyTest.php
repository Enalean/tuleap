<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\Events;

use Tuleap\ProgramManagement\ProgramService;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ServiceDisabledCollectorProxyTest extends TestCase
{
    private ServiceDisabledCollectorProxy $proxy;
    private ServiceDisabledCollector $event;

    #[\Override]
    protected function setUp(): void
    {
        $this->event = new ServiceDisabledCollector(
            new \Project(['group_id' => 101, 'group_name' => 'A project', 'unix_group_name' => 'a_project', 'icon_codepoint' => '']),
            ProgramService::SERVICE_SHORTNAME,
            UserTestBuilder::aUser()->build()
        );

        $this->proxy = ServiceDisabledCollectorProxy::fromEvent($this->event);
    }

    public function testItBuildsFromEvent(): void
    {
        self::assertSame($this->event->getProject()->getID(), $this->proxy->getProjectIdentifier()->getId());
        self::assertSame($this->event->getUser()->getID(), $this->proxy->getUserIdentifier()->getId());
    }

    public function testItVerifyEventIsForService(): void
    {
        $this->assertTrue($this->proxy->isForServiceShortName(ProgramService::SERVICE_SHORTNAME));
    }

    public function testItVerifyEventIsNotForService(): void
    {
        $this->assertFalse($this->proxy->isForServiceShortName('other_service'));
    }

    public function testItPreventsServiceUsage(): void
    {
        $this->proxy->disableWithMessage('A message');
        self::assertSame('A message', $this->event->getReason());
    }
}
