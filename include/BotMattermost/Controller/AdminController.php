<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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
use Tuleap\BotMattermost\BotMattermostDeleted;
use Tuleap\BotMattermost\Presenter\AdminPresenter;
use Tuleap\Theme\BurningParrot\BurningParrotTheme;
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
     * @var BurningParrotTheme
     */
    private $burning_parrot_theme;

    /**
     * @var BaseLanguage
     */
    private $language;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        BotFactory $bot_factory,
        EventManager $event_manager,
        BurningParrotTheme $burning_parrot_theme,
        BaseLanguage $language
    ) {
        $this->csrf                 = $csrf;
        $this->bot_factory          = $bot_factory;
        $this->event_manager        = $event_manager;
        $this->burning_parrot_theme = $burning_parrot_theme;
        $this->language             = $language;
    }

    public function displayIndex()
    {
        try {
            $admin_presenter     = new AdminPresenter($this->csrf, $this->bot_factory->getBots());
            $admin_page_renderer = new AdminPageRenderer();
            $admin_page_renderer->renderAPresenter(
                $admin_presenter->title,
                PLUGIN_BOT_MATTERMOST_BASE_DIR.'/template/',
                'index',
                $admin_presenter
            );
        } catch (BotNotFoundException $e) {
            $this->redirectToAdminSectionWithErrorFeedback($e);
        } catch (ChannelsNotFoundException $e) {
            $this->redirectToAdminSectionWithErrorFeedback($e);
        }
    }

    public function addBot(HTTPRequest $request)
    {
        $this->csrf->check();
        if ($this->validPostArgument($request)) {
            try {
                $this->bot_factory->save(
                    $request->get('bot_name'),
                    $request->get('webhook_url'),
                    $request->get('avatar_url')
                );
                $this->burning_parrot_theme->addFeedback(Feedback::INFO, $this->language->getText('plugin_botmattermost', 'alert_success_add_bot'));
            } catch (CannotCreateBotException $e) {
                $this->burning_parrot_theme->addFeedback(Feedback::ERROR, $e->getMessage());
            } catch (BotAlreadyExistException $e) {
                $this->burning_parrot_theme->addFeedback(Feedback::ERROR, $e->getMessage());
            }
        }
        $this->redirectToIndex();
    }

    public function deleteBot(HTTPRequest $request)
    {
        $this->csrf->check();
        $id = $request->get('bot_id');
        if ($this->validBotId($id)) {
            try {
                $bot   = $this->bot_factory->getBotById($id);
                $event = new BotMattermostDeleted($bot);
                $this->bot_factory->deleteBotById($bot->getId());
                $this->event_manager->processEvent($event);
                $this->burning_parrot_theme->addFeedback(Feedback::INFO, $this->language->getText('plugin_botmattermost','alert_success_delete_bot'));
            } catch (CannotDeleteBotException $e) {
                $this->burning_parrot_theme->addFeedback(Feedback::ERROR, $e->getMessage());
            }
        }
        $this->redirectToIndex();
    }

    public function editBot(HTTPRequest $request)
    {
        $this->csrf->check();
        $id = $request->get('bot_id');
        if ($this->validPostArgument($request) && $this->validBotId($id)) {
            try {
                $this->bot_factory->update(
                    $request->get('bot_name'),
                    $request->get('webhook_url'),
                    $request->get('avatar_url'),
                    $id
                );
                $this->burning_parrot_theme->addFeedback(Feedback::INFO, $this->language->getText('plugin_botmattermost', 'alert_success_edit_bot'));
            } catch (CannotUpdateBotException $e) {
                $this->burning_parrot_theme->addFeedback(Feedback::ERROR, $e->getMessage());
            }
        }
        $this->redirectToIndex();
    }

    private function validPostArgument(HTTPRequest $request)
    {
        if (! $request->existAndNonEmpty('bot_name') || ! $request->existAndNonEmpty('webhook_url')) {
            $this->burning_parrot_theme->addFeedback(Feedback::ERROR, $this->language->getText('plugin_botmattermost', 'alert_error_empty_input'));
            return false;
        }

        return (
            $this->validUrl($request->get('webhook_url')) &&
            $this->validOptionnalUrl($request->get('avatar_url'))
        );
    }

    private function validOptionnalUrl($url) {
        if (! $url) {
            return true;
        }

        return $this->validUrl($url);
    }

    private function validUrl($url)
    {
        $valid_url = new Valid_HTTPURI();
        if ($valid_url->validate($url)) {
            return true;
        } else {
            $this->burning_parrot_theme->addFeedback(Feedback::ERROR, $this->language->getText('plugin_botmattermost', 'alert_error_invalid_url'));
            return false;
        }
    }

    private function validBotId($id)
    {

        if ($this->bot_factory->getBotById($id)) {
            return true;
        } else {
            $this->burning_parrot_theme->addFeedback(Feedback::ERROR, $this->language->getText('plugin_botmattermost', 'alert_error_invalid_id'));
            return false;
        }
    }

    private function redirectToAdminSectionWithErrorFeedback(Exception $e)
    {
        $this->burning_parrot_theme->addFeedback(Feedback::ERROR, $e->getMessage());
        $this->burning_parrot_theme->redirect('/admin/');
    }

    private function redirectToIndex()
    {
        $this->burning_parrot_theme->redirect(BOT_MATTERMOST_BASE_URL.'/admin/');
    }
}
