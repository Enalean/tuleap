<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\BotMattermost\Controller;

use BaseLanguage;
use EventManager;
use Exception;
use HTTPRequest;
use CSRFSynchronizerToken;
use Feedback;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\BotMattermost\Bot\BotCreator;
use Tuleap\BotMattermost\Bot\BotDeletor;
use Tuleap\BotMattermost\Bot\BotEditor;
use Tuleap\BotMattermost\Exception\EmptyUpdateException;
use Tuleap\BotMattermost\Exception\ProvidedBotParameterIsNotValidException;
use Tuleap\BotMattermost\Presenter\AdminPresenter;
use Tuleap\Layout\BaseLayout;
use Valid_HTTPURI;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\Exception\CannotCreateBotException;
use Tuleap\BotMattermost\Exception\CannotDeleteBotException;
use Tuleap\BotMattermost\Exception\CannotUpdateBotException;
use Tuleap\BotMattermost\Exception\BotAlreadyExistException;
use Tuleap\BotMattermost\Exception\BotNotFoundException;
use Tuleap\BotMattermost\Exception\ChannelsNotFoundException;

class AdminController
{
    private $csrf;
    private $bot_factory;
    private $event_manager;

    /**
     * @var BaseLanguage
     */
    private $language;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        BotFactory $bot_factory,
        EventManager $event_manager,
        BaseLanguage $language,
        private BotDeletor $bot_deletor,
        private BotEditor $bot_editor,
        private BotCreator $bot_creator,
    ) {
        $this->csrf          = $csrf;
        $this->bot_factory   = $bot_factory;
        $this->event_manager = $event_manager;
        $this->language      = $language;
    }

    public function displayIndex(BaseLayout $response)
    {
        try {
            $admin_presenter     = new AdminPresenter($this->csrf, $this->bot_factory->getSystemBots());
            $admin_page_renderer = new AdminPageRenderer();
            $admin_page_renderer->renderAPresenter(
                dgettext('tuleap-botmattermost', "Bot Mattermost configuration"),
                PLUGIN_BOT_MATTERMOST_BASE_DIR . '/templates/',
                'index',
                $admin_presenter
            );
        } catch (BotNotFoundException $e) {
            $this->redirectToAdminSectionWithErrorFeedback($e, $response);
        } catch (ChannelsNotFoundException $e) {
            $this->redirectToAdminSectionWithErrorFeedback($e, $response);
        }
    }

    public function addBot(HTTPRequest $request, BaseLayout $response)
    {
        $this->csrf->check();
        if ($this->validPostArgument($request, $response)) {
            try {
                $this->bot_creator->createSystemBot(
                    $request->get('bot_name'),
                    $request->get('webhook_url'),
                    $request->get('avatar_url'),
                );
                $response->addFeedback(Feedback::INFO, dgettext('tuleap-botmattermost', 'Bot added successfully'));
            } catch (CannotCreateBotException | ProvidedBotParameterIsNotValidException $e) {
                $response->addFeedback(Feedback::ERROR, $e->getMessage());
            } catch (BotAlreadyExistException $e) {
                $response->addFeedback(Feedback::ERROR, $e->getMessage());
            }
        }
        $this->redirectToIndex($response);
    }

    public function deleteBot(HTTPRequest $request, BaseLayout $response)
    {
        $this->csrf->check();
        $bot_id = $request->get('bot_id');
        if ($this->validBotId($response, $bot_id)) {
            try {
                $bot = $this->bot_factory->getBotById($bot_id);
                $this->bot_deletor->deleteBot($bot);
                $response->addFeedback(Feedback::INFO, dgettext('tuleap-botmattermost', 'Bot successfully deleted'));
            } catch (CannotDeleteBotException | BotNotFoundException $e) {
                $response->addFeedback(Feedback::ERROR, $e->getMessage());
            }
        }
        $this->redirectToIndex($response);
    }

    public function editBot(HTTPRequest $request, BaseLayout $response)
    {
        $this->csrf->check();
        $id = $request->get('bot_id');
        if ($this->validPostArgument($request, $response) && $this->validBotId($response, $id)) {
            try {
                $this->bot_editor->editBotById(
                    $id,
                    $request->get('bot_name'),
                    $request->get('webhook_url'),
                    $request->get('avatar_url'),
                );
                $response->addFeedback(Feedback::INFO, dgettext('tuleap-botmattermost', 'Bot successfully edited'));
            } catch (CannotUpdateBotException | EmptyUpdateException | BotAlreadyExistException | ProvidedBotParameterIsNotValidException $e) {
                $response->addFeedback(Feedback::ERROR, $e->getMessage());
            }
        }
        $this->redirectToIndex($response);
    }

    private function validPostArgument(HTTPRequest $request, BaseLayout $response)
    {
        if (! $request->existAndNonEmpty('bot_name') || ! $request->existAndNonEmpty('webhook_url')) {
            $response->addFeedback(Feedback::ERROR, dgettext('tuleap-botmattermost', 'The name and the webhook URL input must be filled'));
            return false;
        }

        return (
            $this->validUrl($response, $request->get('webhook_url')) &&
            $this->validOptionalUrl($response, $request->get('avatar_url'))
        );
    }

    private function validOptionalUrl(BaseLayout $response, $url)
    {
        if (! $url) {
            return true;
        }

        return $this->validUrl($response, $url);
    }

    private function validUrl(BaseLayout $response, $url)
    {
        $valid_url = new Valid_HTTPURI();
        if ($valid_url->validate($url)) {
            return true;
        } else {
            $response->addFeedback(Feedback::ERROR, dgettext('tuleap-botmattermost', 'Invalid URL'));
            return false;
        }
    }

    private function validBotId(BaseLayout $response, $id)
    {
        if ($this->bot_factory->getBotById($id)) {
            return true;
        } else {
            $response->addFeedback(Feedback::ERROR, dgettext('tuleap-botmattermost', 'Invalid bot ID'));
            return false;
        }
    }

    private function redirectToAdminSectionWithErrorFeedback(Exception $e, BaseLayout $response): void
    {
        $response->addFeedback(Feedback::ERROR, $e->getMessage());
        $response->redirect('/admin/');
    }

    private function redirectToIndex(BaseLayout $response): void
    {
        $response->redirect(BOT_MATTERMOST_BASE_URL . '/admin/');
    }
}
