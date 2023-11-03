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

namespace Tuleap\Git\Tests\Stub;

final class RetrieveCommitMessageStub implements \Tuleap\Git\CommitMetadata\RetrieveCommitMessage
{
    /**
     * @param list<string> $return_values
     */
    private function __construct(
        private bool $should_throw,
        private array $return_values,
    ) {
    }

    /**
     * @no-named-arguments
     */
    public static function withSuccessiveMessages(string $message, string ...$other_messages): self
    {
        return new self(false, [$message, ...$other_messages]);
    }

    public static function withError(): self
    {
        return new self(true, ['']);
    }

    public function getCommitMessage(string $ref): string
    {
        if ($this->should_throw) {
            throw new \Git_Command_Exception('log', [], 1);
        }
        if (count($this->return_values) > 0) {
            return array_shift($this->return_values);
        }
        throw new \LogicException('No commit message configured');
    }
}
