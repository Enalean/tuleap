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
import { rewire$getUserGroups, rewire$getTransition, restore } from "../../api/rest-querier.js";
import {
    showTransitionConfigurationModal,
    loadTransition,
    loadUserGroupsIfNotCached
} from "./transition-actions.js";
import { create, createList } from "../../support/factories.js";

describe("Transition modal actions", () => {
    let getUserGroups, context;

    beforeEach(() => {
        getUserGroups = jasmine.createSpy("getUserGroups");
        rewire$getUserGroups(getUserGroups);
    });

    afterEach(restore);

    describe("showTransitionConfigurationModal()", () => {
        let transition;
        beforeEach(() => {
            context = {
                commit: jasmine.createSpy("commit"),
                dispatch: jasmine.createSpy("dispatch")
            };
            transition = create("transition", { id: 1 });
        });

        it("will first show the modal, load the transition, load the cached user groups and clear the loading flag", async () => {
            await showTransitionConfigurationModal(context, transition);

            expect(context.commit).toHaveBeenCalledWith("showModal");
            expect(context.dispatch).toHaveBeenCalledWith("loadTransition", 1);
            expect(context.dispatch).toHaveBeenCalledWith("loadUserGroupsIfNotCached");
            expect(context.commit).toHaveBeenCalledWith("endLoadingModal");
        });

        it("When there's a REST error, it will set a flag for the modal to show the error", async () => {
            mockFetchError(context.dispatch, {
                error_json: {
                    error: {
                        i18n_error_message: "You are not allowed to see that"
                    }
                }
            });

            await showTransitionConfigurationModal(context, transition);

            expect(context.commit).toHaveBeenCalledWith("showModal");
            expect(context.commit).toHaveBeenCalledWith(
                "failModalOperation",
                "You are not allowed to see that"
            );
            expect(context.commit).toHaveBeenCalledWith("endLoadingModal");
        });
    });

    describe("loadTransition()", () => {
        const transition = create("transition");

        beforeEach(async () => {
            const getTransition = jasmine.createSpy("getTransition");
            rewire$getTransition(getTransition);
            getTransition.and.returnValue(transition);

            context = {
                commit: jasmine.createSpy("commit")
            };

            await loadTransition(context, 1);
        });

        it("saves transition returned by the API", () => {
            expect(context.commit).toHaveBeenCalledWith("saveCurrentTransition", transition);
        });
    });

    describe("loadUserGroupsIfNotCached()", () => {
        let user_groups;

        beforeEach(() => {
            context = {
                state: {
                    user_groups: null
                },
                commit: jasmine.createSpy("commit"),
                rootGetters: {
                    current_project_id: 205
                }
            };
            user_groups = createList("user_group", 2);
        });

        it("When the user groups were already in the state, it won't do anything", async () => {
            context.state.user_groups = user_groups;

            await loadUserGroupsIfNotCached(context);

            expect(getUserGroups).not.toHaveBeenCalled();
        });

        it("will query the API and set the user groups in the state", async () => {
            getUserGroups.and.returnValue(user_groups);

            await loadUserGroupsIfNotCached(context);

            expect(getUserGroups).toHaveBeenCalledWith(205);
            expect(context.commit).toHaveBeenCalledWith("initUserGroups", user_groups);
        });
    });
});
