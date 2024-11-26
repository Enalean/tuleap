/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
import { okAsync, errAsync } from "neverthrow";
import type { HostElement } from "./AsyncCrossReference";
import {
    CALLBACK_EXECUTION_DELAY_IN_MS,
    createAsyncCrossReference,
    observeTextChange,
    TAG,
} from "./AsyncCrossReference";
import * as fetch_reference from "./fetch-reference";
import { createLocalDocument } from "../../../helpers";
import { displayFullyLoaded } from "./display-state";
import { Fault } from "@tuleap/fault";

const project_id = 120;

describe("AsyncCrossReference", () => {
    let doc: Document;

    beforeEach(() => {
        doc = createLocalDocument();
    });

    const getHost = (): HostElement =>
        Object.assign(doc.createElement("span"), {
            text: "art #123",
            timeout_id: undefined,
            project_id,
        }) as HostElement;

    it("createAsyncCrossReference should create an AsyncCrossReference", () => {
        const text = "art #123",
            project_id = 120;
        const element = createAsyncCrossReference(text, project_id);

        expect(element.tagName.toLowerCase()).toBe(TAG);
        expect(element.text).toBe(text);
        expect(element.project_id).toBe(project_id);
    });

    describe("observeTextChange", () => {
        beforeEach(() => {
            vi.useFakeTimers();
        });

        it(`Should fetch the references in the text after waiting the user to stop typing`, () => {
            const host = getHost();
            const fetchReferencesInText = vi.spyOn(fetch_reference, "fetchReferencesInText");

            observeTextChange(host, "art #1234");

            vi.advanceTimersByTime(CALLBACK_EXECUTION_DELAY_IN_MS - 1);
            expect(fetchReferencesInText).not.toHaveBeenCalled();

            vi.advanceTimersByTime(1);
            expect(fetchReferencesInText).toHaveBeenCalledOnce();
        });

        it(`Should not fetch the references in the text while the user is still typing`, () => {
            const host = getHost();
            const fetchReferencesInText = vi.spyOn(fetch_reference, "fetchReferencesInText");

            ["art #123", "ar #123", "a #123", " #123", "d #123", "do #123", "doc #123"].forEach(
                (text) => {
                    observeTextChange(host, text);
                },
            );

            vi.advanceTimersByTime(CALLBACK_EXECUTION_DELAY_IN_MS);
            expect(fetchReferencesInText).toHaveBeenCalledOnce();
            expect(fetchReferencesInText).toHaveBeenCalledWith("doc #123", project_id);
        });

        it("When a reference has been found server-side, then it should set the data-href attribute and add the .cross-reference class", async () => {
            vi.spyOn(fetch_reference, "fetchReferencesInText").mockReturnValue(
                okAsync([{ link: "https://example.com/" }]),
            );
            const host = getHost();

            observeTextChange(host, "art #456");
            await vi.runAllTimersAsync();

            expect(host.dataset.href).toBe("https://example.com/");
            expect(Array.from(host.classList)).toStrictEqual(["cross-reference"]);
        });

        it.each([
            ["When no reference have been found server-side", okAsync([])],
            ["When an error has occurred", errAsync(Fault.fromMessage("Woops"))],
        ])(
            "%s, then it should reset the data-href attribute and remove the .cross-reference class",
            async (when, backend_return_value) => {
                vi.spyOn(fetch_reference, "fetchReferencesInText").mockReturnValue(
                    backend_return_value,
                );
                const host = getHost();
                displayFullyLoaded(host);
                host.dataset.href = "https://example.com/";

                observeTextChange(host, "art #456");
                await vi.runAllTimersAsync();

                expect(host.dataset.href).toBeUndefined();
                expect(host.classList.length).toBe(0);
            },
        );
    });
});
