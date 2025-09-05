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

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * I hold a technical or business error that is not fatal and does not require the program to stop immediately.
 * The error must be recoverable, otherwise use Exceptions.
 * For example: A User cannot see a Project.
 * @psalm-immutable
 */
readonly class Fault implements \Stringable
{
    /** Internal Exception used only to record stack traces. It is never thrown */
    private \Throwable $exception;

    /** @param string $message Error message to be logged or showed on-screen. */
    protected function __construct(
        private string $message,
        ?\Throwable $exception = null,
    ) {
        $this->exception = $exception ?? new \Exception();
    }

    /**
     * fromMessage returns a new Fault with the supplied message. It also records the stack trace at the point it was called.
     * @param string $message A message to explain what happened. This message could appear in log files or on-screen.
     */
    public static function fromMessage(string $message): self
    {
        return new self($message, new \Exception());
    }

    /**
     * fromThrowable wraps an existing Throwable and returns a new Fault with its message and stack trace.
     * It preserves both the message and the stack trace from $throwable.
     * @param \Throwable $throwable Wrapped throwable
     */
    public static function fromThrowable(\Throwable $throwable): self
    {
        return new self($throwable->getMessage(), $throwable);
    }

    /**
     * fromThrowableWithMessage wraps an existing Throwable and returns a new Fault with the supplied message.
     * It discards the message from $throwable. It preserves the stack trace from $throwable.
     * @param \Throwable $throwable Wrapped throwable
     * @param string     $message   A message to explain what happened. This message could appear in log files or on-screen.
     */
    public static function fromThrowableWithMessage(\Throwable $throwable, string $message): self
    {
        return new self($message, $throwable);
    }

    public function getStackTraceAsString(): string
    {
        return $this->exception->getTraceAsString();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->message;
    }

    /**
     * @psalm-param LogLevel::* $level
     */
    public static function writeToLogger(self $fault, LoggerInterface $logger, string $level = LogLevel::ERROR): void
    {
        if ($fault->exception instanceof \Exception) {
            $logger->log($level, $fault->message, ['exception' => $fault->exception]);
            return;
        }
        $logger->log($level, $fault->message);
    }
}
