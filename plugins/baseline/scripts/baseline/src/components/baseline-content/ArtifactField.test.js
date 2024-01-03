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
import localVue from "../../support/local-vue.ts";
import ArtifactField from "./ArtifactField.vue";

describe("Field", () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(ArtifactField, {
            localVue,
            propsData: { semantic: "description", tracker_id: 1, value: "My description" },
        });
    });

    describe("when value prop is html", () => {
        const value = "Description details <div onload=alert('xss')>";

        beforeEach(() => wrapper.setProps({ value, html_content: true }));

        it("renders sanitized value", () => {
            expect(wrapper.html()).not.toContain(value);
            expect(wrapper.html()).not.toContain("Description details <div onload=alert('xss')>");
        });
    });

    describe("when value prop is not html", () => {
        const value = "Description details <br> new line";

        beforeEach(() => wrapper.setProps({ value, html_content: false }));

        it("renders value", () => {
            expect(wrapper.text()).toContain(value);
        });
    });
});
