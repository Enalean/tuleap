/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import CardXrefLabel from "./CardXrefLabel.vue";
import { Card } from "../../../../../type";

describe("CardXrefLabel", () => {
    it("displays the xref and the label of a card", () => {
        const wrapper = shallowMount(CardXrefLabel, {
            propsData: {
                card: {
                    id: 43,
                    label: "Story 2",
                    xref: "story #43",
                    color: "lake-placid-blue",
                    artifact_html_uri: "/path/to/43",
                    is_in_edit_mode: false,
                } as Card,
                label: "Story 2",
            },
        });
        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays the xref and the label using the background color if it exists", () => {
        const wrapper = shallowMount(CardXrefLabel, {
            propsData: {
                card: {
                    id: 43,
                    label: "Story 2",
                    xref: "story #43",
                    color: "lake-placid-blue",
                    background_color: "fiesta-red",
                    artifact_html_uri: "/path/to/43",
                    is_in_edit_mode: false,
                } as Card,
                label: "Story 2",
            },
        });
        expect(wrapper.get("[data-test=xref]").classes("tlp-swatch-fiesta-red")).toBe(true);
    });

    it("hides the label when card is in edit mode", () => {
        const wrapper = shallowMount(CardXrefLabel, {
            propsData: {
                card: {
                    id: 43,
                    label: "Story 2",
                    xref: "story #43",
                    color: "lake-placid-blue",
                    background_color: "fiesta-red",
                    artifact_html_uri: "/path/to/43",
                    is_in_edit_mode: true,
                } as Card,
                label: "Story 2",
            },
        });
        expect(wrapper.text()).toBe("story #43");
    });
});
