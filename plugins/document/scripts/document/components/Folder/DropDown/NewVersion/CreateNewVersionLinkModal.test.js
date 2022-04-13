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

import { shallowMount } from "@vue/test-utils";
import localVue from "../../../../helpers/local-vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import * as tlp from "tlp";
import CreateNewVersionLinkModal from "./CreateNewVersionLinkModal.vue";
import emitter from "../../../../helpers/emitter";

jest.mock("tlp");

describe("CreateNewVersionLinkModal", () => {
    const add_event_listener = jest.fn();
    const modal_show = jest.fn();
    const remove_backdrop = jest.fn();

    function getWrapper() {
        const state = {
            error: { has_modal_error: false },
        };
        const store_option = { state };
        const store = createStoreMock(store_option);

        return shallowMount(CreateNewVersionLinkModal, {
            localVue,
            propsData: {
                item: { id: 12, title: "Dacia" },
            },
            mocks: { $store: store },
        });
    }

    beforeEach(() => {
        jest.spyOn(tlp, "createModal").mockImplementation(() => {
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

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.version.title).toBe("A title");
    });

    it("Updates the changelog", async () => {
        const wrapper = getWrapper();

        expect(wrapper.vm.$data.version.changelog).toBe("");
        emitter.emit("update-changelog-property", "A changelog");

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.version.changelog).toBe("A changelog");
    });
});
