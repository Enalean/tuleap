/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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

import { createLocalVue, shallowMount } from "@vue/test-utils";
import JenkinsServer from "./JenkinsServers.vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import GetTextPlugin from "vue-gettext";

jest.mock("tlp");

describe("JenkinsServer", () => {
    it("displays the badge and a basic popover", () => {
        const localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });

        const wrapper = shallowMount(JenkinsServer, {
            localVue,
            propsData: {
                servers: [{ id: 1, url: "https://example.com" }],
            },
        });

        expect(wrapper).toMatchSnapshot();
    });
});
