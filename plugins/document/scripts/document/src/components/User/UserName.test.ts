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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import UserName from "./UserName.vue";

describe("UserName", () => {
    it(`Given user is connected
        When we display the user name
        Then we should be able to click on its name`, () => {
        const wrapper = shallowMount(UserName, {
            props: {
                user: {
                    id: 1,
                    is_anonymous: false,
                },
            },
        });

        expect(wrapper.find("[data-test=document-user-profile-link]").exists()).toBeTruthy();
    });

    it(`Given user is anonymous
        When we display the user name
        Then we should not be able to click on its name`, () => {
        const wrapper = shallowMount(UserName, {
            props: {
                user: {
                    id: 1,
                    is_anonymous: true,
                },
            },
        });

        expect(wrapper.find("[data-test=document-user-profile-link]").exists()).toBeFalsy();
    });
});
