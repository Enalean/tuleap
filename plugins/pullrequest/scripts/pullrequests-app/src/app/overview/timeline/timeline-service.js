/*
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

import { PullRequestCommentPresenter } from "../../comments/PullRequestCommentPresenter";

export default TimelineService;

TimelineService.$inject = ["TimelineRestService", "gettextCatalog", "$state"];

function TimelineService(TimelineRestService, gettextCatalog, $state) {
    const self = this;

    Object.assign(self, {
        timeline_pagination: {
            limit: 50,
            offset: 0,
        },
        getTimeline,
        addComment,
    });

    function getPaginatedTimeline(pull_request, accTimeline, limit, offset) {
        return TimelineRestService.getTimeline(pull_request.id, limit, offset).then(function (
            response
        ) {
            accTimeline.push.apply(accTimeline, response.data.collection);

            var headers = response.headers();
            var total = headers["x-pagination-size"];

            if (limit + offset < total) {
                return getPaginatedTimeline(pull_request, accTimeline, limit, offset + limit);
            }

            return accTimeline;
        });
    }

    function getTimeline(pull_request, limit, offset) {
        var initialTimeline = [];
        return getPaginatedTimeline(pull_request, initialTimeline, limit, offset).then(function (
            timeline
        ) {
            return timeline
                .filter((event) => event.type !== "reviewer-change")
                .map((event) =>
                    PullRequestCommentPresenter.fromTimelineEvent($state, event, pull_request)
                );
        });
    }

    function addComment(pullRequest, timeline, comment_replies_store, newComment) {
        return TimelineRestService.addComment(pullRequest.id, newComment).then(function (event) {
            const comment_presenter = PullRequestCommentPresenter.fromTimelineEvent(
                $state,
                event,
                pullRequest
            );
            comment_replies_store.addRootComment(comment_presenter);
        });
    }
}
