/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
import { RouterLinkStub, shallowMount } from "@vue/test-utils";
import GoBackToRootButton from "./GoBackToRootButton.vue";

import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import * as router from "vue-router";

vi.mock("vue-router");
import type { RouteLocationNormalizedLoaded } from "vue-router";

describe("GoBackToRootButton", () => {
    beforeEach(() => {
        vi.spyOn(router, "useRoute").mockReturnValue({
            params: { item_id: "101" },
        } as unknown as RouteLocationNormalizedLoaded);
    });

    function getWrapper(): VueWrapper<InstanceType<typeof GoBackToRootButton>> {
        return shallowMount(GoBackToRootButton, {
            global: {
                ...getGlobalTestOptions({}),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });
    }

    it(`Given we are not displaying root folder
        When error is displayed
        Then a button go back to root is displayed`, () => {
        const wrapper = getWrapper();
        expect(wrapper.find("[data-test=item-can-go-to-root-button]").exists()).toBeTruthy();
    });

    it(`Given we are displaying root folder
        When error is displayed
        Then no button is displayed`, () => {
        const wrapper = getWrapper();
        expect(wrapper.find("[data-test=can-go-to-root-button]").exists()).toBeFalsy();
    });
});
