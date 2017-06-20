<?php
/**
 * Copyright (c) Enalean, 2016-2017. All Rights Reserved.
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

namespace Tuleap\BotMattermostGit;

use Codendi_HTMLPurifier;
use CSRFSynchronizerToken;
use GitRepository;
use GitViews_RepoManagement_Pane_Notification;

class Presenter
{
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    public $bot_assigned;
    public $bots;

    private $repository;

    public function __construct(
        CSRFSynchronizerToken $csrf_token,
        GitRepository $repository,
        array $bots,
        $bot_assigned
    ) {
        $this->csrf_token    = $csrf_token;
        $this->repository    = $repository;
        $this->bots          = $bots;
        $this->bot_assigned  = $bot_assigned;

        $this->project_id    = $this->repository->getProjectId();
        $this->repository_id = $this->repository->getId();
        $this->has_bots      = ! empty($bots);
        $this->title         = $GLOBALS['Language']->getText('plugin_botmattermost_git', 'title');
        $this->description   = $GLOBALS['Language']->getText('plugin_botmattermost_git', 'description');

        $this->modal_add_title          = $GLOBALS['Language']->getText('plugin_botmattermost_git', 'modal_header_configure_notification');
        $this->modal_edit_title         = $GLOBALS['Language']->getText('plugin_botmattermost_git', 'modal_header_edit_configure_notification');
        $this->modal_delete_title       = $GLOBALS['Language']->getText('plugin_botmattermost_git', 'modal_header_delete_configure_notification');
        $this->modal_delete_content     = $GLOBALS['Language']->getText('plugin_botmattermost_git', 'modal_delete_content');

        $this->label_bot_name                  = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_label_bot_name');
        $this->label_avatar_url                = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_label_avatar_url');
        $this->label_channels_handles          = $GLOBALS['Language']->getText('plugin_botmattermost_git', 'configuration_label_channels_handles');
        $this->input_channels_handles          = $GLOBALS['Language']->getText('plugin_botmattermost_git', 'configuration_input_channels_handles');
        $this->purified_info_channels_handles  = Codendi_HTMLPurifier::instance()->purify(
            $GLOBALS['Language']->getText('plugin_botmattermost_git', 'configuration_info_channels_handles'),
            CODENDI_PURIFIER_LIGHT
        );

        $this->any_configured_notification      = $GLOBALS['Language']->getText('plugin_botmattermost_git', 'any_configured_notification');
        $this->any_configured_notification_tips = $GLOBALS['Language']->getText('plugin_botmattermost_git', 'any_configured_notification_tips');
        $this->empty_bot_list                   = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_empty_list');
        $this->empty_channel_list               = $GLOBALS['Language']->getText('plugin_botmattermost_git', 'empty_channel_list');


        $this->button_config  = $GLOBALS['Language']->getText('plugin_botmattermost_git', 'button_configure_notification');
        $this->button_confirm = $GLOBALS['Language']->getText('plugin_botmattermost_git', 'button_confirm');
        $this->button_close   = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_button_close');
        $this->button_delete  = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_button_delete');
        $this->button_edit    = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_button_edit');
    }
}
