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

import { ImageRun } from "docx";
import { get } from "@tuleap/tlp-fetch";
import { computeTransformation } from "./image-transformation";

export async function loadImage(image_url: string): Promise<ImageRun> {
    const response = await get(image_url);
    const response_blob = await response.blob();
    const image_blob_url = URL.createObjectURL(response_blob);
    const image = new Image();
    image.src = image_blob_url;

    await image.decode();

    URL.revokeObjectURL(image_blob_url);

    return new ImageRun({
        data: await response_blob.arrayBuffer(),
        transformation: computeTransformation(image),
    });
}
