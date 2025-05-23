/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import NewFolderModal from "./NewFolderModal.vue";
import * as tlp_modal from "@tuleap/tlp-modal";
import emitter from "../../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import { nextTick } from "vue";

vi.mock("tlp", () => {
    return { datePicker: vi.fn() };
});

describe("NewFolderModal", () => {
    let factory;

    const load_projects_ugroups = vi.fn();
    const current_folder = {
        id: 42,
        title: "My current folder",
        obsolescence_date: null,
        properties: [
            {
                short_name: "title",
                name: "title",
                list_value: "My current folder",
                is_multiple_value_allowed: false,
                type: "text",
                is_required: false,
                description: "My current folder",
                is_used: false,
            },
            {
                short_name: "custom property",
                name: "custom",
                value: "value",
                is_multiple_value_allowed: false,
                type: "text",
                is_required: false,
                description: "Some Custom",
                is_used: false,
            },
            {
                short_name: "status",
                list_value: [
                    {
                        id: 103,
                    },
                ],
            },
        ],
        permissions_for_groups: {
            can_read: [],
            can_write: [],
            can_manage: [],
        },
    };

    beforeEach(() => {
        factory = (item = {}) => {
            return shallowMount(NewFolderModal, {
                data() {
                    return {
                        item,
                    };
                },
                global: {
                    ...getGlobalTestOptions({
                        modules: {
                            permissions: {
                                state: {
                                    project_ugroups: null,
                                },
                                namespaced: true,
                                actions: {
                                    loadProjectUserGroupsIfNeeded: load_projects_ugroups,
                                },
                            },
                            configuration: {
                                state: {
                                    is_status_property_used: true,
                                    has_loaded_properties: true,
                                },
                                namespaced: true,
                            },
                        },
                        state: {
                            current_folder,
                        },
                    }),
                },
            });
        };

        vi.spyOn(tlp_modal, "createModal").mockReturnValue({
            addEventListener: () => {},
            show: () => {},
            hide: () => {},
        });
    });

    it("Updates custom property", () => {
        const item = {
            id: 7,
            title: "Color folder",
            type: "folder",
            description: "A custom description",
            properties: [
                {
                    short_name: "field_9",
                    name: "string 1",
                    value: "",
                    is_multiple_value_allowed: false,
                    type: "string",
                    is_required: true,
                    description: "",
                    is_used: false,
                },
            ],
        };

        const wrapper = factory(item);
        expect(wrapper.vm.item.properties[0].value).toBe("");
        emitter.emit("update-custom-property", {
            property_short_name: "field_9",
            value: "wololo some words",
        });
        expect(wrapper.vm.item.properties[0].value).toBe("wololo some words");
    });

    it("Does not load project properties, when they have already been loaded", async () => {
        factory();

        emitter.emit("show-new-document-modal", {
            detail: { parent: current_folder },
        });
        await nextTick();

        expect(load_projects_ugroups).not.toHaveBeenCalled();
    });

    it("inherit default values from parent properties", () => {
        const folder_to_create = {
            properties: [
                {
                    short_name: "custom property",
                    name: "custom",
                    value: "value",
                    is_multiple_value_allowed: false,
                    type: "text",
                    is_required: false,
                    list_value: null,
                    allowed_list_values: null,
                    description: "Some Custom",
                    is_used: false,
                },
            ],
        };

        const wrapper = factory();

        emitter.emit("show-new-folder-modal", {
            detail: { parent: current_folder },
        });
        expect(wrapper.vm.item.properties).toStrictEqual(folder_to_create.properties);
        expect(wrapper.vm.item.status).toBe("rejected");
    });

    it("Updates status", () => {
        const wrapper = factory();

        emitter.emit("update-status-property", "draft");
        expect(wrapper.vm.item.status).toBe("draft");
    });

    it("Updates title", () => {
        const wrapper = factory();

        emitter.emit("update-title-property", "A folder");
        expect(wrapper.vm.item.title).toBe("A folder");
    });

    it("Updates description", () => {
        const item = {
            id: 7,
            type: "folder",
            description: "A custom description",
            properties: [
                {
                    short_name: "status",
                    list_value: [
                        {
                            id: 103,
                        },
                    ],
                },
            ],
        };

        const wrapper = factory({ item });

        emitter.emit("update-description-property", "A description");
        expect(wrapper.vm.item.description).toBe("A description");
    });
});
