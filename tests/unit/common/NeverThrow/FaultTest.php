<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\NeverThrow;

use Psr\Log\LogLevel;
use ColinODell\PsrTestLogger\TestLogger;

final class FaultTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ERROR_MESSAGE     = 'User is not allowed to do that';
    private const EXCEPTION_MESSAGE = 'An exception was thrown';

    public function testItBuildsANewFaultWithAMessage(): void
    {
        $error = Fault::fromMessage(self::ERROR_MESSAGE);
        self::assertSame(self::ERROR_MESSAGE, (string) $error, 'It can be cast to string');
        self::assertStringContainsString(
            __FUNCTION__,
            $error->getStackTraceAsString(),
            'It records the stack trace where it is created'
        );
    }

    public function testItBuildsAFaultFromAThrowable(): void
    {
        $error = Fault::fromThrowable($this->getThrowable());
        self::assertSame(self::EXCEPTION_MESSAGE, (string) $error, 'It can be cast to string');
        self::assertStringContainsString(
            'getThrowable',
            $error->getStackTraceAsString(),
            'It preserves the stack trace of the throwable'
        );
    }

    public function testItBuildsAFaultFromAThrowableAndMessage(): void
    {
        $error = Fault::fromThrowableWithMessage($this->getThrowable(), self::ERROR_MESSAGE);
        self::assertSame(self::ERROR_MESSAGE, (string) $error);
        self::assertStringContainsString(
            'getThrowable',
            $error->getStackTraceAsString(),
            'It preserves the stack trace of the throwable'
        );
    }

    private function getThrowable(): \Throwable
    {
        return new \Exception(self::EXCEPTION_MESSAGE);
    }

    public function testItCanBeExtended(): void
    {
        $error_code        = 123;
        $specialized_fault = new /** @psalm-immutable */ class (self::ERROR_MESSAGE, $error_code) extends Fault {
            public function __construct(string $error_message, private int $code)
            {
                parent::__construct($error_message);
            }

            public function getCode(): int
            {
                return $this->code;
            }
        };
        self::assertSame($error_code, $specialized_fault->getCode());
        self::assertSame(self::ERROR_MESSAGE, (string) $specialized_fault, 'It can be cast to string');
        self::assertStringContainsString(
            __FUNCTION__,
            $specialized_fault->getStackTraceAsString(),
            'It records the stack trace where it is created'
        );
    }

    public function testCanWritesToLogger(): void
    {
        $logger = new TestLogger();
        $fault  = Fault::fromMessage('Message');

        Fault::writeToLogger($fault, $logger);

        self::assertTrue($logger->hasErrorRecords());
    }

    public function testCanWritesToLoggerAtASpecificLogLevel(): void
    {
        $logger = new TestLogger();
        $fault  = Fault::fromMessage('Message');

        Fault::writeToLogger($fault, $logger, LogLevel::INFO);

        self::assertTrue($logger->hasInfoRecords());
    }

    public function testCanWritesToLoggerEvenWhenThrowableIsNotAnException(): void
    {
        $logger = new TestLogger();
        $fault  = Fault::fromThrowable(new \Error());

        Fault::writeToLogger($fault, $logger);

        self::assertTrue($logger->hasErrorRecords());
    }
}
