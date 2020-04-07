/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

import TrackerFromJiraServer from "./TrackerFromJiraServer.vue";
import { createTrackerCreationLocalVue } from "../../../../../helpers/local-vue-for-tests";
import { shallowMount } from "@vue/test-utils";
import { Credentials } from "../../../../../store/type";

describe("TrackerFromJiraServer", () => {
    it("renders the component", async () => {
        const wrapper = shallowMount(TrackerFromJiraServer, {
            localVue: await createTrackerCreationLocalVue(),
            propsData: {
                value: {
                    server_url: "https://example.com",
                    user_email: "user-email@example.com",
                    token: "azerty1234",
                } as Credentials,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
