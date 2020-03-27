/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
                current_transition: null,
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
                modal_operation_failure_message: null,
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
                        is_comment_required: false,
                    },
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
                        not_empty_field_ids: [],
                    },
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
                        authorized_user_group_ids: [],
                    },
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
            type: "run_job",
        });

        beforeEach(() => {
            state = {
                post_actions_by_unique_id: {
                    "old-post-action": old_action,
                },
            };
            mutations.savePostActions(state, [new_action]);
        });
        it("removes old actions", () => {
            expect(state.post_actions_by_unique_id).not.toContain(old_action);
        });
        it("adds given actions, presented with unique_id, and referenced by unique_id", () => {
            expect(state.post_actions_by_unique_id.run_job_2).toEqual({
                ...new_action,
                unique_id: "run_job_2",
            });
        });

        describe("when given post actions are field set", () => {
            const new_set_field_action = create("post_action", {
                id: 2,
                type: "set_field_value",
                field_type: "date",
            });

            beforeEach(() => {
                mutations.savePostActions(state, [new_set_field_action]);
            });

            it("adds given post actions, presented with unique_id, and referenced by unique_id", () => {
                expect(state.post_actions_by_unique_id.set_field_value_date_2).toEqual({
                    ...new_set_field_action,
                    unique_id: "set_field_value_date_2",
                });
            });
        });
    });

    describe("updateSetValuePostActionValue", () => {
        const post_action = create("post_action", "presented", { value: 1 });
        const unique_id = post_action.unique_id;

        beforeEach(() => {
            state = { post_actions_by_unique_id: {} };
            state.post_actions_by_unique_id[unique_id] = post_action;
            mutations.updateSetValuePostActionValue(state, { post_action, value: 22 });
        });

        it("updates state's post action with given value", () => {
            expect(state.post_actions_by_unique_id[unique_id].value).toBe(22);
        });
    });

    describe("updateRunJobPostActionJobUrl", () => {
        const post_action = create("post_action", "presented", { job_url: "http://old.test" });
        const unique_id = post_action.unique_id;

        beforeEach(() => {
            state = { post_actions_by_unique_id: {} };
            state.post_actions_by_unique_id[unique_id] = post_action;
            mutations.updateRunJobPostActionJobUrl(state, {
                post_action,
                job_url: "http://new.test",
            });
        });

        it("updates state's post action with given job_url", () => {
            expect(state.post_actions_by_unique_id[unique_id].job_url).toBe("http://new.test");
        });
    });

    describe("addPostAction", () => {
        beforeEach(() => {
            state = {
                new_post_action_unique_id_index: 3,
                post_actions_by_unique_id: {
                    run_job_1: create("post_action", "presented"),
                },
            };
            mutations.addPostAction(state);
        });

        it("Increments post action unique_id index", () => {
            expect(state.new_post_action_unique_id_index).toEqual(4);
        });
        it("Adds new ci build post action", () => {
            let post_action_unique_ids = Object.keys(state.post_actions_by_unique_id);
            expect(post_action_unique_ids.length).toEqual(2);
            expect(state.post_actions_by_unique_id["new_4"]).toEqual({
                unique_id: "new_4",
                type: "run_job",
            });
        });
    });

    describe("deletePostAction", () => {
        const post_action_to_remove = create("post_action", "presented", {
            unique_id: "unique_id_to_remove",
        });

        beforeEach(() => {
            state = {
                post_actions_by_unique_id: {
                    unique_id_to_remove: post_action_to_remove,
                    unique_id_to_keep: create("post_action", "presented"),
                },
            };
            mutations.deletePostAction(state, post_action_to_remove);
        });

        it("Removes post action with given unique_id", () => {
            expect(state.post_actions_by_unique_id["unique_id_to_remove"]).toBeUndefined();
        });
        it("Keeps other post actions", () => {
            expect(state.post_actions_by_unique_id["unique_id_to_keep"]).not.toBeUndefined();
        });
    });

    describe("updateSetValuePostActionField", () => {
        const post_action = create("post_action", "presented", {
            unique_id: "unique_id",
            field_id: 3,
        });
        const new_field = create("field", { field_id: 4 });
        const state = {
            post_actions_by_unique_id: {
                unique_id: post_action,
            },
        };

        const mutatedPostAction = () => {
            mutations.updateSetValuePostActionField(state, { post_action, new_field });
            return state.post_actions_by_unique_id.unique_id;
        };

        it("Updates post action field id", () => {
            expect(mutatedPostAction().field_id).toEqual(4);
        });

        describe("when field type change", () => {
            beforeEach(() => {
                post_action.field_type = "date";
                new_field.type = "int";
            });

            it("Updates post action field type", () => {
                expect(mutatedPostAction().field_type).toEqual("int");
            });
            it("Reset post action id", () => {
                expect(mutatedPostAction().id).toBeNull();
            });

            describe("from int to float", () => {
                beforeEach(() => {
                    post_action.field_type = "int";
                    post_action.value = 23;
                    new_field.type = "float";
                });

                it("does not update post action value", () => {
                    expect(mutatedPostAction().value).toEqual(23);
                });
            });

            describe("from float to int", () => {
                beforeEach(() => {
                    post_action.field_type = "float";
                    post_action.value = 1.23;
                    new_field.type = "int";
                });

                it("converts post action value to int", () => {
                    expect(mutatedPostAction().value).toEqual(1);
                });
            });

            describe("from date", () => {
                beforeEach(() => {
                    post_action.field_type = "date";
                    post_action.value = "current";
                    new_field.type = "int";
                });

                it("resets post action value", () => {
                    expect(mutatedPostAction().value).toBeNull();
                });
            });

            describe("to date", () => {
                beforeEach(() => {
                    post_action.field_type = "int";
                    post_action.value = 1.23;
                    new_field.type = "date";
                });

                it("resets post action value", () => {
                    expect(mutatedPostAction().value).toBeNull();
                });
            });
        });
    });
});
