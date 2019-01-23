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

import * as mutations from "./transition-mutations.js";
import { create } from "../../support/factories.js";

describe("Transition mutations", () => {
    let state;

    describe("showModal()", () => {
        beforeEach(() => {
            state = {
                is_modal_shown: false,
                is_loading_modal: false,
                current_transition: null
            };

            mutations.showModal(state);
        });

        it("will set the loading flag", () => expect(state.is_loading_modal).toBe(true));
        it("will reset the current transition", () => expect(state.current_transition).toBeNull());
        it("will set the modal shown flag", () => expect(state.is_modal_shown).toBe(true));
    });

    describe("failModalOperation()", () => {
        let message = "Bad Request";

        beforeEach(() => {
            state = {
                is_modal_operation_failed: false,
                modal_operation_failure_message: null
            };

            mutations.failModalOperation(state, message);
        });

        it("will set the modal's failed operation flag", () =>
            expect(state.is_modal_operation_failed).toBe(true));
        it("will set the modal's error message", () =>
            expect(state.modal_operation_failure_message).toBe(message));
    });

    describe("updateIsCommentRequired()", () => {
        describe("when no current transition", () => {
            beforeEach(() => {
                state = { current_transition: null };
                mutations.updateIsCommentRequired(state, true);
            });
            it("does nothing", () => expect(state.current_transition).toBeNull());
        });
        describe("with a current transition", () => {
            beforeEach(() => {
                state = {
                    current_transition: {
                        is_comment_required: false
                    }
                };
                mutations.updateIsCommentRequired(state, true);
            });
            it("update current transition comment requirement", () =>
                expect(state.current_transition.is_comment_required).toBe(true));
        });
    });

    describe("updateNotEmptyFieldIds()", () => {
        describe("when no current transition", () => {
            beforeEach(() => {
                state = { current_transition: null };
                mutations.updateNotEmptyFieldIds(state, true);
            });
            it("does nothing", () => expect(state.current_transition).toBeNull());
        });
        describe("with a current transition", () => {
            beforeEach(() => {
                state = {
                    current_transition: {
                        not_empty_field_ids: []
                    }
                };
                mutations.updateNotEmptyFieldIds(state, [1, 2]);
            });
            it("update current transition not empty field ids", () =>
                expect(state.current_transition.not_empty_field_ids).toEqual([1, 2]));
        });
    });

    describe("updateAuthorizedUserGroupIds()", () => {
        describe("when no current transition", () => {
            beforeEach(() => {
                state = { current_transition: null };
                mutations.updateAuthorizedUserGroupIds(state, true);
            });
            it("does nothing", () => expect(state.current_transition).toBeNull());
        });
        describe("with a current transition", () => {
            beforeEach(() => {
                state = {
                    current_transition: {
                        authorized_user_group_ids: []
                    }
                };
                mutations.updateAuthorizedUserGroupIds(state, [1, 2]);
            });
            it("update current transition authorized group ids", () =>
                expect(state.current_transition.authorized_user_group_ids).toEqual([1, 2]));
        });
    });

    describe("savePostActions", () => {
        const old_action = create("post_action", "presented");
        const new_action = create("post_action", {
            id: 2,
            type: "run_job"
        });

        beforeEach(() => {
            state = {
                post_actions_by_unique_id: {
                    "old-post-action": old_action
                }
            };
            mutations.savePostActions(state, [new_action]);
        });
        it("removes old actions", () => {
            expect(state.post_actions_by_unique_id).not.toContain(old_action);
        });
        it("adds given actions, presented with unique_id, and referenced by unique_id", () => {
            expect(state.post_actions_by_unique_id.run_job_2).toEqual({
                ...new_action,
                unique_id: "run_job_2"
            });
        });

        describe("when given post actions are field set", () => {
            const new_set_field_action = create("post_action", {
                id: 2,
                type: "set_field_value",
                field_type: "date"
            });

            beforeEach(() => {
                mutations.savePostActions(state, [new_set_field_action]);
            });

            it("adds given post actions, presented with unique_id, and referenced by unique_id", () => {
                expect(state.post_actions_by_unique_id.set_field_value_date_2).toEqual({
                    ...new_set_field_action,
                    unique_id: "set_field_value_date_2"
                });
            });
        });
    });

    describe("updatePostAction", () => {
        const old_action = create("post_action", "presented", { unique_id: "post_action_id" });
        const new_action = create("post_action", "presented", { unique_id: "post_action_id" });

        beforeEach(() => {
            state = {
                post_actions_by_unique_id: { "old-post-action": old_action }
            };
            mutations.updatePostAction(state, new_action);
        });

        it("updates state with given post action", () => {
            expect(state.post_actions_by_unique_id.post_action_id).toBe(new_action);
        });
    });
});
