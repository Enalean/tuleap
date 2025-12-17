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

namespace Tuleap\AICrossTracker\Assistant;

use Override;
use Tuleap\AI\Mistral\Message;
use Tuleap\AI\Mistral\Role;
use Tuleap\AI\Mistral\StringContent;
use Tuleap\AI\Mistral\TokenUsage;
use Tuleap\DB\DataAccessObject;

final class MessageRepositoryDao extends DataAccessObject implements MessageRepository
{
    #[Override]
    public function fetch(ThreadID $id): array
    {
        $messages = [];
        $rows     = $this->getDB()->run(
            <<<EOS
            SELECT role, content
            FROM ai_crosstracker_completion_message
            WHERE thread_id = ?
            ORDER BY id
            EOS,
            $id->uuid->getBytes()
        );
        foreach ($rows as $row) {
            $messages[] = new Message(Role::from($row['role']), new StringContent($row['content']));
        }
        return $messages;
    }

    #[Override]
    public function store(ThreadID $id, Message $message): void
    {
        $message_id = $this->uuid_factory->buildUUIDBytes();
        $this->getDB()->insert(
            'ai_crosstracker_completion_message',
            [
                'id'  => $message_id,
                'thread_id' => $id->uuid->getBytes(),
                'role' => $message->role->value,
                'date' => new \DateTimeImmutable()->getTimestamp(),
                'content' => (string) $message->content,
            ]
        );
    }

    #[Override]
    public function storeWithTokenConsumption(ThreadID $id, Message $message, TokenUsage $token_usage): void
    {
        $message_id = $this->uuid_factory->buildUUIDBytes();
        $this->getDB()->insert(
            'ai_crosstracker_completion_message',
            [
                'id'  => $message_id,
                'thread_id' => $id->uuid->getBytes(),
                'role' => $message->role->value,
                'date' => new \DateTimeImmutable()->getTimestamp(),
                'content' => (string) $message->content,
                'tokens_prompt' => $token_usage->prompt_tokens,
                'tokens_completion' => $token_usage->completion_tokens,
                'tokens_total' => $token_usage->total_tokens,
            ]
        );
    }
}
