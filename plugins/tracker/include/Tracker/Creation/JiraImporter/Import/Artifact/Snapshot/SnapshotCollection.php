<?php
/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot;

use DateInterval;
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\Comment;

final class SnapshotCollection
{
    private Snapshot $initial_snapshot;
    private array $changelog = [];

    public function __construct(private LoggerInterface $logger)
    {
    }

    public function setInitialSnapshot(Snapshot $snapshot): void
    {
        $this->initial_snapshot = $snapshot;
        $this->appendChangelogSnapshot($snapshot);
    }

    public function appendChangelogSnapshot(Snapshot $snapshot): void
    {
        if (isset($this->changelog[$snapshot->getDate()->getTimestamp()])) {
            $this->appendChangelogSnapshot(new Snapshot(
                $snapshot->getUser(),
                $snapshot->getDate()->add(DateInterval::createFromDateString('1 second')),
                $snapshot->getAllFieldsSnapshot(),
                $snapshot->getCommentSnapshot(),
            ));
        } else {
            if ($snapshot->getDate()->getTimestamp() < $this->initial_snapshot->getDate()->getTimestamp()) {
                $this->logger->warning(sprintf('Snapshot from changelog is older (%s) than initial changeset (%s)', $snapshot->getDate()->format('c'), $this->initial_snapshot->getDate()->format('c')));
            }
            $this->changelog[$snapshot->getDate()->getTimestamp()] = $snapshot;
        }
    }

    public function addComment(\PFUser $user, Comment $comment): void
    {
        if ($comment->getRenderedValue() === '') {
            return;
        }

        if (isset($this->changelog[$comment->getDate()->getTimestamp()])) {
            $this->updateExistingSnapshotWithComment($comment);
            return;
        }

        $this->appendChangelogSnapshot(
            new Snapshot(
                $user,
                $comment->getDate(),
                [],
                $comment
            )
        );
    }

    private function updateExistingSnapshotWithComment(Comment $comment): void
    {
        $this->changelog[$comment->getDate()->getTimestamp()] = new Snapshot(
            $this->changelog[$comment->getDate()->getTimestamp()]->getUser(),
            $this->changelog[$comment->getDate()->getTimestamp()]->getDate(),
            $this->changelog[$comment->getDate()->getTimestamp()]->getAllFieldsSnapshot(),
            $comment
        );
    }

    /**
     * @psalm-return list<Snapshot>
     */
    public function toArray(): array
    {
        ksort($this->changelog);
        return array_values($this->changelog);
    }
}
