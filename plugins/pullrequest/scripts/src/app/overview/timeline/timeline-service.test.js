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
    var $httpBackend, $sce, TimelineService;

    beforeEach(function () {
        angular.mock.module(tuleap_pullrequest_module);

        angular.mock.inject(function (_$httpBackend_, _$sce_, _TimelineService_) {
            $httpBackend = _$httpBackend_;
            $sce = _$sce_;
            TimelineService = _TimelineService_;
        });
    });

    describe("#getTimeline", function () {
        var backendData, pullRequest;
        var limit = 50,
            offset = 0;

        beforeEach(function () {
            pullRequest = {
                id: "1",
                user_id: 102,
            };

            backendData = {
                collection: [
                    {
                        id: "6",
                        user: {
                            id: 102,
                            display_name: "Site User (userX)",
                            avatar_url: "/themes/common/images/avatar_default.png",
                        },
                        post_date: "1970-01-01T00:00:00+00:00",
                        content: "Hello world",
                        type: "comment",
                    },
                    {
                        id: "8",
                        user: {
                            id: 101,
                            display_name: "Site Administrator (admin)",
                            avatar_url: "/themes/common/images/avatar_default.png",
                        },
                        post_date: "1970-01-01T00:00:00+00:00",
                        content: "Hello\nSite User\n",
                        type: "inline-comment",
                        file_path: "Readme.md",
                        is_outdated: false,
                    },
                    {
                        id: "9",
                        user: {
                            id: 101,
                            display_name: "Site Administrator (admin)",
                            avatar_url: "/themes/common/images/avatar_default.png",
                        },
                        post_date: "1970-01-01T00:00:00+00:00",
                        content: "Obsolete inline comment",
                        type: "inline-comment",
                        file_path: "Readme.md",
                        is_outdated: true,
                    },
                    {
                        user: {
                            id: 102,
                            display_name: "Site User (userX)",
                            avatar_url: "/themes/common/images/avatar_default.png",
                        },
                        post_date: "1970-01-01T00:00:00+00:00",
                        type: "timeline-event",
                        event_type: "update",
                    },
                    {
                        user: {
                            id: 101,
                            display_name: "Site Administrator (admin)",
                            avatar_url: "/themes/common/images/avatar_default.png",
                        },
                        post_date: "1970-01-01T00:00:00+00:00",
                        type: "timeline-event",
                        event_type: "rebase",
                    },
                    {
                        user: {
                            id: 102,
                            display_name: "Site User (userX)",
                            avatar_url: "/themes/common/images/avatar_default.png",
                        },
                        post_date: "1970-01-01T00:00:00+00:00",
                        type: "timeline-event",
                        event_type: "merge",
                    },
                    {
                        user: {
                            id: 101,
                            display_name: "Site Administrator (admin)",
                            avatar_url: "/themes/common/images/avatar_default.png",
                        },
                        post_date: "1970-01-01T00:00:00+00:00",
                        type: "timeline-event",
                        event_type: "abandon",
                    },
                    {
                        user: {
                            id: 102,
                            display_name: "Site User (userX)",
                            avatar_url: "/themes/common/images/avatar_default.png",
                        },
                        post_date: "2020-01-01T00:00:00+00:00",
                        type: "reviewer-change",
                        event_type: "reviewer-change",
                        added_reviewers: [
                            {
                                id: 101,
                                display_name: "Site Administrator (admin)",
                                avatar_url: "/themes/common/images/avatar_default.png",
                            },
                        ],
                        removed_reviewers: [],
                    },
                ],
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

        it("formats the content of each timeline event", function () {
            $httpBackend.whenGET().respond(backendData);

            var timeline;
            TimelineService.getTimeline(pullRequest, limit, offset).then(function (tl) {
                timeline = tl;
            });
            $httpBackend.flush();

            expect(timeline).toHaveLength(7);
            expect($sce.getTrustedHtml(timeline[0].content)).toEqual("Hello world");
            expect($sce.getTrustedHtml(timeline[1].content)).toEqual("Hello<br/>Site User<br/>");

            expect($sce.getTrustedHtml(timeline[3].content)).toEqual(
                "Has updated the pull request."
            );
            expect($sce.getTrustedHtml(timeline[4].content)).toEqual(
                "Has rebased the pull request."
            );
            expect($sce.getTrustedHtml(timeline[5].content)).toEqual(
                "Has merged the pull request."
            );
            expect($sce.getTrustedHtml(timeline[6].content)).toEqual(
                "Has abandoned the pull request."
            );
        });

        it("sets author flag for each timeline event", function () {
            $httpBackend.whenGET().respond(backendData);

            var timeline;
            TimelineService.getTimeline(pullRequest, limit, offset).then(function (tl) {
                timeline = tl;
            });
            $httpBackend.flush();

            expect(timeline[0].isFromPRAuthor).toBe(true);
            expect(timeline[1].isFromPRAuthor).toBe(false);
            expect(timeline[3].isFromPRAuthor).toBe(true);
            expect(timeline[4].isFromPRAuthor).toBe(false);
        });

        it("sets flags for inline comments, both outdated or not", function () {
            $httpBackend.whenGET().respond(backendData);

            var timeline;
            TimelineService.getTimeline(pullRequest, limit, offset).then(function (tl) {
                timeline = tl;
            });
            $httpBackend.flush();

            expect(timeline[0].isInlineComment).toBe(false);
            expect(timeline[1].isInlineComment).toBe(true);
            expect(timeline[2].isInlineComment).toBe(true);
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
