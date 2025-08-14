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
 *
 */

import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../support/global-options-for-tests";
import ArtifactField from "./ArtifactField.vue";

describe("Field", () => {
    function getWrapper() {
        return shallowMount(ArtifactField, {
            global: {
                ...getGlobalTestOptions(),
                directives: {
                    "dompurify-html": jest.fn(),
                },
            },
            props: { semantic: "description", tracker_id: 1, value: "My description" },
        });
    }

    describe("when value prop is html", () => {
        it("renders sanitized value", async () => {
            const value = "Description details <div onload=alert('xss')>";
            const wrapper = getWrapper();
            await wrapper.setProps({ value, html_content: true });

            expect(wrapper.html()).not.toContain(value);
            expect(wrapper.html()).not.toContain("Description details <div onload=alert('xss')>");
        });
    });

    describe("when value prop is not html", () => {
        it("renders value", async () => {
            const value = "Description details <br> new line";
            const wrapper = getWrapper();
            await wrapper.setProps({ value, html_content: false });

            expect(wrapper.text()).toContain(value);
        });
    });
});
