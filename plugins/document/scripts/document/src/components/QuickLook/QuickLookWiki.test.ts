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
import QuickLookWiki from "./QuickLookWiki.vue";
import { TYPE_WIKI } from "../../constants";
import type { Wiki } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { PROJECT } from "../../configuration-keys";
import { ProjectBuilder } from "../../../tests/builders/ProjectBuilder";
import { ItemBuilder } from "../../../tests/builders/ItemBuilder";

describe("QuickLookWiki", () => {
    it("renders quick look for wiki document", () => {
        const item: Wiki = {
            ...new ItemBuilder(123).withType(TYPE_WIKI).buildApprovableDocument(),
            wiki_properties: {
                page_name: "my_wiki_page",
                page_id: 12,
                version_number: 1,
            },
        };

        const wrapper = shallowMount(QuickLookWiki, {
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [PROJECT.valueOf()]: new ProjectBuilder(101).build(),
                },
            },
            props: { item },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
