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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { setActivePinia, createPinia } from "pinia";
import type { SpyInstance } from "vitest";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import * as rest_querier from "../api/rest-querier";
import type { DryRunState } from "../api/types";
import * as window_helper from "../window-helper";
import { useModalStore } from "./modal";
import { useSelectorsStore } from "./selectors";
import { useDryRunStore } from "./dry-run";

const artifact_id = 101,
    tracker_id = 5;

describe("modal store", () => {
    let redirectTo: SpyInstance;

    beforeEach(() => {
        setActivePinia(createPinia());

        redirectTo = vi.spyOn(window_helper, "redirectTo").mockImplementation((): void => {
            // Do nothing
        });
    });

    it("setErrorMessage should store the error message provided in the given Fault", () => {
        const error_message = "Oh snap!";
        const modal_store = useModalStore();

        modal_store.setErrorMessage(Fault.fromMessage(error_message));

        expect(modal_store.error_message).toBe(error_message);
    });

    it("resetError should reset the error message", () => {
        const modal_store = useModalStore();

        modal_store.setErrorMessage(Fault.fromMessage("Oh snap!"));
        modal_store.resetError();

        expect(modal_store.error_message).toBe("");
    });

    it("startProcessingMove() sets is_processing_move to true, stopProcessingMove() sets it to false", () => {
        const modal_store = useModalStore();
        expect(modal_store.is_processing_move).toBe(false);

        modal_store.startProcessingMove();
        expect(modal_store.is_processing_move).toBe(true);

        modal_store.stopProcessingMove();
        expect(modal_store.is_processing_move).toBe(false);
    });

    describe("move", () => {
        let moveArtifact: SpyInstance;
        beforeEach(() => {
            moveArtifact = vi.spyOn(rest_querier, "moveArtifact");

            useSelectorsStore().saveSelectedTrackerId(tracker_id);
        });

        it("When I want to process the move, Then it should process move.", async () => {
            moveArtifact.mockReturnValue(okAsync({}));

            await useModalStore().move(artifact_id);
            expect(moveArtifact).toHaveBeenCalledWith(artifact_id, tracker_id);
            expect(redirectTo).toHaveBeenCalledWith(`/plugins/tracker/?aid=${artifact_id}`);
        });

        it("When the server responds with an error the error message is stored", async () => {
            const modal_store = useModalStore();
            const error_message = "Oh snap!";

            moveArtifact.mockReturnValue(errAsync(Fault.fromMessage(error_message)));

            await modal_store.move(artifact_id);
            expect(modal_store.error_message).toBe(error_message);
            expect(redirectTo).not.toHaveBeenCalled();
        });
    });

    describe("move dry run", () => {
        let moveDryRunArtifact: SpyInstance, moveArtifact: SpyInstance;

        beforeEach(() => {
            moveDryRunArtifact = vi.spyOn(rest_querier, "moveDryRunArtifact");
            moveArtifact = vi.spyOn(rest_querier, "moveArtifact");

            useSelectorsStore().saveSelectedTrackerId(tracker_id);
        });

        it("When I process move in Dry run, if at least one field has en error, I store dry run has been processed in store", async () => {
            const fields: DryRunState = {
                fields_not_migrated: [
                    {
                        field_id: 10,
                        label: "Not migrated",
                        name: "not_migrated",
                    },
                ],
                fields_partially_migrated: [
                    {
                        field_id: 11,
                        label: "Partially migrated",
                        name: "partially_migrated",
                    },
                ],
                fields_migrated: [
                    {
                        field_id: 12,
                        label: "Fully migrated",
                        name: "fully_migrated",
                    },
                ],
            };

            moveDryRunArtifact.mockReturnValue(
                okAsync({
                    dry_run: { fields },
                })
            );

            const modal_store = useModalStore();
            const dry_run_store = useDryRunStore();

            vi.spyOn(modal_store, "startProcessingMove");
            vi.spyOn(modal_store, "stopProcessingMove");

            await modal_store.moveDryRun(artifact_id);

            expect(modal_store.startProcessingMove).toHaveBeenCalledOnce();
            expect(modal_store.stopProcessingMove).toHaveBeenCalledOnce();

            expect(dry_run_store.has_processed_dry_run).toBe(true);
            expect(dry_run_store.fields_not_migrated).toStrictEqual(fields.fields_not_migrated);
            expect(dry_run_store.fields_partially_migrated).toStrictEqual(
                fields.fields_partially_migrated
            );
            expect(dry_run_store.fields_migrated).toStrictEqual(fields.fields_migrated);

            expect(redirectTo).not.toHaveBeenCalled();
        });

        it("Given that there are no fields can be moved or partially moved, then the move should be blocked", async () => {
            const fields = {
                fields_not_migrated: [
                    {
                        field_id: 10,
                        label: "Not migrated",
                        name: "not_migrated",
                    },
                ],
                fields_partially_migrated: [],
                fields_migrated: [],
            };

            moveDryRunArtifact.mockReturnValue(
                okAsync({
                    dry_run: { fields },
                })
            );

            const modal_store = useModalStore();
            const dry_run_store = useDryRunStore();

            vi.spyOn(modal_store, "startProcessingMove");
            vi.spyOn(modal_store, "stopProcessingMove");

            await modal_store.moveDryRun(artifact_id);

            expect(modal_store.startProcessingMove).toHaveBeenCalledOnce();
            expect(modal_store.stopProcessingMove).toHaveBeenCalledOnce();

            expect(dry_run_store.has_processed_dry_run).toBe(true);
            expect(dry_run_store.fields_not_migrated).toStrictEqual(fields.fields_not_migrated);
            expect(dry_run_store.fields_partially_migrated).toStrictEqual(
                fields.fields_partially_migrated
            );
            expect(dry_run_store.fields_migrated).toStrictEqual(fields.fields_migrated);

            expect(dry_run_store.is_move_possible).toBe(false);

            expect(redirectTo).not.toHaveBeenCalled();
        });

        it("When I process move in Dry run, if all field can be migrated, I process the move", async () => {
            const return_json = {
                dry_run: {
                    fields: {
                        fields_not_migrated: [],
                        fields_partially_migrated: [],
                        fields_migrated: [
                            {
                                field_id: 12,
                                label: "Fully migrated",
                                name: "fully_migrated",
                            },
                        ],
                    },
                },
            };

            moveDryRunArtifact.mockReturnValue(okAsync(return_json));
            moveArtifact.mockReturnValue(okAsync({}));

            const modal_store = useModalStore();

            vi.spyOn(modal_store, "startProcessingMove");
            vi.spyOn(modal_store, "stopProcessingMove");

            await modal_store.moveDryRun(artifact_id);

            expect(modal_store.startProcessingMove).toHaveBeenCalled();
            expect(modal_store.stopProcessingMove).toHaveBeenCalled();
            expect(moveArtifact).toHaveBeenCalledWith(artifact_id, tracker_id);
            expect(redirectTo).toHaveBeenCalledWith(`/plugins/tracker/?aid=${artifact_id}`);
        });
    });
});
