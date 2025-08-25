/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import ItemPermissionError from "./ItemPermissionError.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { PROJECT_ID } from "../../../configuration-keys";

describe("ItemPermissionError", () => {
    it("displays the error with a for to send custom email", () => {
        const wrapper = shallowMount(ItemPermissionError, {
            data() {
                return {
                    error: "",
                    mail_content: "",
                };
            },
            props: { csrf_token: "", csrf_token_name: "challenge" },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [PROJECT_ID.valueOf()]: 101,
                },
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
