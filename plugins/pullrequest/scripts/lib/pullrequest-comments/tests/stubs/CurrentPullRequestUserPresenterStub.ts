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

import type { CurrentPullRequestUserPresenter } from "../../src/types";
import { PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN } from "@tuleap/tlp-relative-date";

const base_user_presenter: CurrentPullRequestUserPresenter = {
    user_id: 104,
    avatar_url: "url/to/avatar.png",
    user_locale: "fr_FR",
    preferred_date_format: "Y/M/D H:m",
    preferred_relative_date_display: PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
};

export const CurrentPullRequestUserPresenterStub = {
    withDefault: (): CurrentPullRequestUserPresenter => ({
        ...base_user_presenter,
    }),
    withUserId: (user_id: number): CurrentPullRequestUserPresenter => ({
        ...base_user_presenter,
        user_id,
    }),
};
