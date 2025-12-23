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
use Tuleap\AICrossTracker\Assistant\ChatThreadManager;
use Tuleap\AICrossTracker\Assistant\ThreadRepository;
use Tuleap\AICrossTracker\Assistant\ThreadStorageDao;
use Tuleap\CrossTracker\REST\v1\UserIsAllowedToSeeWidgetChecker;
use Tuleap\CrossTracker\Widget\CrossTrackerWidgetDao;
use Tuleap\CrossTracker\Widget\CrossTrackerWidgetRetriever;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerWidget;
use Tuleap\CrossTracker\Widget\UserCrossTrackerWidget;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Mapper\ValinorMapperBuilderFactory;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\NeverThrow\Fault;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
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
    protected function post(int $id, string $message, ?string $thread_id = null): HelperRepresentationWithInterpretedExplanations
    {
        $this->checkAccess();

        $mistral_message                         = Message::buildUserMessageFromString($message);
        $current_user_with_logged_in_information = \UserManager::instance()->getCurrentUserWithLoggedInInformation();
        return $this->getUserIsAllowedToSeeWidgetChecker()
            ->getWidgetUserCanSee($current_user_with_logged_in_information->user, $id)
            ->match(
                function (ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget) use ($current_user_with_logged_in_information, $mistral_message, $thread_id) {
                    $message_repository = new MessageRepositoryDao();
                    return new ChatThreadManager(
                        new ThreadRepository(
                            $message_repository,
                            new ThreadStorageDao(),
                            new DatabaseUUIDV7Factory(),
                        ),
                        ProjectManager::instance(),
                        TrackerFactory::instance(),
                        Tracker_FormElementFactory::instance(),
                        new CompletionSender(
                            new MistralConnectorLive(
                                HttpClientFactory::createClientWithCustomTimeout(60),
                                HTTPFactoryBuilder::requestFactory(),
                                HTTPFactoryBuilder::streamFactory(),
                                ValinorMapperBuilderFactory::mapperBuilder(),
                                Prometheus::instance(),
                            ),
                            $message_repository,
                            CommonMarkInterpreter::build(\Codendi_HTMLPurifier::instance()),
                        ),
                    )->handleConversation($current_user_with_logged_in_information, $widget, $mistral_message, $thread_id)->match(
                        static fn (HelperRepresentationWithInterpretedExplanations $helper_representation): HelperRepresentationWithInterpretedExplanations => $helper_representation,
                        static fn (Fault $fault) => throw new RestException(400, (string) $fault),
                    );
                },
                static fn () => throw new RestException(400, 'Invalid widget ID'),
            );
    }

    private function getUserIsAllowedToSeeWidgetChecker(): UserIsAllowedToSeeWidgetChecker
    {
        return new UserIsAllowedToSeeWidgetChecker(
            ProjectManager::instance(),
            new URLVerification(),
            new CrossTrackerWidgetRetriever(new CrossTrackerWidgetDao()),
        );
    }
}
