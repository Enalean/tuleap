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

import { describe, it, expect } from "vitest";
import type { Selection } from "prosemirror-state";
import { ImageStateBuilder } from "./ImageStateBuilder";
import { CheckCanInsertImageStub } from "./stubs/CheckCanInsertImage";
import { ExtractImageFromSelectionStub } from "./stubs/ExtractImageFromSelectionStub";
import { ImageState } from "./ImageState";

const selection = { $from: { pos: 1 } } as Selection;

describe("ImageStateBuilder", () => {
    it("When an image cannot be inserted, then it should return a disabled state", () => {
        const state = ImageStateBuilder(
            CheckCanInsertImageStub.withInsertionForbidden(),
            ExtractImageFromSelectionStub.withNothing(),
        ).build(selection);

        expect(state).toStrictEqual(ImageState.disabled());
    });

    it("When an image is being selected, then it should return a state for an image edition", () => {
        const image = {
            src: "https://example.com",
            title: "An example image",
        };
        const state = ImageStateBuilder(
            CheckCanInsertImageStub.withInsertionAllowed(),
            ExtractImageFromSelectionStub.withImageProperties(image),
        ).build(selection);

        expect(state).toStrictEqual(ImageState.forImageEdition(image));
    });

    it("When no image is being selected, then it should return a state for an image insertion", () => {
        const state = ImageStateBuilder(
            CheckCanInsertImageStub.withInsertionAllowed(),
            ExtractImageFromSelectionStub.withNothing(),
        ).build(selection);

        expect(state).toStrictEqual(ImageState.forImageInsertion());
    });
});
