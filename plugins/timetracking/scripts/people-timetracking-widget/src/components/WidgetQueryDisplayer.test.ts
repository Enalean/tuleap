/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
import WidgetQueryDisplayer from "./WidgetQueryDisplayer.vue";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";
import type { User } from "@tuleap/core-rest-api-types";
import { QueryStub } from "../../tests/stubs/QueryStub";
import type { Query } from "../type";

const mireillelabeille: User = {
    id: 101,
    avatar_url: "https://example.com/users/mireillelabeille/avatar-mireillelabeille.png",
    display_name: "Mireille L'Abeille (mireillelabeille)",
    user_url: "/users/mireillelabeille",
};

const bellelacoccinelle: User = {
    id: 102,
    avatar_url: "https://example.com/users/bellelacoccinelle/avatar-bellelacoccinelle.png",
    display_name: "Belle La Coccinelle (bellelacoccinelle)",
    user_url: "/users/bellelacoccinelle",
};

describe("Given a people timetracking widget query displayer", () => {
    let users_list: User[] = [];
    let query: Query;

    function getWidgetQueryDisplayerInstance(): VueWrapper {
        query = QueryStub.withDefaults(users_list);

        return shallowMount(WidgetQueryDisplayer, {
            props: {
                query,
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });
    }

    describe("When query is displaying", () => {
        it("Then it should display the start date", () => {
            const wrapper = getWidgetQueryDisplayerInstance();

            const start_date = wrapper.find("[data-test=start-date]");

            expect(start_date.text()).equals(query.start_date);
        });

        it("Then it should display the end date", () => {
            const wrapper = getWidgetQueryDisplayerInstance();

            const end_date = wrapper.find("[data-test=end-date]");

            expect(end_date.text()).equals(query.end_date);
        });

        it("When some users are selected, then it should display their avatar", () => {
            users_list = [mireillelabeille, bellelacoccinelle];

            const wrapper = getWidgetQueryDisplayerInstance();

            const images = wrapper.findAll("[data-test=img-avatar]");
            const avatars_url = images.map((img) => {
                return img.attributes().src;
            });

            expect(avatars_url).toStrictEqual([
                mireillelabeille.avatar_url,
                bellelacoccinelle.avatar_url,
            ]);
        });

        it("When no users are selected, then it should display a message", () => {
            users_list = [];

            const wrapper = getWidgetQueryDisplayerInstance();

            const users = wrapper.find("[data-test=users-displayer]");

            expect(users.text()).equals("No user selected");
        });
    });
});
