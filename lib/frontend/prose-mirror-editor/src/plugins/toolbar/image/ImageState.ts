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

import type { ImageProperties } from "../../../types/internal-types";

export type ImageState = {
    readonly is_activated: boolean;
    readonly is_disabled: boolean;
    readonly image_src: string;
    readonly image_title: string;
};

export const ImageState = {
    disabled: (): ImageState => ({
        is_activated: false,
        is_disabled: true,
        image_src: "",
        image_title: "",
    }),
    forImageEdition: (image: ImageProperties): ImageState => ({
        is_activated: true,
        is_disabled: false,
        image_src: image.src,
        image_title: image.title,
    }),
    forImageInsertion: (): ImageState => ({
        is_activated: false,
        is_disabled: false,
        image_src: "",
        image_title: "",
    }),
};
