/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { describe, beforeEach, it, expect } from "vitest";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import type { HeadingsButtonState } from "@/toolbar/HeadingsButtonState";
import { getHeadingsButtonState } from "@/toolbar/HeadingsButtonState";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";

describe("getHeadingsButtonState", () => {
    let headings_button_state: HeadingsButtonState;

    beforeEach(() => {
        headings_button_state = getHeadingsButtonState();
    });

    it("should initialize with disabled button and undefined section", () => {
        expect(headings_button_state.is_button_active.value).toBeFalsy();
        expect(headings_button_state.active_section.value).toBeUndefined();
    });

    it("should activate button and set the section when activeButtonForSection is called", () => {
        const artidoc_section = ReactiveStoredArtidocSectionStub.fromSection(
            ArtifactSectionFactory.create(),
        );

        headings_button_state.activateButtonForSection(artidoc_section);
        expect(headings_button_state.is_button_active.value).toBeTruthy();
        expect(headings_button_state.active_section.value).toStrictEqual(artidoc_section);
    });

    it("should deactivate button and reset the section to undefined when deactivateButton is called", () => {
        headings_button_state.deactivateButton();
        expect(headings_button_state.is_button_active.value).toBeFalsy();
        expect(headings_button_state.active_section.value).toBeUndefined();
    });
});
