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

import { describe, it, vi, expect } from "vitest";
import { InjectedSectionsStoreStub } from "@/helpers/stubs/InjectSectionsStoreStub";
import type { Tracker } from "@/stores/configuration-store";
import { initConfigurationStore } from "@/stores/configuration-store";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
import * as rest from "@/helpers/rest-querier";
import { errAsync, okAsync } from "neverthrow";
import { flushPromises } from "@vue/test-utils";
import { Fault } from "@tuleap/fault";

describe("configuration-store", () => {
    describe("saveConfiguration", () => {
        it("should save the new configuration", async () => {
            const insert = vi.fn();
            vi.spyOn(rest, "putConfiguration").mockReturnValue(okAsync(new Response()));

            const sections =
                InjectedSectionsStoreStub.withMockedInsertPendingArtifactSectionForEmptyDocument(
                    insert,
                );

            const bugs: Tracker = TrackerStub.build(101, "Bugs");
            const tasks: Tracker = TrackerStub.build(102, "Tasks");

            const store = initConfigurationStore(1, null, [bugs, tasks], sections);

            expect(store.selected_tracker.value).toStrictEqual(null);

            store.saveConfiguration(bugs);
            await flushPromises();

            expect(store.selected_tracker.value).toStrictEqual(bugs);
            expect(insert).toHaveBeenCalled();
            expect(store.is_success.value).toBe(true);
            expect(store.is_error.value).toBe(false);
        });

        it("should display the error", async () => {
            const insert = vi.fn();
            vi.spyOn(rest, "putConfiguration").mockReturnValue(
                errAsync(Fault.fromMessage("Bad request")),
            );

            const sections =
                InjectedSectionsStoreStub.withMockedInsertPendingArtifactSectionForEmptyDocument(
                    insert,
                );

            const bugs: Tracker = TrackerStub.build(101, "Bugs");
            const tasks: Tracker = TrackerStub.build(102, "Tasks");

            const store = initConfigurationStore(1, null, [bugs, tasks], sections);

            expect(store.selected_tracker.value).toStrictEqual(null);

            store.saveConfiguration(bugs);
            await flushPromises();

            expect(store.selected_tracker.value).toStrictEqual(null);
            expect(insert).not.toHaveBeenCalled();
            expect(store.is_success.value).toBe(false);
            expect(store.is_error.value).toBe(true);
            expect(store.error_message.value).toBe("Bad request");
        });
    });

    describe("resetSuccessFlagFromPreviousCalls", () => {
        it("should put the success flag to false", () => {
            const store = initConfigurationStore(
                1,
                null,
                [],
                InjectedSectionsStoreStub.withLoadedSections([]),
            );
            store.is_success.value = true;

            store.resetSuccessFlagFromPreviousCalls();

            expect(store.is_success.value).toBe(false);
        });
    });
});
