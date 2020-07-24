<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_XML_Importer_CopyArtifactInformationsAggregatorTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var Tracker_XML_Importer_CopyArtifactInformationsAggregator */
    private $logger;

    /** @var \Psr\Log\LoggerInterface */
    private $backend_logger;

    protected function setUp(): void
    {
        $this->backend_logger = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $this->logger         = new Tracker_XML_Importer_CopyArtifactInformationsAggregator($this->backend_logger);
    }

    public function testItDoesNotContainsAnyMessageIfThereAreNone(): void
    {
        $this->assertEquals([], $this->logger->getAllLogs());
    }

    public function testItContainsAllTheLoggedMessages(): void
    {
        $this->logger->error("this is an error");
        $this->logger->warning("this is a warning");

        $expected_logs = [
            "[error] this is an error",
            "[warning] this is a warning"
        ];
        $this->assertEquals($expected_logs, $this->logger->getAllLogs());
    }

    public function testItAlsoLogsUsingTheBackendLogger(): void
    {
        $this->backend_logger->shouldReceive('log')->once();

        $this->logger->error("this is an error");
    }

    public function testItOnlyLogsErrorsAndWarningsInTheLogStack(): void
    {
        $this->backend_logger->shouldReceive('log')->times(4);

        $this->logger->error("this is an error");
        $this->logger->warning("this is a warning");
        $this->logger->info("this is an info");
        $this->logger->debug("this is a debug");

        $expected_logs = [
            "[error] this is an error",
            "[warning] this is a warning"
        ];
        $this->assertEquals($expected_logs, $this->logger->getAllLogs());
    }
}
