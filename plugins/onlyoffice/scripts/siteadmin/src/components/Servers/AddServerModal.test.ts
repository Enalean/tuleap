/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";

vi.mock("@tuleap/autocomplete-for-select2", () => {
    return {
        autocomplete_projects_for_select2(): void {
            //do nothing
        },
    };
});

import { shallowMount } from "@vue/test-utils";
import AddServerModal from "./AddServerModal.vue";
import { createGettext } from "vue3-gettext";
import { CONFIG } from "../../injection-keys";

describe("AddServerModal", () => {
    describe("warns about possible loss of data", () => {
        it("should display a warning if there is one server unrestricted", () => {
            const wrapper = shallowMount(AddServerModal, {
                global: {
                    plugins: [createGettext({ silent: true })],
                    provide: {
                        [CONFIG.valueOf()]: { servers: [{ id: 1, is_project_restricted: false }] },
                    },
                },
            });

            expect(wrapper.find("[data-test=warning]").exists()).toBe(true);
        });

        it("should not display a warning if there is no existing server", () => {
            const wrapper = shallowMount(AddServerModal, {
                global: {
                    plugins: [createGettext({ silent: true })],
                    provide: {
                        [CONFIG.valueOf()]: { servers: [] },
                    },
                },
            });

            expect(wrapper.find("[data-test=warning]").exists()).toBe(false);
        });

        it("should not display a warning if the existing server is already restricted", () => {
            const wrapper = shallowMount(AddServerModal, {
                global: {
                    plugins: [createGettext({ silent: true })],
                    provide: {
                        [CONFIG.valueOf()]: { servers: [{ id: 1, is_project_restricted: true }] },
                    },
                },
            });

            expect(wrapper.find("[data-test=warning]").exists()).toBe(false);
        });

        it("should not display a warning if there are more than one existing server", () => {
            const wrapper = shallowMount(AddServerModal, {
                global: {
                    plugins: [createGettext({ silent: true })],
                    provide: {
                        [CONFIG.valueOf()]: {
                            servers: [
                                { id: 1, is_project_restricted: true },
                                { id: 2, is_project_restricted: true },
                            ],
                        },
                    },
                },
            });

            expect(wrapper.find("[data-test=warning]").exists()).toBe(false);
        });
    });
});
