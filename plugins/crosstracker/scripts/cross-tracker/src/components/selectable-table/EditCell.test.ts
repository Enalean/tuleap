/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import EditCell from "./EditCell.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";

describe(`EditCell`, () => {
    const getWrapper = (uri: string, even: boolean): VueWrapper<InstanceType<typeof EditCell>> => {
        return shallowMount(EditCell, {
            global: { ...getGlobalTestOptions() },
            props: { uri, even },
        });
    };

    it(`renders a link to artifact URI`, () => {
        const uri = "/plugins/tracker/?aid=77";
        const wrapper = getWrapper(uri, false);

        expect(wrapper.get("a").attributes("href")).toBe(uri);
    });
});
