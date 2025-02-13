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
import { useOpenAddExistingSectionModalBus } from "@/composables/useOpenAddExistingSectionModalBus";
import { noop } from "@/helpers/noop";
import type { PositionForSection } from "@/sections/save/SectionsPositionsForSaveRetriever";
import { AT_THE_END } from "@/sections/insert/SectionsInserter";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";

describe("useOpenAddExistingSectionModalBus", () => {
    it("should call the open modal handler", () => {
        let has_been_called = false;

        const bus = useOpenAddExistingSectionModalBus();
        bus.registerHandler(() => {
            has_been_called = true;
        });

        expect(has_been_called).toBe(false);

        bus.openModal(AT_THE_END, noop);

        expect(has_been_called).toBe(true);
    });

    it("should call the open modal handler with a callback", () => {
        let has_been_called = false;

        const onSuccessfulSaved = (): void => {
            has_been_called = true;
        };

        const bus = useOpenAddExistingSectionModalBus();
        bus.registerHandler(
            (
                position: PositionForSection,
                on_successful_addition: (section: ArtidocSection) => void,
            ) => {
                on_successful_addition(ArtifactSectionFactory.create());
            },
        );

        expect(has_been_called).toBe(false);

        bus.openModal(AT_THE_END, onSuccessfulSaved);

        expect(has_been_called).toBe(true);
    });
});
