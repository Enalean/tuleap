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
import type { ComponentPublicInstance } from "vue";
import { createGettext } from "vue3-gettext";
import SectionFooter from "./SectionFooter.vue";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { CreateStoredSections } from "@/sections/states/CreateStoredSections";
import type { SectionState } from "@/sections/states/SectionStateBuilder";
import { SectionStateStub } from "@/sections/stubs/SectionStateStub";
import { SectionEditorCloserStub } from "@/sections/stubs/SectionEditorCloserStub";
import { SectionRefresherStub } from "@/sections/stubs/SectionRefresherStub";
import { SaveSectionStub } from "@/sections/stubs/SaveSectionStub";

describe("SectionFooter", () => {
    function getWrapper(section_state: SectionState): VueWrapper<ComponentPublicInstance> {
        return shallowMount(SectionFooter, {
            propsData: {
                section: CreateStoredSections.fromArtidocSection(ArtifactSectionFactory.create()),
                section_state,
                close_section_editor: SectionEditorCloserStub.withExpectedCall(),
                refresh_section: SectionRefresherStub.withNoExpectedCall(),
                save_section: SaveSectionStub.withNoExpectedCall(),
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });
    }

    describe("when the section is not editable", () => {
        it("should hide the footer", () => {
            expect(getWrapper(SectionStateStub.notEditable()).find("div").exists()).toBe(false);
        });
    });

    describe("when the section is editable", () => {
        it("should display the footer", () => {
            expect(getWrapper(SectionStateStub.inEditMode()).find("div").exists()).toBe(true);
        });
    });
});
