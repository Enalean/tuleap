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

        $this->title                  = dgettext('tuleap-botmattermost_git', 'Notifications in Mattermost');
        $this->description            = dgettext('tuleap-botmattermost_git', 'Choose a bot to send message in Mattermost when push occurs in this repository.');
        $this->description_create_bot = dgettext('tuleap-botmattermost_git', 'If you don\'t see a Bot linked to your Mattermost project/team, please contact your administrator.');

        $this->modal_add_title          = dgettext('tuleap-botmattermost_git', 'Add notification');
        $this->modal_edit_title         = dgettext('tuleap-botmattermost_git', 'Edit notification');
        $this->modal_delete_title       = dgettext('tuleap-botmattermost_git', 'Delete notification');
        $this->modal_delete_content     = dgettext('tuleap-botmattermost_git', 'You are about to remove the notification. Please confirm your action.');

        $this->label_bot_list                  = dgettext('tuleap-botmattermost_git', 'Bot list:');
        $this->label_bot_name                  = dgettext('tuleap-botmattermost_git', 'Bot name');
        $this->label_avatar_url                = dgettext('tuleap-botmattermost_git', 'Avatar URL');
        $this->label_channels_handles          = dgettext('tuleap-botmattermost_git', 'Channel handles list');
        $this->input_channels_handles          = dgettext('tuleap-botmattermost_git', 'channel1, channel2, channel3');
        $this->purified_info_channels_handles  = Codendi_HTMLPurifier::instance()->purify(
            dgettext('tuleap-botmattermost_git', 'The channel handle is display in its URL<br>example: https://example.com/myGroup/channels/mychannel<br>handle: mychannel'),
            CODENDI_PURIFIER_LIGHT
        );

        $this->any_configured_notification      = dgettext('tuleap-botmattermost_git', 'The Mattermost notification has not yet been configured.');
        $this->any_configured_notification_tips = dgettext('tuleap-botmattermost_git', 'To begin, click on add notification button below.');
        $this->empty_bot_list                   = dgettext('tuleap-botmattermost_git', 'No bots are defined by the system administrator. The notification configuration is not available.');
        $this->empty_channel_list               = dgettext('tuleap-botmattermost_git', 'No channel selected, the channel defined at the webhook creation will be used as default');


        $this->button_config  = dgettext('tuleap-botmattermost_git', 'Add notification');
        $this->button_confirm = dgettext('tuleap-botmattermost_git', 'Add');
        $this->button_close   = dgettext('tuleap-botmattermost_git', 'Cancel');
        $this->button_delete  = dgettext('tuleap-botmattermost_git', 'Delete');
        $this->button_edit    = dgettext('tuleap-botmattermost_git', 'Edit');
    }
}
