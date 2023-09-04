/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
import { useDefaultAvatar } from "./use-default";
import { getPreviewContainer } from "./preview-container";

export function loadAvatarPreview(): void {
    const input_file = document.getElementById("account-information-avatar-modal-select-file");
    if (!(input_file instanceof HTMLInputElement)) {
        throw Error(
            "#account-information-avatar-modal-select-file not found or is not an input element",
        );
    }

    input_file.addEventListener("change", function () {
        if (!input_file.files) {
            return;
        }

        const url = URL.createObjectURL(input_file.files[0]);
        useImageInPreviewIfItIsValid(url);
    });
}

function useImageInPreviewIfItIsValid(url: string): void {
    const img = document.createElement("img");

    img.onload = function (): void {
        const preview = getPreviewContainer();
        const resized_image_url = getResizedImageUrl(url);
        setAvatarPreviewUrl(resized_image_url);
        preview.classList.add("account-information-avatar-modal-preview");
        useDefaultAvatar("0");
    };
    img.src = url;
}

function getResizedImageUrl(url: string): string {
    const tmp_img = document.createElement("img");
    tmp_img.src = url;

    const canvas = document.createElement("canvas"),
        max_size = 100,
        width = tmp_img.width,
        height = tmp_img.height;

    let source_x = 0,
        source_y = 0,
        source_width = width,
        source_height = height;

    if (width > max_size || height > max_size) {
        const size = Math.min(width, height);
        source_x = Math.round((width - size) / 2);
        source_y = Math.round((height - size) / 2);
        source_width = size;
        source_height = size;
    }

    canvas.width = max_size;
    canvas.height = max_size;
    const ctx = canvas.getContext("2d");
    if (ctx === null) {
        throw Error("Could not create canvas for avatar preview");
    }

    ctx.drawImage(
        tmp_img,
        source_x,
        source_y,
        source_width,
        source_height,
        0,
        0,
        max_size,
        max_size,
    );

    return canvas.toDataURL("image/png");
}

function setAvatarPreviewUrl(url: string): void {
    let image = document.querySelector("#account-information-avatar-modal-preview > img");
    if (image === null) {
        image = document.createElement("img");
        getPreviewContainer().appendChild(image);
    }
    if (!(image instanceof HTMLImageElement)) {
        throw new Error("Should be an image");
    }

    image.src = url;
}
