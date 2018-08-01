/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import { mockFetchError } from "tlp-mocks";
import { loadProjectList, loadTrackerList } from "./actions.js";
import {
    rewire$getProjectList,
    rewire$getTrackerList,
    restore as restoreFetch
} from "../api/rest-querier.js";

describe("Store actions", () => {
    describe("loadProjectList", () => {
        let getProjectList, context;
        beforeEach(() => {
            getProjectList = jasmine.createSpy("getProjectList");
            rewire$getProjectList(getProjectList);
            context = {
                commit: jasmine.createSpy("commit")
            };
        });

        afterEach(() => {
            restoreFetch();
        });

        it("When I want to load the project, Then it should fetch them asynchronously and put them in the store.", async () => {
            const json = [
                {
                    id: 102,
                    label: "Project name"
                }
            ];

            getProjectList.and.returnValue(Promise.resolve(json));

            await loadProjectList(context);

            expect(context.commit).toHaveBeenCalledWith("saveProjects", json);
            expect(context.commit).toHaveBeenCalledWith("setIsLoadingInitial", false);
        });

        it("When the server responds with an error the error message is stored", async () => {
            const error_json = {
                error: {
                    code: "403",
                    message: "error"
                }
            };
            mockFetchError(getProjectList, { error_json });

            await loadProjectList(context);
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "error");
        });
    });

    describe("loadTrackerList", () => {
        let getTrackerList, context;
        beforeEach(() => {
            getTrackerList = jasmine.createSpy("getTrackerList");
            rewire$getTrackerList(getTrackerList);
            context = {
                commit: jasmine.createSpy("commit")
            };
        });

        afterEach(() => {
            restoreFetch();
        });

        it("When I want to load the tracker, Then it should fetch them asynchronously and put them in the store.", async () => {
            const return_json = [
                {
                    id: 10,
                    label: "Tracker name"
                }
            ];

            getTrackerList.and.returnValue(Promise.resolve(return_json));

            await loadTrackerList(context);

            expect(context.commit).toHaveBeenCalledWith("setAreTrackerLoading", true);
            expect(context.commit).toHaveBeenCalledWith("saveTrackers", return_json);
            expect(context.commit).toHaveBeenCalledWith("setAreTrackerLoading", false);
        });

        it("When the server responds with an error the error message is stored", async () => {
            const error_json = {
                error: {
                    code: "403",
                    message: "error"
                }
            };
            mockFetchError(getTrackerList, { error_json });

            await loadTrackerList(context);
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "error");
        });
    });
});
