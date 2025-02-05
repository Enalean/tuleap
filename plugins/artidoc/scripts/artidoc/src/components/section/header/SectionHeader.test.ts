/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SectionHeader from "./SectionHeader.vue";
import { createGettext } from "vue3-gettext";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";

const current_title = "Current section title";
const current_display_level = "1. ";
const number_and_title = current_display_level + current_title;

describe("SectionHeader", () => {
    let can_user_edit_document: boolean, is_print_mode: boolean;

    const getWrapper = (): VueWrapper =>
        shallowMount(SectionHeader, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [CAN_USER_EDIT_DOCUMENT.valueOf()]: can_user_edit_document,
                },
            },
            props: {
                title: current_title,
                is_print_mode,
                is_freetext: false,
                display_level: current_display_level,
            },
        });

    it("When the current user cannot edit the document and it is not in print mode, then it should display a readonly title", () => {
        can_user_edit_document = false;
        is_print_mode = false;

        const readonly_title = getWrapper().find("h1");
        expect(readonly_title.text()).toBe(current_title);
    });

    it("When the user can edit the document, but it is in print mode, then it should display a readonly title", () => {
        can_user_edit_document = true;
        is_print_mode = true;

        const readonly_title = getWrapper().find("h1");
        expect(readonly_title.text()).toBe(number_and_title);
    });

    it("When the user can edit the document, and it is NOT in print mode, then it should display nothing", () => {
        can_user_edit_document = true;
        is_print_mode = false;

        expect(getWrapper().find("h1").exists()).toBe(false);
    });
});
