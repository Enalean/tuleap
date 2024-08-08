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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { Option } from "@tuleap/option";
import UserValue from "./UserValue.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import type { UserCellValue } from "../../domain/ArtifactsTable";

describe(`UserValue`, () => {
    const getWrapper = (user: UserCellValue): VueWrapper<InstanceType<typeof UserValue>> => {
        return shallowMount(UserValue, {
            global: { ...getGlobalTestOptions() },
            props: { user },
        });
    };

    describe(`render()`, () => {
        it(`renders a link to the user URI and an image with the avatar URI`, () => {
            const user_uri = "/users/mcastro";
            const avatar_uri = "https://example.com/users/mcastro/avatar.png";
            const wrapper = getWrapper({
                display_name: "Mario Castro (mcastro)",
                user_uri: Option.fromValue(user_uri),
                avatar_uri,
            });

            expect(wrapper.get("a").attributes("href")).toBe(user_uri);
            expect(wrapper.get("img").attributes("src")).toBe(avatar_uri);
        });

        it(`does not render a link when the user is anonymous`, () => {
            const wrapper = getWrapper({
                display_name: "Anonymous user",
                user_uri: Option.nothing(),
                avatar_uri: "https://example.com/themes/common/images/avatar_default.png",
            });

            expect(wrapper.find("a").exists()).toBe(false);
        });
    });
});
