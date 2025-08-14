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
import { getGlobalTestOptions } from "../../../support/global-options-for-tests";
import FieldComparison from "./FieldComparison.vue";

describe("FieldComparison", () => {
    function getWrapper() {
        return shallowMount(FieldComparison, {
            global: { ...getGlobalTestOptions() },
            props: {
                semantic: "description",
                tracker_id: 1,
                base: "My description",
                compared_to: "New description",
            },
            directives: {
                "dompurify-html": jest.fn(),
            },
        });
    }

    describe("when compared values contain html", () => {
        it("does not render dirty html", async () => {
            const wrapper = getWrapper();
            await wrapper.setProps({
                base: "My description<div onload=alert('xss')>",
                compared_to: "<div onload=alert('xss')>My description",
            });

            expect(wrapper.html()).not.toContain("<div onload=alert('xss')>");
        });
    });
});
