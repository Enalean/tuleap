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

import { mockFetchSuccess } from "tlp-mocks";
import { getProjectList, getTrackerList } from "./rest-querier.js";
import { restore as restoreFetch, rewire$recursiveGet } from "tlp-fetch";

describe("API querier", () => {
    let recursiveGet;
    beforeEach(() => {
        recursiveGet = jasmine.createSpy("recursiveGet");
        rewire$recursiveGet(recursiveGet);
    });

    afterEach(() => {
        restoreFetch();
    });

    describe("getProjectList", () => {
        it("it will get all project user is tracker admin of", () => {
            const return_json = [
                {
                    id: 102,
                    label: "Project name"
                }
            ];

            mockFetchSuccess(recursiveGet, { return_json });
            getProjectList();

            expect(recursiveGet).toHaveBeenCalledWith(
                "/api/projects",
                jasmine.objectContaining({
                    params: {
                        query: '{"is_tracker_admin":"true"}',
                        limit: 50,
                        offset: 0
                    }
                })
            );
        });
    });

    describe("getTrackerList", () => {
        it("Given a project id, then it will get all trackers user is admin of", () => {
            const return_json = [
                {
                    id: 10,
                    label: "Tracker name"
                }
            ];

            mockFetchSuccess(recursiveGet, { return_json });
            const project_id = 5;

            getTrackerList(project_id);

            expect(recursiveGet).toHaveBeenCalledWith(
                "/api/projects/5/trackers/",
                jasmine.objectContaining({
                    params: {
                        query: '{"is_tracker_admin":"true"}',
                        limit: 50,
                        offset: 0
                    }
                })
            );
        });
    });
});
