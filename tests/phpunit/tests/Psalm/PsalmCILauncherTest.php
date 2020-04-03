<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Test\Psalm;

use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class PsalmCILauncherTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private const FILE_IN_SCOPE = 'src/common/Config/ForgeConfig.php';
    private const FILE_OUT_OF_SCOPE = 'simpletest/common/Config/ConfigTest.php';
    private const FILE_NOT_EXISTING = 'DoNotExist.php';

    /**
     * @var bool
     */
    private $previous_status_xml_entity_loading;

    protected function setUp(): void
    {
        // Psalm XML configuration will be verified against a XSD file external entities are needed
        $this->previous_status_xml_entity_loading = libxml_disable_entity_loader(false);
    }

    protected function tearDown(): void
    {
        libxml_disable_entity_loader($this->previous_status_xml_entity_loading);
    }

    public function testPsalmIsLaunchedOnlyWithFilesInScopeOfTheConfig(): void
    {
        $shell_passthrough = Mockery::mock(ShellPassthrough::class);
        $command           = new PsalmCILauncher($shell_passthrough);
        $command_tester    = new CommandTester($command);

        $shell_passthrough->shouldReceive('__invoke')->withArgs(
            function (string $command): bool {
                $this->assertStringContainsString(self::FILE_IN_SCOPE, $command);
                $this->assertStringNotContainsString(self::FILE_OUT_OF_SCOPE, $command);
                $this->assertStringNotContainsString(self::FILE_NOT_EXISTING, $command);
                return true;
            }
        )->andReturn(0)->once();

        $command_tester->execute(['modified-files' => [
            self::FILE_IN_SCOPE,
            self::FILE_OUT_OF_SCOPE,
            self::FILE_NOT_EXISTING,
        ]]);
        $this->assertSame(0, $command_tester->getStatusCode());
    }

    public function testPsalmIsNotLaunchedIfNoFileNeedsToBeAnalyzed(): void
    {
        $shell_passthrough = Mockery::mock(ShellPassthrough::class);
        $command           = new PsalmCILauncher($shell_passthrough);
        $command_tester    = new CommandTester($command);

        $shell_passthrough->shouldNotReceive('__invoke');

        $command_tester->execute(['modified-files' => [realpath(self::FILE_OUT_OF_SCOPE)]]);
    }
}
