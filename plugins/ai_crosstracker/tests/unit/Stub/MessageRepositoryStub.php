<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AICrossTracker\Stub;

use Override;
use Tuleap\AI\Mistral\Message;
use Tuleap\AI\Mistral\TokenUsage;
use Tuleap\AICrossTracker\Assistant\MessageRepository;
use Tuleap\AICrossTracker\Assistant\Thread;
use Tuleap\AICrossTracker\Assistant\ThreadID;

final class MessageRepositoryStub implements MessageRepository
{
    private array $threads;
    private(set) TokenUsage $token_usage;

    public function __construct(Thread ...$threads)
    {
        foreach ($threads as $thread) {
            $this->threads[$thread->id->uuid->toString()] = $thread->messages;
        }
    }

    #[Override]
    public function fetch(ThreadID $id): array
    {
        if (! isset($this->threads[$id->uuid->toString()])) {
            return [];
        }
        return $this->threads[$id->uuid->toString()];
    }

    #[Override]
    public function store(ThreadID $id, Message $message): void
    {
        $this->threads[$id->uuid->toString()][] = $message;
    }

    #[Override]
    public function storeWithTokenConsumption(ThreadID $id, Message $message, TokenUsage $token_usage): void
    {
        $this->store($id, $message);
        $this->token_usage = $token_usage;
    }
}
