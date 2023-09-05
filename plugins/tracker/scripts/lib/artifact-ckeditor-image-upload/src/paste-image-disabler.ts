/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { isThereAnImageWithDataURI } from "@tuleap/ckeditor-image-upload";
import type CKEDITOR from "ckeditor4";
import type { GettextProvider } from "@tuleap/gettext";

export function disablePasteOfImages(
    ckeditor_instance: CKEDITOR.editor,
    gettext_provider: GettextProvider,
): void {
    ckeditor_instance.on("paste", (event) => {
        if (isThereAnImageWithDataURI(event.data.dataValue)) {
            event.data.dataValue = "";
            event.cancel();
            ckeditor_instance.showNotification(
                gettext_provider.gettext("You are not allowed to paste images here"),
                "warning",
                0,
            );
        }
    });
}
