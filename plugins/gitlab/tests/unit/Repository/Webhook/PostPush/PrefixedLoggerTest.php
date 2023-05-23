<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use Psr\Log\LogLevel;
use ColinODell\PsrTestLogger\TestLogger;

final class PrefixedLoggerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PREFIX = '| |';
    private TestLogger $sub_logger;

    protected function setUp(): void
    {
        $this->sub_logger = new TestLogger();
    }

    private function log(string $message): void
    {
        $logger = new PrefixedLogger($this->sub_logger, self::PREFIX);
        $logger->log(LogLevel::DEBUG, $message);
    }

    public function testItAddsPrefixToLogMessage(): void
    {
        $message = 'Ooops, an error occurred';
        $this->log($message);
        self::assertTrue($this->sub_logger->hasDebug(self::PREFIX . $message));
    }
}
