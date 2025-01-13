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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\User;

use Codendi_Mail_Interface;
use CSRFSynchronizerToken;

/**
 * @psalm-immutable
 */
final readonly class UserPreferencesPresenter
{
    public bool $wants_notifications;
    public bool $wants_all_updates_after_change;
    public bool $email_format_html;
    public bool $email_format_text;

    public function __construct(
        public CSRFSynchronizerToken $csrf_token,
        public string $form_post_uri,
        NotificationOnOwnActionPreference $own_action_preference,
        NotificationOnAllUpdatesPreference $all_updates_preference,
        string $mail_format_preference,
    ) {
        $this->wants_notifications            = $own_action_preference->enabled;
        $this->wants_all_updates_after_change = $all_updates_preference->enabled;
        $this->email_format_html              = $mail_format_preference === Codendi_Mail_Interface::FORMAT_HTML;
        $this->email_format_text              = $mail_format_preference !== Codendi_Mail_Interface::FORMAT_HTML;
    }
}
