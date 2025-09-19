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

namespace Tuleap\Git\Hook\Asynchronous;

use Tuleap\Git\Hook\DefaultBranchPush\CommitHash;
use Tuleap\Git\Hook\DefaultBranchPush\DefaultBranchPushReceived;

/**
 * @psalm-immutable
 */
final class AnalyzePushTask implements \Tuleap\Queue\QueueTask
{
    public const string TOPIC = 'tuleap.git.hooks.post-receive';

    private function __construct(
        /**
         * @var list<string>
         */
        private array $commit_hashes,
        private int $git_repository_id,
        private int $pushing_user_id,
    ) {
    }

    public static function fromDefaultBranchPush(DefaultBranchPushReceived $push): self
    {
        $commit_hashes = array_map(
            static fn(CommitHash $commit_hash) => (string) $commit_hash,
            $push->getCommitHashes()
        );
        return new self($commit_hashes, (int) $push->getRepository()->getId(), (int) $push->getPusher()->getId());
    }

    #[\Override]
    public function getTopic(): string
    {
        return self::TOPIC;
    }

    #[\Override]
    public function getPayload(): array
    {
        return [
            'commit_hashes'     => $this->commit_hashes,
            'git_repository_id' => $this->git_repository_id,
            'pushing_user_id'   => $this->pushing_user_id,
        ];
    }

    #[\Override]
    public function getPreEnqueueMessage(): string
    {
        return 'Analyze push in git repository #' . $this->git_repository_id . ' by user #' . $this->pushing_user_id;
    }
}
