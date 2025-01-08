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
 *
 */

import { shallowMount } from "@vue/test-utils";

import NotEmptyFieldsSelect from "./NotEmptyFieldsSelect.vue";
import { create } from "../../support/factories.js";
import * as list_picker from "@tuleap/list-picker";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests.js";

describe("NotEmptyFieldsSelect", () => {
    function getWrapper(state, fields) {
        return shallowMount(NotEmptyFieldsSelect, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        transitionModal: {
                            namespaced: true,
                            state,
                        },
                    },
                    state: {
                        current_tracker: {
                            fields,
                        },
                    },
                    getters: {
                        current_workflow_transitions: () => [],
                        is_transition_from_new_artifact: () => false,
                    },
                }),
            },
        });
    }

    beforeEach(() => {
        jest.spyOn(list_picker, "createListPicker").mockImplementation();
    });

    describe("writable_fields", () => {
        describe("when no current tracker", () => {
            it("returns empty array", () => {
                const wrapper = getWrapper(null, []);

                const all_options = wrapper
                    .get("[data-test=not-empty-field-select]")
                    .findAll("option");
                expect(all_options).toHaveLength(0);
            });
        });

        describe("with a current tracker", () => {
            const valid_field = create("field", { type: "valid", label: "valid" });
            const invalid_field = create("field", { type: "burndown", label: "invalid" });
            const current_tracker_fields = {
                fields: [invalid_field, valid_field],
            };

            it("returns valid fields", () => {
                const wrapper = getWrapper({ current_tracker_fields }, [valid_field]);
                const all_options = wrapper
                    .get("[data-test=not-empty-field-select]")
                    .findAll("option");
                expect(all_options).toHaveLength(1);
                expect(all_options.at(0)).toMatchInlineSnapshot(`<option value="0">valid</option>`);
            });
            it("does not return invalid fields", () => {
                const wrapper = getWrapper({ current_tracker_fields }, [valid_field]);
                const all_options = wrapper
                    .get("[data-test=not-empty-field-select]")
                    .findAll("option");
                expect(all_options).not.toContain(invalid_field);
            });

            describe("which fields are not sorted", () => {
                it("returns fields sorted by natural order", () => {
                    const fields = [
                        create("field", { type: "valid", label: "second" }),
                        create("field", { type: "valid", label: "First" }),
                        create("field", { type: "valid", label: "Third" }),
                    ];
                    const current_tracker_fields = {
                        fields: fields,
                    };
                    const wrapper = getWrapper({ current_tracker_fields }, fields);
                    const all_options = wrapper
                        .get("[data-test=not-empty-field-select]")
                        .findAll("option");
                    expect(all_options.at(0)).toMatchInlineSnapshot(
                        `<option value="3">First</option>`,
                    );
                    expect(all_options.at(1)).toMatchInlineSnapshot(
                        `<option value="2">second</option>`,
                    );
                    expect(all_options.at(2)).toMatchInlineSnapshot(
                        `<option value="4">Third</option>`,
                    );
                });
            });
        });
    });

    describe("not_empty_field_ids", () => {
        describe("when no current transition", () => {
            it("returns empty array", () => {
                const wrapper = getWrapper(null, []);
                const all_options = wrapper
                    .get("[data-test=not-empty-field-select]")
                    .findAll("option");
                expect(all_options).toHaveLength(0);
            });
        });

        describe("with a current transition", () => {
            const not_empty_field_ids = [1, 2];
            it("returns transition empty field ids", () => {
                const state = {
                    current_transition: {
                        not_empty_field_ids,
                        authorized_user_group_ids: [],
                    },
                };
                const wrapper = getWrapper(state, []);
                const all_options = wrapper
                    .get("[data-test=not-empty-field-select]")
                    .findAll("option");
                expect(all_options).toHaveLength(0);
            });
        });
    });

    describe(`when the modal is saving`, () => {
        let wrapper;
        beforeEach(() => {
            const state = {
                is_modal_save_running: true,
            };
            wrapper = getWrapper(state, []);
        });

        it(`will disable the "Not empty fields" select`, () => {
            const not_empty_field_select = wrapper.get("[data-test=not-empty-field-select]");
            expect(not_empty_field_select.attributes("disabled")).toBe("");
        });
    });
});
