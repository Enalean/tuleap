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
import type { TestingPinia } from "@pinia/testing";
import { createTestingPinia } from "@pinia/testing";
const emitMock = jest.fn();
jest.mock("../../../helpers/emitter", () => {
    return {
        emit: emitMock,
    };
});
const mocked_store = { store: { dispatch: jest.fn() } } as unknown as Store<RootState>;
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import CopyItem from "./CopyItem.vue";
import type { Item, RootState } from "../../../type";
import { useClipboardStore } from "../../../stores/clipboard";
import { nextTick, ref } from "vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import type { ConfigurationState } from "../../../store/configuration";
import type { Store } from "vuex";
describe("CopyItem", () => {
    let pinia: TestingPinia;
    let store: ReturnType<typeof useClipboardStore>;

    function createWrapper(item: Item, pasting_in_progress: boolean): VueWrapper<CopyItem> {
        pinia = createTestingPinia({
            initialState: {
                clipboard: {
                    pasting_in_progress: ref(pasting_in_progress),
                    item_id: ref(item.id),
                },
            },
        });

        store = useClipboardStore(mocked_store, "1", "1", pinia);
        return shallowMount(CopyItem, {
            props: { item },
            global: {
                ...getGlobalTestOptions(
                    {
                        modules: {
                            configuration: {
                                state: {
                                    user_id: "1",
                                    project_id: "1",
                                } as ConfigurationState,
                                namespaced: true,
                            },
                        },
                    },
                    pinia,
                ),
            },
        });
    }
    beforeEach(() => {
        emitMock.mockClear();
    });
    it(`Given item is copied
        Then the store is updated accordingly
        And the menu closed`, async () => {
        const item = { id: 147, type: "item_type", title: "My item" } as Item;
        const wrapper = createWrapper(item, false);

        wrapper.trigger("click");
        await nextTick();

        expect(store.copyItem).toHaveBeenCalledWith(item);

        expect(emitMock).toHaveBeenCalledWith("hide-action-menu");
    });
    it(`Given an item is being pasted
        Then the action is marked as disabled
        And the menu is not closed if the user tries to click on it`, async () => {
        const item = { id: 147, type: "item_type", title: "My item" } as Item;

        const wrapper = createWrapper(item, true);
        await nextTick();

        expect(wrapper.attributes().disabled).toBe("");
        expect(wrapper.classes("tlp-dropdown-menu-item-disabled")).toBe(true);
        wrapper.trigger("click");
        expect(emitMock).not.toHaveBeenCalled();
    });
});
