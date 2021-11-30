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

import type { IMediaTransformation } from "docx";

/**
 * The max values are selected to make sure that:
 * - an image never take more than 1/3 of a page vertically
 * - an image is always visible completely horizontally
 *
 * We hardcode the fact we are working in a ISO216 A4 (21 cm x 29.7cm) format with 1 inch (2.54 cm) margin on each side.
 *
 * In OOXML drawing sizes are defined in EMUs. 1 inch = 914400 EMUs (1 cm = (914400 / 2.54) EMUs = 360000 EMUs) [0].
 * We are also supposing the DPI is 96 like the docx library does [1] to do the conversion from pixels
 *
 *
 * [0] https://startbigthinksmall.wordpress.com/2010/01/04/points-inches-and-emus-measuring-units-in-office-open-xml/
 * [1] https://github.com/dolanmiu/docx/blob/7.1.1/src/file/paragraph/run/image-run.ts#L33-L34 (914400 EMUs / 96 dpi = 9525)
 */
const MAX_HEIGHT_CM = (29.7 - 2 * 2.54) / 3;
const MAX_WIDTH_CM = 21 - 2 * 2.54;
const MAX_HEIGHT_PX = (MAX_HEIGHT_CM * 360000) / (914400 / 96);
const MAX_WIDTH_PX = (MAX_WIDTH_CM * 360000) / (914400 / 96);

export function computeTransformation(image: HTMLImageElement): IMediaTransformation {
    const image_size_width = image.naturalWidth;
    const image_size_height = image.naturalHeight;

    const scale_width = MAX_WIDTH_PX / image_size_width;
    const scale_height = MAX_HEIGHT_PX / image_size_height;

    const scale = Math.min(scale_width, scale_height, 1);

    return {
        width: Math.round(image_size_width * scale),
        height: Math.round(image_size_height * scale),
    };
}
