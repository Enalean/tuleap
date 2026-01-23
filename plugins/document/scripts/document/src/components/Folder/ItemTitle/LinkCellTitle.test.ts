/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
import LinkCellTitle from "./LinkCellTitle.vue";
import { shallowMount } from "@vue/test-utils";
import { TYPE_LINK } from "../../../constants";
import type { Link } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { ItemBuilder } from "../../../../tests/builders/ItemBuilder";

describe("LinkCellTitle", () => {
    it(`should render link title`, () => {
        const item: Link = {
            ...new ItemBuilder(42)
                .withType(TYPE_LINK)
                .withTitle("my link")
                .withIcon("fa-solid fa-link document-link-icon")
                .buildApprovableDocument(),
            link_properties: {
                link_url: "https://example.com",
                version_number: null,
            },
        };

        const wrapper = shallowMount(LinkCellTitle, {
            props: { item },
            global: {
                ...getGlobalTestOptions({}),
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
