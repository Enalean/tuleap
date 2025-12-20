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

use Tuleap\AI\Mistral\Message;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerWidget;
use Tuleap\CrossTracker\Widget\UserCrossTrackerWidget;
use Tuleap\DB\DatabaseUUIDFactory;
use Tuleap\DB\UUID;
use Tuleap\Option\Option;

final readonly class ThreadRepository
{
    public function __construct(private MessageRepository $message_repository, private ThreadStorage $thread_storage, private DatabaseUUIDFactory $uuid_factory)
    {
    }

    /**
     * @psalm-return Option<Thread>
     */
    public function fetchThread(ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget, \PFUser $user, ?string $thread_id, Message $submitted_message): Option
    {
        if ($thread_id !== null) {
            return $this->fetchExistingThread($widget, $user, $thread_id, $submitted_message);
        } else {
            return $this->fetchNewThread($widget, $user, $submitted_message);
        }
    }

    /**
     * @psalm-return Option<Thread>
     */
    private function fetchNewThread(ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget, \PFUser $user, Message $submitted_message): Option
    {
        $id = $this->thread_storage->createNew($user, $widget);

        $this->message_repository->store($id, $submitted_message);

        return Option::fromValue(
            new Thread(
                $id,
                $submitted_message,
            )
        );
    }

    /**
     * @psalm-return Option<Thread>
     */
    private function fetchExistingThread(ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget, \PFUser $user, string $thread_id, Message $submitted_message): Option
    {
        return $this->uuid_factory->buildUUIDFromHexadecimalString($thread_id)->andThen(
            fn (UUID $uuid) => $this->thread_storage->threadExists($user, $widget, new ThreadID($uuid))->andThen(
                function (ThreadID $thread_id) use ($submitted_message) {
                    $user_messages = array_merge(
                        $this->message_repository->fetch($thread_id),
                        [$submitted_message],
                    );

                    $this->message_repository->store($thread_id, $submitted_message);

                    return Option::fromValue(
                        new Thread(
                            $thread_id,
                            ...$user_messages,
                        )
                    );
                }
            )
        );
    }
}
