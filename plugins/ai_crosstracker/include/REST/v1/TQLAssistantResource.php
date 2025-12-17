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

namespace Tuleap\AICrossTracker\REST\v1;

use Luracast\Restler\RestException;
use ProjectManager;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\AI\Mistral\Message;
use Tuleap\AI\Mistral\MistralConnectorLive;
use Tuleap\AICrossTracker\Assistant\CompletionSender;
use Tuleap\AICrossTracker\Assistant\MessageRepositoryDao;
use Tuleap\AICrossTracker\Assistant\ProjectAssistant;
use Tuleap\AICrossTracker\Assistant\Thread;
use Tuleap\AICrossTracker\Assistant\ThreadID;
use Tuleap\AICrossTracker\Assistant\ThreadRepository;
use Tuleap\AICrossTracker\Assistant\ThreadStorageDao;
use Tuleap\AICrossTracker\Assistant\UserAssistant;
use Tuleap\CrossTracker\REST\v1\CrossTrackerWidgetNotFoundException;
use Tuleap\CrossTracker\REST\v1\UserIsAllowedToSeeWidgetChecker;
use Tuleap\CrossTracker\Widget\CrossTrackerWidgetDao;
use Tuleap\CrossTracker\Widget\CrossTrackerWidgetRetriever;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerWidget;
use Tuleap\CrossTracker\Widget\UserCrossTrackerWidget;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\DB\UUID;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Mapper\ValinorMapperBuilderFactory;
use Tuleap\NeverThrow\Fault;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use URLVerification;

final class TQLAssistantResource extends AuthenticatedResource
{
    public const string ROUTE = 'crosstracker_assistant';

    /**
     * @url {id}/helper
     */
    public function optionsHelper(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * (EXPERIMENTAL) Get help on TQL
     *
     * @url    POST {id}/helper
     * @access protected
     *
     * @param int $id Widget Id {@from body}
     * @param string $message {@from body}{@min 1}{@max 500}
     * @param ?string $thread_id {@from body}
     *
     * @status 200
     * @throws RestException
     */
    protected function post(int $id, string $message, ?string $thread_id = null): HelperRepresentation
    {
        $this->checkAccess();

        if (! $this->getWidgetDao()->searchWidgetExistence($id)) {
            throw new CrossTrackerWidgetNotFoundException();
        }

        $current_user_with_logged_in_information = \UserManager::instance()->getCurrentUserWithLoggedInInformation();
        try {
            $this->getUserIsAllowedToSeeWidgetChecker()->checkUserIsAllowedToSeeWidget($current_user_with_logged_in_information->user, $id);

            $mistral_message = Message::buildUserMessageFromString($message);

            $uuid_factory       = new DatabaseUUIDV7Factory();
            $message_repository = new MessageRepositoryDao();
            $thread_repository  = new ThreadRepository(
                $message_repository,
                new ThreadStorageDao()
            );
            if ($thread_id === null) {
                $thread = $thread_repository->fetchNewThread($id, $current_user_with_logged_in_information->user, $mistral_message);
            } else {
                $thread = $uuid_factory->buildUUIDFromHexadecimalString($thread_id)
                    ->match(
                        static fn (UUID $uuid): Thread => $thread_repository->fetchExistingThread($id, $current_user_with_logged_in_information->user, new ThreadID($uuid), $mistral_message)->match(
                            static fn (Thread $thread): Thread => $thread,
                            static fn () => throw new RestException(400, 'Invalid thread id'),
                        ),
                        static fn () => throw new RestException(400, 'Invalid UUID')
                    );
            }

            $cross_tracker_retriever = new CrossTrackerWidgetRetriever($this->getWidgetDao());
            return $cross_tracker_retriever->retrieveWidgetById($id)->match(
                function (ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget) use ($current_user_with_logged_in_information, $thread, $message_repository): HelperRepresentation {
                    $assistant = match ($widget::class) {
                        ProjectCrossTrackerWidget::class => new ProjectAssistant(
                            ProjectManager::instance(),
                            TrackerFactory::instance(),
                            Tracker_FormElementFactory::instance(),
                            $widget
                        ),
                        UserCrossTrackerWidget::class => new UserAssistant(),
                    };

                    $mistral_connector = new MistralConnectorLive(
                        HttpClientFactory::createClientWithCustomTimeout(60),
                        HTTPFactoryBuilder::requestFactory(),
                        HTTPFactoryBuilder::streamFactory(),
                        ValinorMapperBuilderFactory::mapperBuilder(),
                        Prometheus::instance(),
                    );

                    return new CompletionSender($mistral_connector, $message_repository)
                        ->sendMessages($current_user_with_logged_in_information, $assistant, $thread)
                        ->match(
                            static fn (HelperRepresentation $helper_representation): HelperRepresentation => $helper_representation,
                            static fn (Fault $fault) => throw new RestException(400, (string) $fault)
                        );
                },
                static fn() => throw new RestException(400, 'Unknown widget type'),
            );
        } catch (\Project_NotFoundException) {
            throw new RestException(404, 'Project not found');
        } catch (CrossTrackerWidgetNotFoundException) {
            throw new I18NRestException(404, sprintf(dgettext('tuleap-crosstracker', 'Widget with id %d not found'), $id));
        }
    }

    private function getWidgetDao(): CrossTrackerWidgetDao
    {
        return new CrossTrackerWidgetDao();
    }

    private function getUserIsAllowedToSeeWidgetChecker(): UserIsAllowedToSeeWidgetChecker
    {
        return new UserIsAllowedToSeeWidgetChecker(
            ProjectManager::instance(),
            new URLVerification(),
            new CrossTrackerWidgetRetriever($this->getWidgetDao()),
        );
    }
}
