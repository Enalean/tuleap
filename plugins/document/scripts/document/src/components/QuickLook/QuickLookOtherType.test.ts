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
import QuickLookOtherType from "./QuickLookOtherType.vue";
import type { OtherTypeItem } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("QuickLookOtherType", () => {
    it("renders quick look for other type document with a CTA to open the document", () => {
        const item = {
            id: 42,
            title: "my document",
            other_type_properties: {
                open_href: "/path/to/open/42",
            },
            type: "whatever",
        } as OtherTypeItem;

        const wrapper = shallowMount(QuickLookOtherType, {
            props: { item: item },
            global: { ...getGlobalTestOptions({}) },
        });

        const cta = wrapper.find<HTMLAnchorElement>(
            "[data-test=document-quick-look-document-cta-open]",
        ).element;
        expect(cta.href).toContain("/path/to/open/42");
    });
});
