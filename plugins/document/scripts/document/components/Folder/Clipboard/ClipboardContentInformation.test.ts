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
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ClipboardContentInformation from "./ClipboardContentInformation.vue";
import { CLIPBOARD_OPERATION_CUT, CLIPBOARD_OPERATION_COPY } from "../../../constants";
import { useClipboardStore } from "../../../stores/clipboard";
import type { ClipboardState } from "../../../stores/types";
import type { TestingPinia } from "@pinia/testing";
import { createTestingPinia } from "@pinia/testing";
import { ref } from "vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import type { ConfigurationState } from "../../../store/configuration";

let pinia: TestingPinia;
const mocked_store = { dispatch: jest.fn() };

function getWrapper(clipboard: ClipboardState): Wrapper<ClipboardContentInformation> {
    pinia = createTestingPinia({
        initialState: {
            clipboard,
        },
    });
    useClipboardStore(mocked_store, "1", "1", pinia);
    return shallowMount(ClipboardContentInformation, {
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
describe("ClipboardContentInformation", () => {
    it(`Given there is no item in the clipboard
        Then no information is displayed`, () => {
        const wrapper = getWrapper({
            item_id: ref(null),
            item_type: ref(null),
            item_title: ref(null),
            operation_type: ref(null),
            pasting_in_progress: ref(false),
        });
        expect(wrapper.html()).toBe("<!--v-if-->");
    });
    it.each([
        [CLIPBOARD_OPERATION_CUT, "You are currently moving"],
        [CLIPBOARD_OPERATION_COPY, "You are currently copying"],
    ])(
        "Given there is an item in the clipboard  Then information is displayed for %s",
        (operation_type: string, expected_message: string) => {
            const clipboard = {
                item_id: ref(123),
                item_type: ref("folder"),
                item_title: ref("My item"),
                operation_type: ref(operation_type),
                pasting_in_progress: ref(false),
            };
            const wrapper = getWrapper(clipboard);

            const result_copy = wrapper.html();
            expect(result_copy).toContain(expected_message);
        },
    );

    it.each([
        [CLIPBOARD_OPERATION_CUT, "is being moved…"],
        [CLIPBOARD_OPERATION_COPY, "is being copied…"],
    ])(
        "Given pasting is in progress Then information is displayed for %s",
        (operation_type: string, expected_message: string) => {
            const clipboard = {
                item_id: ref(123),
                item_type: ref("folder"),
                item_title: ref("My item"),
                operation_type: ref(operation_type),
                pasting_in_progress: ref(true),
            };
            const wrapper = getWrapper(clipboard);

            const result_copy = wrapper.html();
            expect(result_copy).toContain(expected_message);
        },
    );
});
