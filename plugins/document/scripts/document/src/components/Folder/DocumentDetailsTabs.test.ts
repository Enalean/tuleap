/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
 */

import { describe, expect, it } from "vitest";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../constants";
import { RouterLinkStub, shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import DocumentDetailsTabs from "./DocumentDetailsTabs.vue";
import { ItemBuilder } from "../../../tests/builders/ItemBuilder";
describe("DocumentDetailsTabs", () => {
    it.each([
        [TYPE_FOLDER, false],
        [TYPE_FILE, true],
        [TYPE_LINK, true],
        [TYPE_EMBEDDED, true],
        [TYPE_WIKI, false],
        [TYPE_EMPTY, false],
    ])(`should display a Versions link for %s: %s`, (type, should_versions_link_be_displayed) => {
        const wrapper = shallowMount(DocumentDetailsTabs, {
            props: { item: new ItemBuilder(12).withType(type).build(), active_tab: "versions" },
            global: {
                ...getGlobalTestOptions({}),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });
        expect(wrapper.vm.item_has_versions).toBe(should_versions_link_be_displayed);
    });
});
