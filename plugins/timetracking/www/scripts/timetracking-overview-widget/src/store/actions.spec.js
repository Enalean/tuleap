/*
 * Copyright Enalean (c) 2019. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import * as actions from "./actions.js";
import initial_state from "./state.js";
import { mockFetchError, mockFetchSuccess, tlp } from "tlp-mocks";
import { ERROR_OCCURRED } from "../../../constants.js";

describe("Store actions", () => {
    let context;
    beforeEach(() => {
        context = {
            commit: jasmine.createSpy("commit"),
            state: { ...initial_state }
        };
    });

    describe("initWidgetWithReport - success", () => {
        it("Given a success response, When report is received, Then no message error is reveived", async () => {
            const rapport = [
                {
                    id: 1,
                    uri: "timetracking_reports/1",
                    trackers: [{ id: 1, label: "timetracking_tracker" }]
                }
            ];

            mockFetchSuccess(tlp.get, {
                return_json: rapport
            });

            await actions.initWidgetWithReport(context);
            expect(context.commit).toHaveBeenCalledWith("resetErrorMessage");
            expect(context.commit).toHaveBeenCalledWith("setSelectedTrackers", rapport.trackers);
        });
    });

    describe("initWidgetWithReport - rest errors", () => {
        it("Given a rest error ,When no error message is provided, Then it should add a generic error message on rest_feedback", async () => {
            mockFetchError(tlp.get, {});

            await actions.initWidgetWithReport(context);
            expect(context.commit).toHaveBeenCalledWith("resetErrorMessage");
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", ERROR_OCCURRED);
        });

        it("Given a rest error, When a json error message is received, Then the message is extracted in the component 's error_message private property.", async () => {
            mockFetchError(tlp.get, {
                error_json: {
                    error: {
                        code: 403,
                        message: "Forbidden"
                    }
                }
            });

            await actions.initWidgetWithReport(context);
            expect(context.commit).toHaveBeenCalledWith("resetErrorMessage");
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "403 Forbidden");
        });
    });
});
