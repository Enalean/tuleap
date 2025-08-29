/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";

import NewItemModal from "./NewItemModal.vue";
import emitter from "../../../../helpers/emitter";
import * as tlp_modal from "@tuleap/tlp-modal";
import { TYPE_FILE, TYPE_FOLDER } from "../../../../constants";
import * as get_office_file from "../../../../helpers/office/get-empty-office-file";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import { IS_STATUS_PROPERTY_USED, PROJECT_ID } from "../../../../configuration-keys";

vi.useFakeTimers();

vi.mock("tlp", () => {
    return { datePicker: vi.fn() };
});

describe("NewItemModal", () => {
    let factory: () => VueWrapper<NewItemModal>;
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
        factory = (): VueWrapper<NewItemModal> => {
            return shallowMount(NewItemModal, {
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
                                    is_obsolescence_date_property_used: true,
                                },
                                namespaced: true,
                            },
                        },
                        state: {
                            current_folder,
                        },
                    }),
                    provide: {
                        [PROJECT_ID.valueOf()]: 102,
                        [IS_STATUS_PROPERTY_USED.valueOf()]: true,
                    },
                },
            });
        };

        vi.spyOn(tlp_modal, "createModal").mockReturnValue({
            addEventListener: () => {},
            removeEventListener: () => {},
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

        const wrapper = factory();
        emitter.emit("createItem", { item, type: "folder" });
        expect(wrapper.vm.item.properties[0].value).toBe("");
        emitter.emit("update-custom-property", {
            property_short_name: "field_9",
            value: "wololo some words",
        });
        expect(wrapper.vm.item.properties[0].value).toBe("wololo some words");
        wrapper.unmount();
    });

    it("inherit default values from parent properties", async () => {
        const item_to_create = {
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
        emitter.emit("createItem", {
            item: current_folder,
        });
        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.vm.item.properties).toStrictEqual(item_to_create.properties);
        wrapper.unmount();
    });

    it("Updates status", () => {
        const wrapper = factory();
        emitter.emit("update-status-property", "approved");
        expect(wrapper.vm.item.status).toBe("approved");
        emitter.emit("update-status-property", "draft");
        expect(wrapper.vm.item.status).toBe("draft");
        wrapper.unmount();
    });

    it("Updates title", () => {
        const wrapper = factory();
        emitter.emit("update-title-property", "Color folder");
        expect(wrapper.vm.item.title).toBe("Color folder");
        emitter.emit("update-title-property", "A folder");
        expect(wrapper.vm.item.title).toBe("A folder");
        wrapper.unmount();
    });

    it("should update the filename accordingly to title when created from empty", async function () {
        vi.spyOn(get_office_file, "getEmptyOfficeFileFromMimeType").mockResolvedValue({
            badge_class: "document-document-badge",
            extension: "docx",
            file: new File([], "document.docx", { type: "application/docx" }),
        });

        const wrapper = factory();
        const parent = {
            id: 123,
            type: TYPE_FOLDER,
            permissions_for_groups: { apply_permissions_on_children: true },
            properties: [],
        };
        emitter.emit("createItem", {
            item: parent,
            type: TYPE_FILE,
            from_alternative: {
                mime_type: "application/word",
                title: "Document",
            },
        });

        await vi.runOnlyPendingTimersAsync();

        emitter.emit("update-title-property", "Specs V1");
        expect(wrapper.vm.item.file_properties.file.name).toBe("Specs V1.docx");
        emitter.emit("update-title-property", "Specs V1.final");
        expect(wrapper.vm.item.file_properties.file.name).toBe("Specs V1.final.docx");
        wrapper.unmount();
    });

    it("Updates description", () => {
        const wrapper = factory();
        emitter.emit("update-description-property", "A custom description");
        expect(wrapper.vm.item.description).toBe("A custom description");
        emitter.emit("update-description-property", "A description");
        expect(wrapper.vm.item.description).toBe("A description");
        wrapper.unmount();
    });
});
