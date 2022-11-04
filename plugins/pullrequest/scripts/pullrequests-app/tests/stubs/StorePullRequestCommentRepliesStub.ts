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

import type { StorePullRequestCommentReplies } from "../../src/app/comments/PullRequestCommentRepliesStore";
import { PullRequestCommentPresenterStub } from "./PullRequestCommentPresenterStub";
import type { PullRequestCommentRepliesCollectionPresenter } from "../../src/app/comments/PullRequestCommentRepliesCollectionPresenter";
import type { PullRequestCommentPresenter } from "../../src/app/comments/PullRequestCommentPresenter";

export const StorePullRequestCommentRepliesStub = {
    withReplies: (): StorePullRequestCommentReplies => ({
        getCommentReplies: (): PullRequestCommentRepliesCollectionPresenter => [
            PullRequestCommentPresenterStub.buildWithData({
                id: 10,
                parent_id: 9,
                post_date: "2022-11-03T14:00:57+01:00",
            }),
            PullRequestCommentPresenterStub.buildWithData({
                id: 11,
                parent_id: 9,
                post_date: "2022-11-03T14:30:57+01:00",
            }),
            PullRequestCommentPresenterStub.buildWithData({
                id: 12,
                parent_id: 9,
                post_date: "2022-11-03T14:50:57+01:00",
            }),
        ],
        getAllRootComments: (): PullRequestCommentPresenter[] => [
            PullRequestCommentPresenterStub.buildWithData({ id: 9 }),
        ],
        addRootComment: (): void => {
            // Do nothing
        },
    }),
    withNoReplies: (): StorePullRequestCommentReplies => ({
        getCommentReplies: (): PullRequestCommentRepliesCollectionPresenter => [],
        getAllRootComments: (): PullRequestCommentPresenter[] => [],
        addRootComment: (): void => {
            // Do nothing
        },
    }),
};
