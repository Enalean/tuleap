/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import FileVersionChangelogModal from "./FileVersionChangelogModal.vue";
import ItemUpdateProperties from "./PropertiesForUpdate/ItemUpdateProperties.vue";
import * as tlp_modal from "@tuleap/tlp-modal";
import emitter from "../../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import { nextTick } from "vue";
import type { Modal } from "@tuleap/tlp-modal";

describe("FileVersionChangelogModal", () => {
    const create_file_version = vi.fn();

    function getWrapper(): VueWrapper<FileVersionChangelogModal> {
        return shallowMount(FileVersionChangelogModal, {
            props: {
                updated_file: { id: 12, title: "How to.pdf", properties: [] },
                dropped_file: new File([], "How to (updated).pdf"),
            },
            global: {
                ...getGlobalTestOptions({
                    actions: {
                        createNewFileVersionFromModal: create_file_version,
                    },
                    modules: {
                        error: {
                            namespaced: true,
                            mutations: {
                                resetModalError: vi.fn(),
                            },
                        },
                    },
                }),
            },
        });
    }

    beforeEach(() => {
        vi.spyOn(tlp_modal, "createModal").mockReturnValue({
            addEventListener: () => {},
            show: () => {},
            hide: () => {},
            removeBackdrop: () => {},
        } as unknown as Modal);
    });

    it("Create a new version of the document with the provided changelog and titles if any.", async () => {
        const wrapper = getWrapper();
        emitter.emit("update-version-title", "Added the [contributions] section");
        emitter.emit(
            "update-changelog-property",
            "Now, it mentions how to contribute to the project.",
        );

        await wrapper.get("form").trigger("submit");

        expect(create_file_version).toHaveBeenCalledWith(expect.anything(), [
            { id: 12, title: "How to.pdf", properties: [] },
            expect.any(File),
            "Added the [contributions] section",
            "Now, it mentions how to contribute to the project.",
            false,
            null,
        ]);
    });

    it("Create a new version of the document with the new approval table.", async () => {
        const wrapper = getWrapper();
        emitter.emit("update-version-title", "Added the [contributions] section");
        emitter.emit(
            "update-changelog-property",
            "Now, it mentions how to contribute to the project.",
        );

        wrapper
            .findComponent(ItemUpdateProperties)
            .vm.$emit("approval-table-action-change", "reset");
        await wrapper.get("form").trigger("submit");

        expect(create_file_version).toHaveBeenCalledWith(expect.anything(), [
            { id: 12, title: "How to.pdf", properties: [] },
            expect.any(File),
            "Added the [contributions] section",
            "Now, it mentions how to contribute to the project.",
            false,
            "reset",
        ]);
    });

    it("Updates the version title", async () => {
        const wrapper = getWrapper();

        expect(wrapper.vm.version.title).toBe("");
        emitter.emit("update-version-title", "A title");

        await nextTick();

        expect(wrapper.vm.version.title).toBe("A title");
    });

    it("Updates the changelog", async () => {
        const wrapper = getWrapper();

        expect(wrapper.vm.version.changelog).toBe("");
        emitter.emit("update-changelog-property", "A changelog");

        await nextTick();

        expect(wrapper.vm.version.changelog).toBe("A changelog");
    });

    it("Updates the lock", async () => {
        const wrapper = getWrapper();

        await nextTick();

        expect(wrapper.vm.version.is_file_locked).toBeUndefined();
        emitter.emit("update-lock", true);

        await nextTick();

        expect(wrapper.vm.version.is_file_locked).toBe(true);
    });
});
