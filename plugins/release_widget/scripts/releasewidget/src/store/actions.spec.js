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
import { rewire$getCurrentMilestones } from "./actions.js";
import { rewire$handleErrorMessage } from "./actions.js";

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
                pagination_limit: 50,
                current_milestones: []
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
            it("Given a success response, When total of backlog are received, Then no message error is received", async () => {
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
            it("Given a success response, When total of backlog are received, Then no message error is received", async () => {
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

    describe("getCurrentMilestones - rest", () => {
        describe("getCurrentMilestones - rest errors", () => {
            it("Given a rest error, When a json error message is received, Then an exception is thrown.", async () => {
                mockFetchError(tlp.get, {
                    error_json: {
                        error: {
                            code: 403,
                            message: "Forbidden"
                        }
                    }
                });

                await expectAsync(actions.getCurrentMilestones(context)).toBeRejected();
            });
        });

        describe("getCurrentMilestones - success", () => {
            it("Given a success response, When all current milestones are received, Then no message error is received", async () => {
                let milestones = [
                    [
                        {
                            start_date: {},
                            end_date: {},
                            project: {}
                        }
                    ]
                ];

                mockFetchSuccess(tlp.get, {
                    headers: {
                        get: header_name => {
                            const headers = {
                                "X-PAGINATION-SIZE": 1
                            };

                            return headers[header_name];
                        }
                    },
                    return_json: milestones
                });

                await actions.getCurrentMilestones(context);

                expect(context.commit).toHaveBeenCalledWith("resetErrorMessage");
                expect(context.commit).toHaveBeenCalledWith("setCurrentMilestones", milestones);
            });
        });
    });

    describe("getMilestones - rest", () => {
        describe("getMilestones - rest errors", () => {
            it("Given a rest error, When a json error message is received, Then the error message is set.", async () => {
                mockFetchError(tlp.get, {
                    error_json: {
                        error: {
                            code: 403,
                            message: "Forbidden"
                        }
                    }
                });

                await actions.getMilestones(context);
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", true);
                expect(context.commit).toHaveBeenCalledWith("resetErrorMessage");
                expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "403 Forbidden");
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
            });
        });
        describe("getMilestones - success", () => {
            it("Given a success response, When totals of backlog and upcoming releases are received, Then no message error is received", async () => {
                const getNumberOfBacklogItems = jasmine.createSpy("getNumberOfBacklogItems");
                rewire$getNumberOfBacklogItems(getNumberOfBacklogItems);

                const getNumberOfUpcomingReleases = jasmine.createSpy(
                    "getNumberOfUpcomingReleases"
                );
                rewire$getNumberOfUpcomingReleases(getNumberOfUpcomingReleases);

                const getCurrentMilestones = jasmine.createSpy("getCurrentMilestones");
                rewire$getCurrentMilestones(getCurrentMilestones);

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

                await actions.getMilestones(context);
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", true);
                expect(getNumberOfBacklogItems).toHaveBeenCalled();
                expect(getNumberOfUpcomingReleases).toHaveBeenCalled();
                expect(getCurrentMilestones).toHaveBeenCalled();
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
            });
        });
    });

    describe("handleErrorMessage - error", () => {
        it("Given an error, When it can't parse the error, Then the error message is empty.", async () => {
            const error_json = "[a,b, c, d, e, f,]";
            await actions.handleErrorMessage(context, error_json);

            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "");
        });
    });

    describe("getNumberOfSprints - rest", () => {
        describe("getNumberOfSprints - rest errors", () => {
            it("Given a rest error, When a json error message is received, Then an exception is thrown.", async () => {
                const handleErrorMessage = jasmine.createSpy("handleErrorMessage");
                rewire$handleErrorMessage(handleErrorMessage);

                mockFetchError(tlp.get, {
                    error_json: {
                        error: {
                            code: 403,
                            message: "Forbidden"
                        }
                    }
                });

                await actions.getNumberOfSprints(context, 102);

                expect(handleErrorMessage).toHaveBeenCalled();
            });
        });
        describe("getNumberOfSprints - success", () => {
            it("Given a success response, When totals of sprints is received, Then no message error is received", async () => {
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

                await expectAsync(actions.getNumberOfSprints(context, 102)).toBeResolved();
            });
        });
    });
});
