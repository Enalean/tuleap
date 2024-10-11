<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Dashboard\User;

use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;

final class FirstTimerPresenter
{
    public ?UserPresenter $invited_by_user;
    public ?string $project_name = null;
    public ?string $project_icon = null;

    public function __construct(
        public string $real_name,
        public string $plateform_name,
        ?\PFUser $invited_by_user,
        ?\Project $project,
        public JavascriptViteAsset $javascript_assets,
        ProvideUserAvatarUrl $provide_user_avatar_url,
    ) {
        if ($project) {
            $this->project_name = $project->getPublicName();
            $this->project_icon = EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat(
                $project->getIconUnicodeCodepoint()
            );
        }

        $this->invited_by_user = $invited_by_user ? new UserPresenter($invited_by_user, $provide_user_avatar_url) : null;
    }
}
