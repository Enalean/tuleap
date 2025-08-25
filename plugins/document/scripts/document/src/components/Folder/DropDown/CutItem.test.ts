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

import type { Mock } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { Store } from "vuex";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import CutItem from "./CutItem.vue";
import type { Item, RootState } from "../../../type";
import { useClipboardStore } from "../../../stores/clipboard";
import type { TestingPinia } from "@pinia/testing";
import { createTestingPinia } from "@pinia/testing";
import { ref } from "vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import emitter from "../../../helpers/emitter";
import { PROJECT_ID, USER_ID } from "../../../configuration-keys";

const mocked_store = { store: { dispatch: vi.fn() } } as unknown as Store<RootState>;

describe("CutItem", () => {
    let pinia: TestingPinia;
    let store: ReturnType<typeof useClipboardStore>;
    let emitMock: Mock;

    function createWrapper(item: Item, pasting_in_progress: boolean): VueWrapper<CutItem> {
        pinia = createTestingPinia({
            initialState: {
                clipboard: {
                    pasting_in_progress: ref(pasting_in_progress),
                    item_id: ref(item.id),
                },
            },
            createSpy: vi.fn,
        });

        store = useClipboardStore(mocked_store, 1, 1, pinia);
        return shallowMount(CutItem, {
            global: {
                ...getGlobalTestOptions({}, pinia),
                provide: {
                    [USER_ID.valueOf()]: 1,
                    [PROJECT_ID.valueOf()]: 1,
                },
            },
            props: { item },
        });
    }

    beforeEach(() => {
        emitMock = vi.spyOn(emitter, "emit");
    });

    it(`Given item is cut
        Then the store is updated accordingly
        And the menu closed`, async () => {
        const item = {
            id: 147,
            type: "item_type",
            title: "My item",
            parent_id: 146,
            user_can_write: true,
        } as Item;
        const wrapper = createWrapper(item, false);

        await wrapper.trigger("click");

        expect(store.cutItem).toHaveBeenCalledWith(item);
        expect(emitMock).toHaveBeenCalledWith("hide-action-menu");
    });

    it(`Given an item is being pasted
        Then the action is marked as disabled
        And the menu is not closed if the user tries to click on it`, () => {
        const item = {
            id: 147,
            type: "item_type",
            title: "My item",
            parent_id: 146,
            user_can_write: true,
        } as Item;
        const wrapper = createWrapper(item, true);

        expect(wrapper.attributes().disabled).toBe("");
        expect(wrapper.classes("tlp-dropdown-menu-item-disabled")).toBe(true);

        wrapper.trigger("click");

        expect(emitMock).not.toHaveBeenCalled();
    });

    it(`Given the item is the root
        Then the cut action is not visible`, () => {
        const item = {
            id: 147,
            type: "item_type",
            title: "My item",
            parent_id: 0,
            user_can_write: true,
        } as Item;
        const wrapper = createWrapper(item, false);

        expect(wrapper.html()).toBe("<!--v-if-->");
    });

    it(`Given the item is not writable
        Then the cut action is not visible`, () => {
        const item = {
            id: 147,
            type: "item_type",
            title: "My item",
            parent_id: 146,
            user_can_write: false,
        } as Item;
        const wrapper = createWrapper(item, false);

        expect(wrapper.html()).toBe("<!--v-if-->");
    });
});
