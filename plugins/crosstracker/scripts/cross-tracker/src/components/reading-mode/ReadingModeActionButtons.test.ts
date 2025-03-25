/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

import { describe, expect, it, beforeEach } from "vitest";
import ExportXLSXButton from "../ExportXLSXButton.vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { EMITTER, IS_EXPORT_ALLOWED, IS_USER_ADMIN } from "../../injection-symbols";
import type { Query } from "../../type";
import ReadingModeActionButtons from "./ReadingModeActionButtons.vue";
import mitt from "mitt";
import type { EditQueryEvent, EmitterProvider, Events } from "../../helpers/emitter-provider";
import { EDIT_QUERY_EVENT } from "../../helpers/emitter-provider";
import DeleteQueryButton from "./DeleteQueryButton.vue";

describe("ReadingModeActionButtons", () => {
    let is_xlsx_export_allowed: boolean,
        current_query: Query,
        emitter: EmitterProvider,
        is_user_admin: boolean,
        dispatched_edit_query_events: EditQueryEvent[];
    function getWrapper(): VueWrapper<InstanceType<typeof ReadingModeActionButtons>> {
        return shallowMount(ReadingModeActionButtons, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [IS_EXPORT_ALLOWED.valueOf()]: is_xlsx_export_allowed,
                    [EMITTER.valueOf()]: emitter,
                    [IS_USER_ADMIN.valueOf()]: is_user_admin,
                },
            },
            props: {
                current_query,
            },
        });
    }

    beforeEach(() => {
        is_user_admin = false;
        dispatched_edit_query_events = [];
        current_query = {
            id: "00000000-03e8-70c0-9e41-6ea7a4e2b78d",
            tql_query: "",
            title: "",
            description: "a great query",
            is_default: false,
        };
        emitter = mitt<Events>();
        emitter.on(EDIT_QUERY_EVENT, (event) => {
            dispatched_edit_query_events.push(event);
        });
    });
    describe(`render XLSX button`, () => {
        it(`does not show the XLSX export button when told not to`, () => {
            is_xlsx_export_allowed = false;
            const wrapper = getWrapper();
            expect(wrapper.findComponent(ExportXLSXButton).exists()).toBe(false);
        });

        it(`shows the XLSX export button otherwise`, () => {
            is_xlsx_export_allowed = true;
            const wrapper = getWrapper();
            expect(wrapper.findComponent(ExportXLSXButton).exists()).toBe(true);
        });
        describe("Render 'Edit' button", () => {
            it(`shows the 'Edit' button if the user is admin`, () => {
                is_user_admin = true;
                const wrapper = getWrapper();
                expect(wrapper.find('[data-test="reading-mode-action-edit-button"]').exists()).toBe(
                    true,
                );
            });
            it(`does not show the 'Edit' button if the user is not admin`, () => {
                const wrapper = getWrapper();
                expect(wrapper.find('[data-test="reading-mode-action-edit-button"]').exists()).toBe(
                    false,
                );
            });
        });
    });
    describe("render delete button", () => {
        it("does not show the delete button if user not admin", () => {
            is_user_admin = false;
            const wrapper = getWrapper();
            expect(wrapper.findComponent(DeleteQueryButton).exists()).toBe(false);
        });

        it("shows the delete button if user is admin", () => {
            is_user_admin = true;
            const wrapper = getWrapper();
            expect(wrapper.findComponent(DeleteQueryButton).exists()).toBe(true);
        });
    });
    describe(`emitted events`, () => {
        it("emit the EDIT_QUERY_EVENT event when the edit query button is clicked", async () => {
            is_user_admin = true;
            const wrapper = getWrapper();
            await wrapper.find('[data-test="reading-mode-action-edit-button"]').trigger("click");

            expect(dispatched_edit_query_events.length).toBe(1);
            expect(dispatched_edit_query_events[0].query_to_edit).toStrictEqual(current_query);
        });
    });
});
