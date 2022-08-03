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

use Tuleap\Git\Hook\CommitHash;

/**
 * @psalm-immutable
 */
final class AnalyzeCommitTask implements \Tuleap\Queue\QueueTask
{
    public const TOPIC = 'tuleap.git.hooks.post-receive';

    private function __construct(
        private CommitHash $commit_hash,
        private int $git_repository_id,
        private int $pushing_user_id,
    ) {
    }

    public static function fromCommit(CommitAnalysisOrder $order): self
    {
        return new self($order->getCommitHash(), (int) $order->getRepository()->getId(), (int) $order->getPusher()->getId());
    }

    public function getTopic(): string
    {
        return self::TOPIC;
    }

    public function getPayload(): array
    {
        return [
            'commit_sha1'       => (string) $this->commit_hash,
            'git_repository_id' => $this->git_repository_id,
            'pushing_user_id'   => $this->pushing_user_id,
        ];
    }

    public function getPreEnqueueMessage(): string
    {
        return 'Analyze commit pushed in git repository #' . $this->git_repository_id . ' by user #' . $this->pushing_user_id . ' with hash #' . $this->commit_hash;
    }
}
