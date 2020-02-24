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

namespace Tuleap\Git\Webhook;

use CSRFSynchronizerToken;

class WebhookSettingsPresenter
{
    public $edit_modal;
    public $create_modal;
    public $sections;
    public $create_buttons;
    public $description;
    public $title;
    public $last_push;
    public $url;
    public $logs;
    public $edit_hook;
    public $remove;
    public $btn_close;
    public $btn_cancel;
    public $csrf_token;

    /**
     * @var string
     */
    public $additional_description;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        $title,
        $description,
        string $additional_description,
        array $create_buttons,
        array $sections,
        WebhookModalPresenter $create_modal,
        WebhookModalPresenter $edit_modal
    ) {
        $this->csrf_token     = $csrf->getToken();
        $this->title          = $title;
        $this->description    = $description;
        $this->create_buttons = $create_buttons;
        $this->sections       = $sections;
        $this->create_modal   = $create_modal;
        $this->edit_modal     = $edit_modal;

        $this->has_sections = count($sections) > 0;

        $this->last_push       = dgettext('tuleap-git', 'Last push');
        $this->url             = dgettext('tuleap-git', 'URL');
        $this->empty_hooks     = dgettext('tuleap-git', 'No defined webhooks yet');

        $this->logs            = dgettext('tuleap-git', 'Logs');
        $this->edit_hook       = $GLOBALS['Language']->getText('global', 'btn_edit');
        $this->remove          = dgettext('tuleap-git', 'Remove');

        $this->btn_close       = $GLOBALS['Language']->getText('global', 'btn_close');
        $this->btn_cancel      = $GLOBALS['Language']->getText('global', 'btn_cancel');

        $this->additional_description = $additional_description;
    }
}
