/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { beforeEach, describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import * as tlp_modal from "@tuleap/tlp-modal";
import CreateNewVersionEmbeddedFileModal from "./CreateNewVersionEmbeddedFileModal.vue";
import emitter from "../../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

vi.useFakeTimers();

describe("CreateNewVersionEmbeddedFileModal", () => {
    const add_event_listener = vi.fn();
    const modal_show = vi.fn();
    const remove_backdrop = vi.fn();
    const load_documents = vi.fn();

    function getWrapper(prop) {
        load_documents.mockImplementation(() => {
            return Promise.resolve({
                id: 12,
                title: "Dacia",
                embedded_file_properties: {
                    content: "VROOM VROOM",
                },
            });
        });
        return shallowMount(CreateNewVersionEmbeddedFileModal, {
            props: {
                ...prop,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        error: {
                            state: {
                                has_modal_error: false,
                            },
                            namespaced: true,
                        },
                    },
                    actions: {
                        loadDocument: load_documents,
                    },
                }),
            },
        });
    }

    beforeEach(() => {
        vi.spyOn(tlp_modal, "createModal").mockImplementation(() => {
            return {
                addEventListener: add_event_listener,
                show: modal_show,
                removeBackdrop: remove_backdrop,
            };
        });
    });

    it("Updates the version title", async () => {
        const wrapper = getWrapper({
            item: { id: 12, title: "Dacia", embedded_file_properties: {} },
        });

        expect(wrapper.vm.$data.version.title).toBe("");
        emitter.emit("update-version-title", "A title");

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.vm.$data.version.title).toBe("A title");
    });

    it("Updates the changelog", async () => {
        const wrapper = getWrapper({
            item: { id: 12, title: "Dacia", embedded_file_properties: {} },
        });

        expect(wrapper.vm.$data.version.changelog).toBe("");
        emitter.emit("update-changelog-property", "A changelog");

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.vm.$data.version.changelog).toBe("A changelog");
    });

    it("Updates the lock", async () => {
        const wrapper = getWrapper({
            item: { id: 12, title: "Dacia", embedded_file_properties: {} },
        });

        expect(wrapper.vm.$data.version.is_file_locked).toBe(true);
        emitter.emit("update-lock", false);

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.vm.$data.version.is_file_locked).toBe(false);
    });

    it("should not retrieve the document content if there is content when the component is mounted", async () => {
        const wrapper = getWrapper({
            item: { id: 12, title: "Dacia", embedded_file_properties: { content: "Time or ..." } },
        });
        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.vm.$data.embedded_item.embedded_file_properties.content).toBe("Time or ...");
    });

    it("should retrieve the document content if there is no content when the component is mounted", async () => {
        const wrapper = getWrapper({
            item: { id: 12, title: "Dacia", embedded_file_properties: {} },
        });

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.vm.$data.embedded_item.embedded_file_properties.content).toBe("VROOM VROOM");
    });
});
