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

import { shallowMount } from "@vue/test-utils";
import UserBadge from "./UserBadge.vue";

import localVue from "../../helpers/local-vue.js";

describe("UserBadge", () => {
    let user_badge_factory;
    beforeEach(() => {
        user_badge_factory = (props = {}) => {
            return shallowMount(UserBadge, {
                localVue,
                propsData: { ...props },
            });
        };
    });

    it(`Given user has avatar
        When we display the user badge
        Then its avatar is displayed`, () => {
        const wrapper = user_badge_factory({
            user: {
                id: 1,
                has_avatar: true,
                user_url: "https://example.com/avatar",
                is_anonymous: false,
            },
        });

        expect(wrapper.find("[data-test=document-user-avatar]").exists()).toBeTruthy();
    });

    it(`Given user hasn't an avatar
        When we display the user badge
        Then whe should not display it`, () => {
        const wrapper = user_badge_factory({
            user: {
                id: 1,
                has_avatar: false,
                user_url: "https://example.com/avatar",
                is_anonymous: false,
            },
        });

        expect(wrapper.find("[data-test=document-user-avatar]").exists()).toBeFalsy();
    });
});
