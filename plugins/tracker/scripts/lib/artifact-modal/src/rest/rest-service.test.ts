/*
 * Copyright (c) Enalean, 2017-present. All Rights Reserved.
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

import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import * as RestService from "./rest-service";

import * as tlp_fetch from "@tuleap/tlp-fetch";
import { TEXT_FORMAT_TEXT } from "@tuleap/plugin-tracker-constants";

describe("rest-service", () => {
    it("getTracker() - Given a tracker id, when I get the tracker, then a promise will be resolved with the tracker", async () => {
        const return_json = {
            id: 84,
            label: "Functionize recklessly",
        };
        const tlpGetSpy = jest.spyOn(tlp_fetch, "get");
        mockFetchSuccess(tlpGetSpy, { return_json });

        const tracker = await RestService.getTracker(84);

        expect(tracker).toEqual({
            id: 84,
            label: "Functionize recklessly",
        });
        expect(tlpGetSpy).toHaveBeenCalledWith("/api/v1/trackers/84");
    });

    it("getArtifactWithCompleteTrackerStructure() - given an artifact id, when I get the artifact's field values, then a promise will be resolved with a map of field values indexed by their field id", async () => {
        const return_json = {
            id: 40,
            values: [
                {
                    field_id: 866,
                    label: "unpredisposed",
                    value: "ectogenous",
                },
                {
                    field_id: 468,
                    label: "coracler",
                    value: "caesaropapism",
                },
            ],
            title: "coincoin",
        };
        mockFetchSuccess(jest.spyOn(tlp_fetch, "get"), {
            return_json,
            headers: {
                get: (header) => {
                    if (header === "Etag") {
                        return "etag";
                    }
                    if (header === "Last-Modified") {
                        return "1629098386";
                    }
                    return null;
                },
            },
        });

        const values = await RestService.getArtifactWithCompleteTrackerStructure(40);

        expect(values).toEqual({ ...return_json, Etag: "etag", "Last-Modified": "1629098386" });
    });

    describe("searchUsers() -", () => {
        it("Given a query, when I search for a username containing the query, then a promise will be resolved with an array of user representations", async () => {
            const return_json = [
                { id: 629, label: "Blue" },
                { id: 593, label: "Blurred" },
            ];
            const tlpGetSpy = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGetSpy, { return_json });

            const {
                results: [first_user, second_user],
            } = await RestService.searchUsers("Blu");

            expect(first_user).toEqual({ id: 629, label: "Blue" });
            expect(second_user).toEqual({ id: 593, label: "Blurred" });
            expect(tlpGetSpy).toHaveBeenCalledWith("/api/v1/users", {
                params: { query: "Blu" },
            });
        });
    });

    describe("getUserPreference() -", () => {
        it("Given a key, when I search for a preference, then a promise will be resolved with an object of user preference representation", async () => {
            const return_json = {
                key: "tracker_comment_invertorder_93",
                value: "1",
            };
            const tlpGetSpy = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGetSpy, { return_json });

            const result = await RestService.getUserPreference(
                102,
                "tracker_comment_invertorder_93",
            );

            expect(result).toEqual(return_json);
            expect(tlpGetSpy).toHaveBeenCalledWith("/api/v1/users/102/preferences", {
                cache: "force-cache",
                params: {
                    key: "tracker_comment_invertorder_93",
                },
            });
        });
    });

    describe("editArtifact() -", () => {
        it("Given an artifact id and an array of fields containing their id and selected value, when I edit an artifact, then the field values will be sent using the edit REST route and a promise will be resolved with the edited artifact's id", async () => {
            const followup_comment = {
                body: "",
                format: TEXT_FORMAT_TEXT,
            };
            const field_values = [
                { field_id: 47, value: "unpensionableness" },
                { field_id: 71, bind_value_ids: [726, 332] },
            ];
            const tlpPutSpy = jest.spyOn(tlp_fetch, "put");
            mockFetchSuccess(tlpPutSpy, {
                return_json: {
                    values: field_values,
                    comment: followup_comment,
                },
            });

            const artifact_edition = await RestService.editArtifact(
                8354,
                field_values,
                followup_comment,
            );

            expect(artifact_edition).toEqual({
                id: 8354,
            });
            expect(tlpPutSpy).toHaveBeenCalledWith("/api/v1/artifacts/8354", {
                headers: {
                    "content-type": "application/json",
                },
                body: JSON.stringify({
                    values: field_values,
                    comment: followup_comment,
                }),
            });
        });
    });

    describe("editArtifactWithConcurrencyChecking() -", () => {
        it("Given an artifact id and an array of fields, when I edit an artifact in concurrency mode, then the field values will be sent using the edit REST route and a promise will be resolved with the edited artifact's id", async () => {
            const followup_comment = {
                body: "",
                format: TEXT_FORMAT_TEXT,
            };
            const field_values = [
                { field_id: 47, value: "unpensionableness" },
                { field_id: 71, bind_value_ids: [726, 332] },
            ];
            const tlpPutSpy = jest.spyOn(tlp_fetch, "put");
            mockFetchSuccess(tlpPutSpy, {
                return_json: {
                    values: field_values,
                    comment: followup_comment,
                },
            });

            const artifact_edition = await RestService.editArtifactWithConcurrencyChecking(
                8354,
                field_values,
                followup_comment,
                "etag",
                "1629098047",
            );

            expect(artifact_edition).toEqual({
                id: 8354,
            });
            expect(tlpPutSpy).toHaveBeenCalledWith("/api/v1/artifacts/8354", {
                headers: {
                    "content-type": "application/json",
                    "If-match": "etag",
                    "If-Unmodified-Since": "1629098047",
                },
                body: JSON.stringify({
                    values: field_values,
                    comment: followup_comment,
                }),
            });
        });

        it("Given an artifact id and and no etag, when I edit an artifact in concurrency mode, then the field values will be sent using the edit REST route and a promise will be resolved with the edited artifact's id", async () => {
            const followup_comment = {
                body: "",
                format: TEXT_FORMAT_TEXT,
            };
            const tlpPutSpy = jest.spyOn(tlp_fetch, "put");
            mockFetchSuccess(tlpPutSpy, {
                return_json: {
                    values: [],
                    comment: followup_comment,
                },
            });

            const artifact_edition = await RestService.editArtifactWithConcurrencyChecking(
                8354,
                [],
                followup_comment,
                null,
                "1629098047",
            );

            expect(artifact_edition).toEqual({
                id: 8354,
            });
            expect(tlpPutSpy).toHaveBeenCalledWith("/api/v1/artifacts/8354", {
                headers: {
                    "content-type": "application/json",
                    "If-Unmodified-Since": "1629098047",
                },
                body: JSON.stringify({
                    values: [],
                    comment: followup_comment,
                }),
            });
        });
    });
});
