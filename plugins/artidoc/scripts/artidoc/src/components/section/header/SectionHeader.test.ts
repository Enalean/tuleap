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
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import type { Level } from "@/sections/levels/SectionsNumberer";
import { LEVEL_1, LEVEL_2, LEVEL_3 } from "@/sections/levels/SectionsNumberer";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";

describe("SectionHeader", () => {
    let can_user_edit_document: boolean, is_print_mode: boolean;

    const buildSectionWithLevel = (level: Level): ReactiveStoredArtidocSection =>
        ReactiveStoredArtidocSectionStub.fromSection(
            ArtifactSectionFactory.override({
                level,
                title: "Section title",
                display_level:
                    level === LEVEL_1
                        ? "1. "
                        : level === LEVEL_2
                          ? "1. 1. "
                          : level === LEVEL_3
                            ? "1. 1. 1. "
                            : "",
            }),
        );

    const getWrapper = (section: ReactiveStoredArtidocSection): VueWrapper =>
        shallowMount(SectionHeader, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [CAN_USER_EDIT_DOCUMENT.valueOf()]: can_user_edit_document,
                },
            },
            props: {
                is_print_mode,
                section,
            },
        });

    it("When the current user cannot edit the document and it is not in print mode, then it should display a readonly title", () => {
        can_user_edit_document = false;
        is_print_mode = false;

        const section = buildSectionWithLevel(LEVEL_1);

        expect(getWrapper(section).find("h1").text()).toBe(section.value.title);
    });

    it("When the user can edit the document, but it is in print mode, then it should display a readonly title", () => {
        can_user_edit_document = true;
        is_print_mode = true;

        const section = buildSectionWithLevel(LEVEL_1);

        expect(getWrapper(section).find("h1").text()).toBe(
            `${section.value.display_level}${section.value.title}`,
        );
    });

    it("When the user can edit the document, and it is NOT in print mode, then it should display nothing", () => {
        can_user_edit_document = true;
        is_print_mode = false;

        const section = buildSectionWithLevel(LEVEL_1);

        expect(getWrapper(section).find("h1").exists()).toBe(false);
    });

    it.each([
        [LEVEL_1, "h1"],
        [LEVEL_2, "h2"],
        [LEVEL_3, "h3"],
    ])(
        "When the section is at level %s, then the title should be rendered as a <%s> title.",
        (level, expected_tag) => {
            can_user_edit_document = false;
            is_print_mode = true;

            const section = buildSectionWithLevel(level);

            expect(getWrapper(section).find(expected_tag).text()).toBe(
                `${section.value.display_level}${section.value.title}`,
            );
        },
    );
});
