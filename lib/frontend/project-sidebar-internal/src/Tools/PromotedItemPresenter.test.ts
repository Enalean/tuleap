/*
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import PromotedItemPresenter from "./PromotedItemPresenter.vue";
import { shallowMount } from "@vue/test-utils";
import SubItemPresenter from "./SubItemPresenter.vue";

describe("PromotedItemPresenter", () => {
    it("Displays sub items", () => {
        const wrapper = shallowMount(PromotedItemPresenter, {
            props: {
                href: "/service/a/release-a",
                label: "Release A",
                description: "Description of release A",
                is_active: true,
                quick_link_add: {
                    href: "/service/a/release-a/add",
                    label: "Add",
                },
                items: [
                    {
                        href: "/service/a/release-a/sprint-w12",
                        label: "Sprint W12",
                        description: "Description of sprint W12",
                        is_active: true,
                    },
                    {
                        href: "/service/a/release-a/sprint-w11",
                        label: "Sprint W11",
                        description: "Description of sprint W11",
                        is_active: false,
                        quick_link_add: {
                            href: "/service/a/release-a/sprint-w11/add",
                            label: "Add",
                        },
                    },
                ],
            },
        });

        expect(wrapper.findAllComponents(SubItemPresenter)).toHaveLength(2);
    });
});
