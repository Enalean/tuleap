<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Queue;

use Tuleap\Config\InvalidConfigKeyValueException;
use Tuleap\Plugin\MandatoryAsyncWorkerSetupPluginInstallRequirement;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\Plugin\RetrieveEnabledPluginsStub;

final class NbBackendWorkersConfigValidatorTest extends TestCase
{
    public function testLesserThanZero(): void
    {
        $validator = NbBackendWorkersConfigValidator::buildWithPluginRetriever(
            RetrieveEnabledPluginsStub::buildWithoutPlugins(),
        );

        $this->expectException(InvalidConfigKeyValueException::class);

        $validator->checkIsValid("-1");
    }

    public function testEqualZeroWhenPluginsNeedWorkers(): void
    {
        $cce = $this->createMock(\Plugin::class);
        $cce->method('getName')->willReturn('CCE');
        $cce->method('getInstallRequirements')->willReturn(
            [
                new MandatoryAsyncWorkerSetupPluginInstallRequirement(new WorkerAvailability()),
            ]
        );

        $doc = $this->createMock(\Plugin::class);
        $doc->method('getName')->willReturn('Doc');
        $doc->method('getInstallRequirements')->willReturn([]);

        $wiki = $this->createMock(\Plugin::class);
        $wiki->method('getName')->willReturn('Wiki');
        $wiki->method('getInstallRequirements')->willReturn(
            [
                new MandatoryAsyncWorkerSetupPluginInstallRequirement(new WorkerAvailability()),
            ]
        );

        $validator = NbBackendWorkersConfigValidator::buildWithPluginRetriever(
            RetrieveEnabledPluginsStub::buildWithPlugins($cce, $doc, $wiki),
        );

        $this->expectException(InvalidConfigKeyValueException::class);
        $this->expectErrorMessage("Nb backend workers cannot be 0, the following plugins need workers: CCE, Wiki");

        $validator->checkIsValid("0");
    }

    public function testEqualZeroWhenPluginsDoNotNeedWorkers(): void
    {
        $cce = $this->createMock(\Plugin::class);
        $cce->method('getName')->willReturn('CCE');
        $cce->method('getInstallRequirements')->willReturn([]);
        $doc = $this->createMock(\Plugin::class);
        $doc->method('getName')->willReturn('Doc');
        $doc->method('getInstallRequirements')->willReturn([]);
        $wiki = $this->createMock(\Plugin::class);
        $wiki->method('getName')->willReturn('Wiki');
        $wiki->method('getInstallRequirements')->willReturn([]);

        $validator = NbBackendWorkersConfigValidator::buildWithPluginRetriever(
            RetrieveEnabledPluginsStub::buildWithPlugins($cce, $doc, $wiki),
        );

        $this->expectNotToPerformAssertions();

        $validator->checkIsValid("0");
    }

    public function testGreaterThanZeroWhenPluginsNeedWorkers(): void
    {
        $cce = $this->createMock(\Plugin::class);
        $cce->method('getName')->willReturn('CCE');
        $cce->method('getInstallRequirements')->willReturn(
            [
                new MandatoryAsyncWorkerSetupPluginInstallRequirement(new WorkerAvailability()),
            ]
        );

        $doc = $this->createMock(\Plugin::class);
        $doc->method('getName')->willReturn('Doc');
        $doc->method('getInstallRequirements')->willReturn([]);

        $wiki = $this->createMock(\Plugin::class);
        $wiki->method('getName')->willReturn('Wiki');
        $wiki->method('getInstallRequirements')->willReturn(
            [
                new MandatoryAsyncWorkerSetupPluginInstallRequirement(new WorkerAvailability()),
            ]
        );

        $validator = NbBackendWorkersConfigValidator::buildWithPluginRetriever(
            RetrieveEnabledPluginsStub::buildWithPlugins($cce, $doc, $wiki),
        );

        $this->expectNotToPerformAssertions();

        $validator->checkIsValid("1");
    }

    public function testGreaterThanZeroWhenPluginsDoNotNeedWorkers(): void
    {
        $cce = $this->createMock(\Plugin::class);
        $cce->method('getName')->willReturn('CCE');
        $cce->method('getInstallRequirements')->willReturn([]);

        $doc = $this->createMock(\Plugin::class);
        $doc->method('getName')->willReturn('Doc');
        $doc->method('getInstallRequirements')->willReturn([]);

        $wiki = $this->createMock(\Plugin::class);
        $wiki->method('getName')->willReturn('Wiki');
        $wiki->method('getInstallRequirements')->willReturn([]);

        $validator = NbBackendWorkersConfigValidator::buildWithPluginRetriever(
            RetrieveEnabledPluginsStub::buildWithPlugins($cce, $doc, $wiki),
        );

        $this->expectNotToPerformAssertions();

        $validator->checkIsValid("1");
    }
}
