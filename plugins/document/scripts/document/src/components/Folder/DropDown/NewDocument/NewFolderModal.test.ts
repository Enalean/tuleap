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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import NewFolderModal from "./NewFolderModal.vue";
import * as tlp_modal from "@tuleap/tlp-modal";
import emitter from "../../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import { nextTick, ref } from "vue";
import { IS_STATUS_PROPERTY_USED, PROJECT } from "../../../../configuration-keys";
import { ProjectBuilder } from "../../../../../tests/builders/ProjectBuilder";
import { PROJECT_USER_GROUPS } from "../../../../injection-keys";
import * as user_group_helpers from "../../../../helpers/permissions/ugroups";

describe("NewFolderModal", () => {
    const load_projects_ugroups = vi.spyOn(user_group_helpers, "loadProjectUserGroups");
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

    function getWrapper(): VueWrapper<NewFolderModal> {
        return shallowMount(NewFolderModal, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        current_folder,
                    },
                }),
                provide: {
                    [PROJECT.valueOf()]: new ProjectBuilder(101).build(),
                    [IS_STATUS_PROPERTY_USED.valueOf()]: true,
                    [PROJECT_USER_GROUPS.valueOf()]: ref(null),
                },
            },
        });
    }

    beforeEach(() => {
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

        const wrapper = getWrapper();
        wrapper.vm.item = item;
        expect(wrapper.vm.item.properties[0].value).toBe("");
        emitter.emit("update-custom-property", {
            property_short_name: "field_9",
            value: "wololo some words",
        });
        expect(wrapper.vm.item.properties[0].value).toBe("wololo some words");
    });

    it("Does not load project properties, when they have already been loaded", async () => {
        getWrapper();

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

        const wrapper = getWrapper();

        emitter.emit("show-new-folder-modal", {
            detail: { parent: current_folder },
        });
        expect(wrapper.vm.item.properties).toStrictEqual(folder_to_create.properties);
        expect(wrapper.vm.item.status).toBe("rejected");
    });

    it("Updates status", () => {
        const wrapper = getWrapper();

        emitter.emit("update-status-property", "draft");
        expect(wrapper.vm.item.status).toBe("draft");
    });

    it("Updates title", () => {
        const wrapper = getWrapper();

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

        const wrapper = getWrapper();
        wrapper.vm.item = item;

        emitter.emit("update-description-property", "A description");
        expect(wrapper.vm.item.description).toBe("A description");
    });
});
