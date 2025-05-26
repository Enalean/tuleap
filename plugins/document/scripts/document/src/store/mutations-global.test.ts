/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
import * as mutations from "./mutations-global";
import { StateBuilder } from "../../tests/builders/StateBuilder";
import { ItemBuilder } from "../../tests/builders/ItemBuilder";
import { FolderBuilder } from "../../tests/builders/FolderBuilder";

describe("Store mutations", () => {
    describe("beginLoading()", () => {
        it("sets loading to true", () => {
            const state = new StateBuilder().thatIsLoadingFolder(false).build();

            mutations.beginLoading(state);

            expect(state.is_loading_folder).toBe(true);
        });
    });

    describe("stopLoading()", () => {
        it("sets loading to false", () => {
            const state = new StateBuilder().thatIsLoadingFolder(true).build();

            mutations.stopLoading(state);

            expect(state.is_loading_folder).toBe(false);
        });
    });

    describe("appendFolderToAscendantHierarchy", () => {
        it("get all the ids of the direct ascendants", () => {
            const target_folder = new FolderBuilder(43).withParentId(41).build();
            const state = new StateBuilder()
                .withFolderContent([
                    new FolderBuilder(30).withParentId(0).build(),
                    new ItemBuilder(32).withParentId(30).build(),
                    new ItemBuilder(34).withParentId(32).build(),
                    new ItemBuilder(36).withParentId(34).build(),
                    new ItemBuilder(37).withParentId(34).build(),
                    new ItemBuilder(35).withParentId(32).build(),
                    new ItemBuilder(38).withParentId(35).build(),
                    new ItemBuilder(39).withParentId(35).build(),
                    new FolderBuilder(33).withParentId(30).build(),
                    new ItemBuilder(40).withParentId(33).build(),
                    new FolderBuilder(41).withParentId(33).build(),
                    target_folder,
                    new ItemBuilder(42).withParentId(33).build(),
                    new ItemBuilder(31).withParentId(0).build(),
                ])
                .build();

            mutations.appendFolderToAscendantHierarchy(state, target_folder);
            expect(state.current_folder_ascendant_hierarchy).toHaveLength(4);
            expect(state.current_folder_ascendant_hierarchy).toEqual([
                new FolderBuilder(30).withParentId(0).build(),
                new FolderBuilder(33).withParentId(30).build(),
                new FolderBuilder(41).withParentId(33).build(),
                target_folder,
            ]);
        });
    });

    describe("toggle quick look", () => {
        it("toggle quick look to true", () => {
            const state = new StateBuilder().withToggleQuickLook(false).build();
            mutations.toggleQuickLook(state, true);

            expect(state.toggle_quick_look).toBe(true);
        });

        it("toggle quick look to false", () => {
            const state = new StateBuilder().withToggleQuickLook(true).build();
            mutations.toggleQuickLook(state, false);

            expect(state.toggle_quick_look).toBe(false);
        });
    });
});
