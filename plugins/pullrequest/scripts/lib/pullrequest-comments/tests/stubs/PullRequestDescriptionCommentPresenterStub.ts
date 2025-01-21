/*
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

import type { PullRequestDescriptionCommentPresenter } from "../../src/description-comment/PullRequestDescriptionCommentPresenter";
import { FORMAT_COMMONMARK } from "@tuleap/plugin-pullrequest-constants";

export const PullRequestDescriptionCommentPresenterStub = {
    buildInitial: (content: string): PullRequestDescriptionCommentPresenter => ({
        pull_request_id: 640,
        project_id: 588,
        author: {
            id: 122,
            display_name: "Irene Hong (ihong)",
            avatar_url: "url/to/user_avatar.png",
            user_url: "url/to/user_page",
        },
        post_date: "2017-12-29T19:07:29+01",
        can_user_update_description: true,
        format: FORMAT_COMMONMARK,
        raw_content: content,
        post_processed_content: `<p>Post-processed content</p>`,
        content: "",
    }),
};
