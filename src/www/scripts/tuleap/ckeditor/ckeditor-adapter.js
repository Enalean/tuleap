/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import { getGettextProvider } from "./gettext-factory.js";

export function getUsedUploadedImagesURLs(ckeditor_instance) {
    return Array.from(
        new DOMParser()
            .parseFromString(ckeditor_instance.getData(), "text/html")
            .querySelectorAll("img")
    ).map(img => img.getAttribute("src"));
}

export function disablePasteOfImages(ckeditor_instance) {
    ckeditor_instance.on("paste", event => {
        const doc = new DOMParser().parseFromString(event.data.dataValue, "text/html");
        for (const img of doc.querySelectorAll("img")) {
            if (img.src.match(/^data:/i)) {
                event.data.dataValue = "";
                event.cancel();
                ckeditor_instance.showNotification(
                    getGettextProvider().gettext("You are not allowed to paste images here"),
                    "warning"
                );
                return;
            }
        }
    });
}
