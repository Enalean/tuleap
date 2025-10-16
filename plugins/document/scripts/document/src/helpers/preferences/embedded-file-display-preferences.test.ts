/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { RootState } from "../../type";
import { EMBEDDED_FILE_DISPLAY_LARGE, EMBEDDED_FILE_DISPLAY_NARROW } from "../../type";
import type { ActionContext } from "vuex";
import * as preferencies_rest from "../../api/preferences-rest-querier";
import {
    displayEmbeddedInLargeMode,
    displayEmbeddedInNarrowMode,
    getEmbeddedFileDisplayPreference,
} from "./embedded-file-display-preferences";
import { ItemBuilder } from "../../../tests/builders/ItemBuilder";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { TYPE_EMBEDDED } from "../../constants";

describe("embedded-file-display-preferences", () => {
    let context: ActionContext<RootState, RootState>;

    beforeEach(() => {
        context = {
            dispatch: vi.fn(),
        } as unknown as ActionContext<RootState, RootState>;
    });

    describe("getEmbeddedFileDisplayPreference", () => {
        it("should return user preference", async () => {
            const getPreference = vi
                .spyOn(preferencies_rest, "getPreferenceForEmbeddedDisplay")
                .mockReturnValue(okAsync(EMBEDDED_FILE_DISPLAY_NARROW));

            const result = await getEmbeddedFileDisplayPreference(
                context,
                new ItemBuilder(123).build(),
                102,
                101,
            );

            expect(getPreference).toHaveBeenCalledWith(102, 101, 123);
            expect(result.isValue()).toBe(true);
            expect(result.unwrapOr(null)).toStrictEqual(EMBEDDED_FILE_DISPLAY_NARROW);
        });

        it("should dispatch error on api fail", async () => {
            const fault = Fault.fromMessage("Oh no!");
            const getPreference = vi
                .spyOn(preferencies_rest, "getPreferenceForEmbeddedDisplay")
                .mockReturnValue(errAsync(fault));

            const result = await getEmbeddedFileDisplayPreference(
                context,
                new ItemBuilder(123).build(),
                102,
                101,
            );

            expect(getPreference).toHaveBeenCalledWith(102, 101, 123);
            expect(result.isNothing()).toBe(true);
            expect(context.dispatch).toHaveBeenCalledWith("error/handleErrors", fault);
        });
    });

    describe("displayEmbeddedInLargeMode", () => {
        beforeEach(() => {
            vi.spyOn(preferencies_rest, "removeUserPreferenceForEmbeddedDisplay").mockReturnValue(
                okAsync(EMBEDDED_FILE_DISPLAY_LARGE),
            );
        });

        it("should store in user preferences the new mode and then update the store value", async () => {
            const result = await displayEmbeddedInLargeMode(
                context,
                new ItemBuilder(123).withTitle("My embedded").withType(TYPE_EMBEDDED).build(),
                254,
                101,
            );

            expect(result.isValue()).toBe(true);
        });
    });

    describe("displayEmbeddedInNarrowMode", () => {
        beforeEach(() => {
            vi.spyOn(preferencies_rest, "setNarrowModeForEmbeddedDisplay").mockReturnValue(
                okAsync(EMBEDDED_FILE_DISPLAY_NARROW),
            );
        });

        it("should store in user preferences the new mode and then update the store value", async () => {
            const result = await displayEmbeddedInNarrowMode(
                context,
                new ItemBuilder(123).withTitle("My embedded").withType(TYPE_EMBEDDED).build(),
                254,
                101,
            );

            expect(result.isValue()).toBe(true);
        });
    });
});
