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
use Tuleap\AI\Mistral\MistralConnector;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerWidget;
use Tuleap\CrossTracker\Widget\UserCrossTrackerWidget;
use Tuleap\DB\DatabaseUUIDFactory;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\RetrieveMultipleTrackers;
use Tuleap\User\CurrentUserWithLoggedInInformation;

final readonly class ChatThreadManager
{
    public function __construct(
        private DatabaseUUIDFactory $uuid_factory,
        private MessageRepository $message_repository,
        private ThreadStorage $thread_storage,
        private ProjectByIDFactory $project_factory,
        private RetrieveMultipleTrackers $tracker_factory,
        private RetrieveUsedFields $fields_factory,
        private MistralConnector $mistral_connector,
    ) {
    }

    public function handleConversation(CurrentUserWithLoggedInInformation $current_user_with_logged_in_information, ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget, Message $mistral_message, ?string $thread_id = null): Ok|Err
    {
        $thread_repository = new ThreadRepository(
            $this->message_repository,
            $this->thread_storage,
            $this->uuid_factory,
        );

        return $thread_repository->fetchThread($widget, $current_user_with_logged_in_information->user, $thread_id, $mistral_message)
            ->match(
                function (Thread $thread) use ($widget, $current_user_with_logged_in_information): Ok|Err {
                    $assistant = match ($widget::class) {
                        ProjectCrossTrackerWidget::class => new ProjectAssistant(
                            $this->project_factory,
                            $this->tracker_factory,
                            $this->fields_factory,
                            $widget
                        ),
                        UserCrossTrackerWidget::class => new UserAssistant(),
                    };

                    return new CompletionSender($this->mistral_connector, $this->message_repository)
                        ->sendMessages($current_user_with_logged_in_information, $assistant, $thread);
                },
                static fn() => Result::err(Fault::fromMessage('Invalid UUID')),
            );
    }
}
