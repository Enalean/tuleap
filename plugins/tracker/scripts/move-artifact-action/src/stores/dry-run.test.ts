/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";
import type { ArtifactField, DryRunState } from "../api/types";
import { useDryRunStore } from "./dry-run";

describe("dry-run store", () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it("saveDryRunState() should store the dry run state", () => {
        const dry_run_store = useDryRunStore();

        dry_run_store.saveDryRunState({
            fields_not_migrated: [{} as ArtifactField],
            fields_partially_migrated: [{} as ArtifactField],
            fields_migrated: [{} as ArtifactField],
        });

        expect(dry_run_store.has_processed_dry_run).toBe(true);
        expect(dry_run_store.fields_not_migrated).toHaveLength(1);
        expect(dry_run_store.fields_partially_migrated).toHaveLength(1);
        expect(dry_run_store.fields_migrated).toHaveLength(1);
    });

    it.each([
        [true, "there is at least 1 field migrated", "fields_migrated" as keyof DryRunState],
        [
            true,
            "there is at least 1 field partially migrated",
            "fields_partially_migrated" as keyof DryRunState,
        ],
        [false, "there are only fields not migrated", "fields_not_migrated" as keyof DryRunState],
    ])(
        "is_move_possible should be %s when %s",
        (expected, when, present_fields: keyof DryRunState) => {
            const dry_run_store = useDryRunStore();

            const dry_run_state: DryRunState = {
                fields_not_migrated: [],
                fields_partially_migrated: [],
                fields_migrated: [],
            };

            dry_run_state[present_fields].push({} as ArtifactField);

            dry_run_store.saveDryRunState(dry_run_state);
            expect(dry_run_store.is_move_possible).toBe(expected);
        }
    );

    it.each([
        [
            true,
            "there is at least 1 field not migrated",
            "fields_not_migrated" as keyof DryRunState,
        ],
        [
            true,
            "there is at least 1 field partially migrated",
            "fields_partially_migrated" as keyof DryRunState,
        ],
        [false, "there are only fields migrated", "fields_migrated" as keyof DryRunState],
    ])(
        "is_confirmation_needed should be %s when %s",
        (expected, when, present_fields: keyof DryRunState) => {
            const dry_run_store = useDryRunStore();

            const dry_run_state: DryRunState = {
                fields_not_migrated: [],
                fields_partially_migrated: [],
                fields_migrated: [],
            };

            dry_run_state[present_fields].push({} as ArtifactField);

            dry_run_store.saveDryRunState(dry_run_state);
            expect(dry_run_store.is_confirmation_needed).toBe(expected);
        }
    );
});
