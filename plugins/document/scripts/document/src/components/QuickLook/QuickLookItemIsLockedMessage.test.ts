/*
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import QuickLookItemIsLockedMessage from "./QuickLookItemIsLockedMessage.vue";
import type { User } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("QuickLookItemIsLockedMessage", () => {
    it("renders locked message for document", () => {
        const wrapper = shallowMount(QuickLookItemIsLockedMessage, {
            props: {
                lock_info: {
                    lock_by: { id: 1 } as User,
                    lock_date: "2019-04-25T16:32:59+02:00",
                },
            },
            global: {
                ...getGlobalTestOptions({}),
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
