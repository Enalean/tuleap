/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import * as rest_querier from "../../api/rest-querier.js";
import * as item_animator from "../../helpers/item-animator.js";
import {
    showTransitionConfigurationModal,
    loadTransition,
    loadUserGroupsIfNotCached,
    loadPostActions,
    saveTransitionRules,
} from "./transition-actions.js";
import { create, createList } from "../../support/factories.js";

describe("Transition modal actions", () => {
    let getUserGroups, getPostActions, context;

    beforeEach(() => {
        getUserGroups = jest.spyOn(rest_querier, "getUserGroups");

        getPostActions = jest.spyOn(rest_querier, "getPostActions");

        context = {
            state: {},
            commit: jest.fn(),
            dispatch: jest.fn(),
        };
    });

    describe("showTransitionConfigurationModal()", () => {
        let transition;
        beforeEach(() => {
            transition = create("transition", { id: 1 });
        });

        it("will first show the modal, load the transition, load the cached user groups, load the actions and clear the loading flag", async () => {
            await showTransitionConfigurationModal(context, transition);

            expect(context.commit).toHaveBeenCalledWith("showModal");
            expect(context.dispatch).toHaveBeenCalledWith("loadTransition", 1);
            expect(context.dispatch).toHaveBeenCalledWith("loadUserGroupsIfNotCached");
            expect(context.dispatch).toHaveBeenCalledWith("loadPostActions", 1);
            expect(context.commit).toHaveBeenCalledWith("endLoadingModal");
        });

        it("When there's a REST error, it will set a flag for the modal to show the error", async () => {
            mockFetchError(context.dispatch, {
                error_json: {
                    error: {
                        i18n_error_message: "You are not allowed to see that",
                    },
                },
            });

            await showTransitionConfigurationModal(context, transition);

            expect(context.commit).toHaveBeenCalledWith("showModal");
            expect(context.commit).toHaveBeenCalledWith(
                "failModalOperation",
                "You are not allowed to see that",
            );
            expect(context.commit).toHaveBeenCalledWith("endLoadingModal");
        });
    });

    describe("loadTransition()", () => {
        const transition = create("transition");

        beforeEach(async () => {
            jest.spyOn(rest_querier, "getTransition").mockReturnValue(transition);

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
                ...context,
                state: {
                    user_groups: null,
                },
                rootGetters: {
                    current_project_id: 205,
                },
            };
            user_groups = createList("user_group", 2);
        });

        it("When the user groups were already in the state, it won't do anything", async () => {
            context.state.user_groups = user_groups;

            await loadUserGroupsIfNotCached(context);

            expect(getUserGroups).not.toHaveBeenCalled();
        });

        it("sets the user groups returned by the API in the state", async () => {
            getUserGroups.mockReturnValue(user_groups);

            await loadUserGroupsIfNotCached(context);

            expect(getUserGroups).toHaveBeenCalledWith(205);
            expect(context.commit).toHaveBeenCalledWith("initUserGroups", user_groups);
        });
    });

    describe("loadPostActions()", () => {
        let post_actions = createList("post_action", 2);

        beforeEach(async () => {
            getPostActions.mockReturnValue(post_actions);
            await loadPostActions(context);
        });

        it("will query the API and set the user groups in the state", () => {
            expect(context.commit).toHaveBeenCalledWith("savePostActions", post_actions);
        });
    });

    describe("saveTransitionRules()", () => {
        let patchTransition, putPostActions, animateUpdated;
        const current_transition = create("transition", { id: 9 });
        const post_actions = createList("post_action", 2, "presented");

        beforeEach(async () => {
            animateUpdated = jest.spyOn(item_animator, "animateUpdated");
            patchTransition = jest
                .spyOn(rest_querier, "patchTransition")
                .mockReturnValue(Promise.resolve());
            putPostActions = jest
                .spyOn(rest_querier, "putPostActions")
                .mockReturnValue(Promise.resolve());

            context = {
                ...context,
                state: {
                    current_transition: current_transition,
                },
                getters: {
                    post_actions,
                },
            };
            await saveTransitionRules(context);
        });
        it("animates the transition", () => {
            expect(animateUpdated).toHaveBeenCalled();
        });

        it("patches transition", () => {
            expect(patchTransition).toHaveBeenCalledWith(current_transition);
        });
        it("patches actions", () => {
            expect(putPostActions).toHaveBeenCalledWith(9, post_actions);
        });
    });
});
