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

import { defineStore } from "pinia";
import type { ArtifactField, DryRunState } from "../api/types";

type DryRun = {
    fields_not_migrated: ArtifactField[];
    fields_partially_migrated: ArtifactField[];
    fields_migrated: ArtifactField[];
    has_processed_dry_run: boolean;
};

export const useDryRunStore = defineStore("dry-run", {
    state: (): DryRun => ({
        fields_not_migrated: [],
        fields_partially_migrated: [],
        fields_migrated: [],
        has_processed_dry_run: false,
    }),
    actions: {
        saveDryRunState(dry_run_state: DryRunState): void {
            this.fields_not_migrated = dry_run_state.fields_not_migrated;
            this.fields_partially_migrated = dry_run_state.fields_partially_migrated;
            this.fields_migrated = dry_run_state.fields_migrated;
            this.has_processed_dry_run = true;
        },
    },
    getters: {
        is_move_possible(state): boolean {
            return state.fields_partially_migrated.length > 0 || state.fields_migrated.length > 0;
        },
        is_confirmation_needed(state): boolean {
            return (
                state.fields_partially_migrated.length > 0 || state.fields_not_migrated.length > 0
            );
        },
    },
});
