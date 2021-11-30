/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { computeTransformation } from "./image-transformation";

describe("image-transformation", () => {
    it("keeps the image size as is when the image is small", () => {
        const transformation = computeTransformation(buildHTMLImageElement(100, 100));

        expect(transformation).toStrictEqual({ width: 100, height: 100 });
    });

    it("resizes images with a large width", () => {
        const transformation = computeTransformation(buildHTMLImageElement(10000, 100));

        expect(transformation).toStrictEqual({ width: 602, height: 6 });
    });

    it("resizes images with a large height", () => {
        const transformation = computeTransformation(buildHTMLImageElement(100, 10000));

        expect(transformation).toStrictEqual({ width: 3, height: 310 });
    });
});

function buildHTMLImageElement(width: number, height: number): HTMLImageElement {
    return {
        naturalWidth: width,
        naturalHeight: height,
    } as HTMLImageElement;
}
