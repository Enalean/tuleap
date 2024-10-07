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
import { ImageState } from "./ImageState";

describe("ImageState", () => {
    it("ImageState.disabled() should return a disabled state", () => {
        expect(ImageState.disabled()).toStrictEqual({
            is_activated: false,
            is_disabled: true,
            image_src: "",
            image_title: "",
        });
    });

    it("ImageState.forImageEdition() should return a state for an image edition", () => {
        const current_image = {
            src: "https://example.com",
            title: "An example image",
        };

        expect(ImageState.forImageEdition(current_image)).toStrictEqual({
            is_activated: true,
            is_disabled: false,
            image_src: current_image.src,
            image_title: current_image.title,
        });
    });

    it("ImageState.forImageInsertion() should return a state for an image insertion", () => {
        expect(ImageState.forImageInsertion()).toStrictEqual({
            is_activated: false,
            is_disabled: false,
            image_src: "",
            image_title: "",
        });
    });
});
