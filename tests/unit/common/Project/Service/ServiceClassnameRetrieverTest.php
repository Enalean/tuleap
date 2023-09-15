<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Service;

use Service;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

final class ServiceClassnameRetrieverTest extends TestCase
{
    private const PLUGIN_SERVICE_SHORTNAME = 'plugin_service';

    public function testReturnsProjectDefinedServiceWhenShortNameIsEmpty(): void
    {
        $retriever = new ServiceClassnameRetriever(EventDispatcherStub::withIdentityCallback());

        self::assertSame(
            ProjectDefinedService::class,
            $retriever->getServiceClassName(''),
        );
    }

    /**
     * @dataProvider getServicesWithoutSpecificImplementation
     */
    public function testReturnsService(string $name): void
    {
        $retriever = new ServiceClassnameRetriever(EventDispatcherStub::withIdentityCallback());

        self::assertSame(
            Service::class,
            $retriever->getServiceClassName($name),
        );
    }

    /**
     * @return list<string[]>
     */
    private function getServicesWithoutSpecificImplementation(): array
    {
        return [
            [Service::SUMMARY],
            [Service::ADMIN],
            [Service::FORUM],
            [Service::HOMEPAGE],
            [Service::NEWS],
            [Service::WIKI],
            [Service::TRACKERV3],
        ];
    }

    public function testReturnsServiceFile(): void
    {
        $retriever = new ServiceClassnameRetriever(EventDispatcherStub::withIdentityCallback());

        self::assertSame(
            \ServiceFile::class,
            $retriever->getServiceClassName(Service::FILE),
        );
    }

    public function testReturnsServiceSVN(): void
    {
        $retriever = new ServiceClassnameRetriever(EventDispatcherStub::withIdentityCallback());

        self::assertSame(
            \ServiceSVN::class,
            $retriever->getServiceClassName(Service::SVN),
        );
    }

    public function testReturnsServiceDefinedByPlugin(): void
    {
        $plugin_service           = new class (ProjectTestBuilder::aProject()->build(), []) extends Service {
        };
        $plugin_service_classname = $plugin_service::class;

        $retriever = new ServiceClassnameRetriever(EventDispatcherStub::withCallback(function (ServiceClassnamesCollector $event) use ($plugin_service_classname): object {
            $event->addService(self::PLUGIN_SERVICE_SHORTNAME, $plugin_service_classname);

            return $event;
        }));

        self::assertSame(
            $plugin_service_classname,
            $retriever->getServiceClassName(self::PLUGIN_SERVICE_SHORTNAME),
        );
    }

    public function testItCachesRetrievalOfPluginClassnamesToSaveRainforestWhenMultipleCalls(): void
    {
        $nb_calls = 0;

        $retriever = new ServiceClassnameRetriever(EventDispatcherStub::withCallback(function (ServiceClassnamesCollector $event) use (&$nb_calls): object {
            $nb_calls++;

            return $event;
        }));

        $retriever->getServiceClassName(Service::SVN);
        $retriever->getServiceClassName(Service::FILE);
        $retriever->getServiceClassName(Service::SVN);

        self::assertSame(1, $nb_calls);
    }
}
