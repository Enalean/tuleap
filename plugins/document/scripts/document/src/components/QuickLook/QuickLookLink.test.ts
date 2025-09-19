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
import QuickLookLink from "./QuickLookLink.vue";
import { TYPE_LINK } from "../../constants";
import type { Item } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { PROJECT } from "../../configuration-keys";
import { ProjectBuilder } from "../../../tests/builders/ProjectBuilder";

describe("QuickLookLink", () => {
    it("renders quick look for link document", () => {
        const item = {
            type: TYPE_LINK,
        } as Item;

        const wrapper = shallowMount(QuickLookLink, {
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [PROJECT.valueOf()]: new ProjectBuilder(101).build(),
                },
            },
            props: { item: item },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
