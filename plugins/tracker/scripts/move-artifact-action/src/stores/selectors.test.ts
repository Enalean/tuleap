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
import type { SpyInstance } from "vitest";
import { setActivePinia, createPinia } from "pinia";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import * as rest_querier from "../api/rest-querier";
import type { Tracker, Project } from "../api/types";
import { useSelectorsStore } from "./selectors";
import { useDryRunStore } from "./dry-run";
import { useModalStore } from "./modal";

const selected_project_id = 104;

describe("selectors store", () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it("saveSelectedProjectId() should save the selected project id", () => {
        const selectors_store = useSelectorsStore();
        const project_id = 104;

        expect(selectors_store.selected_project_id).toBeNull();

        selectors_store.saveSelectedProjectId(project_id);
        expect(selectors_store.selected_project_id).toBe(project_id);
    });

    it("saveSelectedTrackerId() should save the selected project id", () => {
        const selectors_store = useSelectorsStore();
        const dry_run_store = useDryRunStore();
        const tracker_id = 14;

        vi.spyOn(dry_run_store, "$reset");

        expect(selectors_store.selected_tracker_id).toBeNull();

        selectors_store.saveSelectedTrackerId(tracker_id);

        expect(selectors_store.selected_tracker_id).toBe(tracker_id);
        expect(dry_run_store.$reset).toHaveBeenCalledOnce();
    });

    describe("loadTrackerList()", () => {
        let getTrackerList: SpyInstance;

        beforeEach(() => {
            getTrackerList = vi.spyOn(rest_querier, "getTrackerList");
        });

        it("should reset the dry run store, the error message, load the trackers and store them", async () => {
            const trackers: Tracker[] = [
                {
                    id: 10,
                    label: "Tracker name",
                },
            ];

            getTrackerList.mockReturnValue(okAsync(trackers));

            const selectors_store = useSelectorsStore();
            const dry_run_store = useDryRunStore();
            const modal_store = useModalStore();

            modal_store.$patch({ error_message: "Oh snap!" });

            vi.spyOn(selectors_store, "startLoadingTrackers");
            vi.spyOn(selectors_store, "stopLoadingTrackers");
            vi.spyOn(dry_run_store, "$reset");

            await selectors_store.loadTrackerList(selected_project_id);

            expect(dry_run_store.$reset).toHaveBeenCalledOnce();
            expect(selectors_store.startLoadingTrackers).toHaveBeenCalledOnce();
            expect(selectors_store.stopLoadingTrackers).toHaveBeenCalledOnce();
            expect(selectors_store.trackers).toStrictEqual(trackers);
            expect(modal_store.error_message).toBe("");
        });

        it("When the server responds with an error, then error message is stored", async () => {
            const api_error = "Oh snap!";
            getTrackerList.mockReturnValue(errAsync(Fault.fromMessage(api_error)));

            await useSelectorsStore().loadTrackerList(selected_project_id);
            expect(useModalStore().error_message).toBe(api_error);
        });
    });

    describe("loadProjectList()", () => {
        let getProjectList: SpyInstance;

        beforeEach(() => {
            getProjectList = vi.spyOn(rest_querier, "getProjectList");
        });

        it("Should load the projects, Then store them in alphabetical order.", async () => {
            const projects: Project[] = [
                {
                    id: 155,
                    label: "Otters",
                },
                {
                    id: 102,
                    label: "Hamsters",
                },
                {
                    id: 113,
                    label: "Guinea Pig",
                },
            ];

            getProjectList.mockReturnValue(okAsync(projects));

            const selectors_store = useSelectorsStore();

            vi.spyOn(selectors_store, "startLoadingProjects");
            vi.spyOn(selectors_store, "startLoadingProjects");

            await selectors_store.loadProjectList();

            expect(selectors_store.startLoadingProjects).toHaveBeenCalledOnce();
            expect(selectors_store.startLoadingProjects).toHaveBeenCalledOnce();

            expect(selectors_store.projects.map(({ id }) => id)).toStrictEqual([113, 102, 155]);
        });

        it("When the server responds with an error the error message is stored", async () => {
            const api_error = "Oh snap!";
            getProjectList.mockReturnValue(errAsync(Fault.fromMessage(api_error)));

            await useSelectorsStore().loadProjectList();
            expect(useModalStore().error_message).toBe(api_error);
        });
    });
});
