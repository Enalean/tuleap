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
use Tuleap\AI\Mistral\CompletionResponse;
use Tuleap\AI\Mistral\Message;
use Tuleap\AI\Mistral\MistralConnectorLive;
use Tuleap\AI\Requestor\AIRequestorEntity;
use Tuleap\AI\Requestor\EndUserAIRequestor;
use Tuleap\AICrossTracker\Assistant\ProjectAssistant;
use Tuleap\AICrossTracker\Assistant\UserAssistant;
use Tuleap\CrossTracker\REST\v1\CrossTrackerWidgetNotFoundException;
use Tuleap\CrossTracker\REST\v1\UserIsAllowedToSeeWidgetChecker;
use Tuleap\CrossTracker\Widget\CrossTrackerWidgetDao;
use Tuleap\CrossTracker\Widget\CrossTrackerWidgetRetriever;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerWidget;
use Tuleap\CrossTracker\Widget\UserCrossTrackerWidget;
use Tuleap\Http\HttpClientFactory;
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
     * @param array $messages {@from body} {@type \Tuleap\AICrossTracker\REST\v1\MessageRepresentation}
     *
     * @status 200
     * @throws RestException
     */
    protected function post(int $id, array $messages): HelperRepresentation
    {
        $this->checkAccess();

        if (! $this->getWidgetDao()->searchWidgetExistence($id)) {
            throw new CrossTrackerWidgetNotFoundException();
        }

        $current_user_with_logged_in_information = \UserManager::instance()->getCurrentUserWithLoggedInInformation();
        try {
            $this->getUserIsAllowedToSeeWidgetChecker()->checkUserIsAllowedToSeeWidget($current_user_with_logged_in_information->user, $id);

            $cross_tracker_retriever = new CrossTrackerWidgetRetriever($this->getWidgetDao());
            return $cross_tracker_retriever->retrieveWidgetById($id)->match(
                function (ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget) use ($current_user_with_logged_in_information, $messages): HelperRepresentation {
                    $assistant = match ($widget::class) {
                        ProjectCrossTrackerWidget::class => new ProjectAssistant($widget),
                        UserCrossTrackerWidget::class => new UserAssistant(),
                    };

                    $user_messages = array_map(static fn (MessageRepresentation $message): Message => $message->toMistralMessage(), $messages);

                    $mistral_connector = new MistralConnectorLive(HttpClientFactory::createClientWithCustomTimeout(60));
                    return EndUserAIRequestor::fromCurrentUser($current_user_with_logged_in_information)
                        ->andThen(
                            fn (AIRequestorEntity $requestor) => $mistral_connector->sendCompletion(
                                $requestor,
                                $assistant->getCompletion($current_user_with_logged_in_information->user, $user_messages)
                            )
                        )
                        ->match(
                            static fn (CompletionResponse $response) => new HelperRepresentation((string) $response->choices[0]->message->content),
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
