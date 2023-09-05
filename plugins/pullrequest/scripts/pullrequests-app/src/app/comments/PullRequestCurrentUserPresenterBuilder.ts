/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { CurrentPullRequestUserPresenter } from "@tuleap/plugin-pullrequest-comments";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";

export const PullRequestCurrentUserPresenterBuilder = {
    fromUserInfo: (
        user_id: number,
        avatar_url: string,
        user_locale: string,
        preferred_date_format: string,
        preferred_relative_date_display: RelativeDatesDisplayPreference,
    ): CurrentPullRequestUserPresenter => ({
        user_id,
        avatar_url,
        user_locale,
        preferred_date_format,
        preferred_relative_date_display,
    }),
};
