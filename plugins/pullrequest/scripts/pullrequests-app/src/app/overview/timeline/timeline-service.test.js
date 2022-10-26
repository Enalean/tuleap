/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

import angular from "angular";
import tuleap_pullrequest_module from "../../app.js";

import "angular-mocks";

describe("TimelineService", function () {
    var $httpBackend, TimelineService;

    beforeEach(function () {
        angular.mock.module(tuleap_pullrequest_module);

        angular.mock.inject(function (_$httpBackend_, _TimelineService_) {
            $httpBackend = _$httpBackend_;
            TimelineService = _TimelineService_;
        });
    });

    describe("#getTimeline", function () {
        var pullRequest;
        var limit = 50,
            offset = 0;

        beforeEach(function () {
            pullRequest = {
                id: "1",
                user_id: 102,
            };
        });

        it("requests a timeline of pull request events from the REST service", function () {
            var expectedUrl =
                "/api/v1/pull_requests/" +
                pullRequest.id +
                "/timeline?limit=" +
                limit +
                "&offset=" +
                offset;
            $httpBackend.expectGET(expectedUrl).respond([]);

            TimelineService.getTimeline(pullRequest, limit, offset);

            $httpBackend.verifyNoOutstandingExpectation();
        });
    });

    describe("#addComment", function () {
        var pullRequest = {
            id: "1",
        };

        it("sends a request to post a new comment", function () {
            var newComment = {
                content: "hello",
                user_id: 100,
            };
            var expectedUrl = "/api/v1/pull_requests/" + pullRequest.id + "/comments";
            $httpBackend.expectPOST(expectedUrl, newComment).respond(newComment);

            TimelineService.addComment(pullRequest, [], newComment);

            $httpBackend.verifyNoOutstandingExpectation();
        });

        it("returns a timeline with the new comment", function () {
            var timeline = [];
            var newComment = {
                content: "hello",
                user_id: 100,
            };
            var expectedComment = {
                id: 1,
                user: {
                    id: 100,
                },
                content: "hello",
                type: "comment",
            };
            var expectedUrl = "/api/v1/pull_requests/" + pullRequest.id + "/comments";
            $httpBackend.expectPOST(expectedUrl, newComment).respond(expectedComment);

            TimelineService.addComment(pullRequest, timeline, newComment);
            $httpBackend.flush();

            expect(timeline.map(({ id }) => id)).toEqual([expectedComment.id]);
        });
    });
});
