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
import type { Fault } from "@tuleap/fault";
import { moveArtifact, moveDryRunArtifact } from "../api/rest-querier";
import { redirectTo } from "../window-helper";
import { useSelectorsStore } from "./selectors";
import { useDryRunStore } from "./dry-run";

export type ModalState = {
    is_processing_move: boolean;
    error_message: string;
};

export const useModalStore = defineStore("modal", {
    state: (): ModalState => ({
        is_processing_move: false,
        error_message: "",
    }),
    actions: {
        resetError(): void {
            this.error_message = "";
        },
        setErrorMessage(fault: Fault): void {
            this.error_message = String(fault);
        },
        startProcessingMove(): void {
            this.is_processing_move = true;
        },
        stopProcessingMove(): void {
            this.is_processing_move = false;
        },
        moveDryRun(artifact_id: number): Promise<void> {
            const selected_tracker_id = useSelectorsStore().selected_tracker_id;
            if (!selected_tracker_id) {
                return Promise.reject(
                    "Expected a tracker to be selected before calling MoveDryRun",
                );
            }

            this.startProcessingMove();

            return moveDryRunArtifact(artifact_id, selected_tracker_id)
                .match((result) => {
                    const dry_run_store = useDryRunStore();

                    dry_run_store.saveDryRunState(result.dry_run.fields);

                    if (!dry_run_store.is_confirmation_needed) {
                        this.move(artifact_id);
                    }
                }, this.setErrorMessage)
                .finally(() => {
                    this.stopProcessingMove();
                });
        },
        move(artifact_id: number): Promise<void> {
            const selected_tracker_id = useSelectorsStore().selected_tracker_id;
            if (!selected_tracker_id) {
                return Promise.reject("Expected a tracker to be selected before calling move");
            }

            this.startProcessingMove();

            return moveArtifact(artifact_id, selected_tracker_id).match(() => {
                redirectTo(`/plugins/tracker/?aid=${artifact_id}`);
            }, this.setErrorMessage);
        },
    },
});
