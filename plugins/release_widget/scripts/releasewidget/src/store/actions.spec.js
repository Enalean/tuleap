/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
import { tlp, mockFetchError, mockFetchSuccess } from "tlp-mocks";
import { rewire$getNumberOfBacklogItems } from "./actions.js";
import { rewire$getNumberOfUpcomingReleases } from "./actions.js";

describe("Store actions", () => {
    let context;
    beforeEach(() => {
        context = {
            commit: jasmine.createSpy("commit"),
            state: {
                project_id: null,
                nb_backlog_items: 0,
                nb_upcoming_releases: 0,
                pagination_offset: 0,
                pagination_limit: 50
            }
        };
    });

    describe("getNumberOfBacklogItems - rest", () => {
        describe("getNumberOfBacklogItems - rest errors", () => {
            it("Given a rest error, When a json error message is received, Then an exception is caught.", async () => {
                mockFetchError(tlp.get, {
                    error_json: {
                        error: {
                            code: 403,
                            message: "Forbidden"
                        }
                    }
                });

                await expectAsync(actions.getNumberOfBacklogItems(context)).toBeRejected();
            });
        });
        describe("getNumberOfBacklogItems - success", () => {
            it("Given a success response, When total of backlog are received, Then no message error is reveived", async () => {
                context.state.project_id = 102;

                mockFetchSuccess(tlp.get, {
                    headers: {
                        get: header_name => {
                            const headers = {
                                "X-PAGINATION-SIZE": 1
                            };

                            return headers[header_name];
                        }
                    }
                });

                await actions.getNumberOfBacklogItems(context);
                expect(context.commit).toHaveBeenCalledWith("resetErrorMessage");
                expect(context.commit).toHaveBeenCalledWith("setNbBacklogItem", 1);
            });
        });
    });

    describe("getNumberOfUpcomingReleases - rest", () => {
        describe("getNumberOfUpcomingReleases - rest errors", () => {
            it("Given a rest error, When a json error message is received, Then an exception is caught.", async () => {
                mockFetchError(tlp.get, {
                    error_json: {
                        error: {
                            code: 403,
                            message: "Forbidden"
                        }
                    }
                });

                await expectAsync(actions.getNumberOfUpcomingReleases(context)).toBeRejected();
            });
        });
        describe("getNumberOfUpcomingReleases - success", () => {
            it("Given a success response, When total of backlog are received, Then no message error is reveived", async () => {
                context.state.project_id = 102;

                mockFetchSuccess(tlp.get, {
                    headers: {
                        get: header_name => {
                            const headers = {
                                "X-PAGINATION-SIZE": 1
                            };

                            return headers[header_name];
                        }
                    }
                });

                await actions.getNumberOfUpcomingReleases(context);
                expect(context.commit).toHaveBeenCalledWith("resetErrorMessage");
                expect(context.commit).toHaveBeenCalledWith("setNbUpcomingReleases", 1);
            });
        });
    });

    describe("getTotalsBacklogAndUpcomingReleases - rest", () => {
        describe("getTotalsBacklogAndUpcomingReleases - rest errors", () => {
            it("Given a rest error, When a json error message is received, Then the error message is set.", async () => {
                mockFetchError(tlp.get, {
                    error_json: {
                        error: {
                            code: 403,
                            message: "Forbidden"
                        }
                    }
                });

                await actions.getTotalsBacklogAndUpcomingReleases(context);
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", true);
                expect(context.commit).toHaveBeenCalledWith("resetErrorMessage");
                expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "403 Forbidden");
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
            });
        });
        describe("getTotalsBacklogAndUpcomingReleases - success", () => {
            it("Given a success response, When totals of backlog and upcoming releases are received, Then no message error is reveived", async () => {
                const getNumberOfBacklogItems = jasmine.createSpy("getNumberOfBacklogItems");
                rewire$getNumberOfBacklogItems(getNumberOfBacklogItems);

                const getNumberOfUpcomingReleases = jasmine.createSpy(
                    "getNumberOfUpcomingReleases"
                );
                rewire$getNumberOfUpcomingReleases(getNumberOfUpcomingReleases);

                context.state.project_id = 102;

                mockFetchSuccess(tlp.get, {
                    headers: {
                        get: header_name => {
                            const headers = {
                                "X-PAGINATION-SIZE": 1
                            };

                            return headers[header_name];
                        }
                    }
                });

                await actions.getTotalsBacklogAndUpcomingReleases(context);
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", true);
                expect(getNumberOfBacklogItems).toHaveBeenCalled();
                expect(getNumberOfUpcomingReleases).toHaveBeenCalled();
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
            });
        });
    });
});
