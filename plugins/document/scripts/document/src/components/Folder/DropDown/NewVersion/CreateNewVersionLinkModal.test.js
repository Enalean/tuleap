/*
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import * as tlp_modal from "@tuleap/tlp-modal";
import CreateNewVersionLinkModal from "./CreateNewVersionLinkModal.vue";
import emitter from "../../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import { nextTick } from "vue";

describe("CreateNewVersionLinkModal", () => {
    const add_event_listener = vi.fn();
    const modal_show = vi.fn();
    const remove_backdrop = vi.fn();

    function getWrapper() {
        return shallowMount(CreateNewVersionLinkModal, {
            props: {
                item: { id: 12, title: "Dacia" },
            },
            global: { ...getGlobalTestOptions({}) },
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
        const wrapper = getWrapper();

        expect(wrapper.vm.$data.version.title).toBe("");
        emitter.emit("update-version-title", "A title");

        await nextTick();

        expect(wrapper.vm.$data.version.title).toBe("A title");
    });

    it("Updates the changelog", async () => {
        const wrapper = getWrapper();

        expect(wrapper.vm.$data.version.changelog).toBe("");
        emitter.emit("update-changelog-property", "A changelog");

        await nextTick();

        expect(wrapper.vm.$data.version.changelog).toBe("A changelog");
    });

    it("Updates the lock", async () => {
        const wrapper = getWrapper();

        await nextTick();

        expect(wrapper.vm.$data.version.is_file_locked).toBe(true);
        emitter.emit("update-lock", false);

        await nextTick();

        expect(wrapper.vm.$data.version.is_file_locked).toBe(false);
    });
});
