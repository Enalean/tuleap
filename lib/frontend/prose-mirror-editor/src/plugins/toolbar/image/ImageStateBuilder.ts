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

import type { Selection } from "prosemirror-state";
import type { CheckCanInsertImage } from "./CanInsertImageChecker";
import type { ExtractImageFromSelection } from "./ImageFromSelectionExtractor";
import { ImageState } from "./ImageState";

export type BuildImageState = {
    build(selection: Selection): ImageState;
};

export const ImageStateBuilder = (
    check_can_insert_image: CheckCanInsertImage,
    extract_image_from_selection: ExtractImageFromSelection,
): BuildImageState => ({
    build: (selection): ImageState => {
        if (!check_can_insert_image.canInsertImage(selection.$from)) {
            return ImageState.disabled();
        }

        const image = extract_image_from_selection.extractImageProperties(selection);
        if (image) {
            return ImageState.forImageEdition(image);
        }

        return ImageState.forImageInsertion();
    },
});
