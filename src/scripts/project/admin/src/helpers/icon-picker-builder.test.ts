/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import { buildIconPicker } from "./icon-picker-builder";
import type { GetText } from "@tuleap/gettext";
import * as icon_picker from "@picmo/popup-picker";
import type { PopupPickerController } from "@picmo/popup-picker";

describe("icon-picker-builder", () => {
    let doc: Document;
    let gettext_provider: GetText;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        gettext_provider = {
            gettext(value: string): string {
                return value;
            },
        } as GetText;
    });
    it("returns null if there is no mount point for the emoji picker", () => {
        const mount_point = doc.createElement("div");
        mount_point.id = "not-icon-picker-div";

        doc.body.append(mount_point);

        const picker = buildIconPicker(gettext_provider, doc);

        expect(picker).toBeNull();
    });

    it("returns null if the mount point does not contains any value", () => {
        const mount_point = doc.createElement("div");
        mount_point.id = "form-group-name-icon-input-container";

        doc.body.append(mount_point);

        const picker = buildIconPicker(gettext_provider, doc);

        expect(picker).toBeNull();
    });

    it("returns null if the mount point does not receive any emoji", () => {
        const mount_point = doc.createElement("div");
        mount_point.id = "form-group-name-icon-input-container";
        mount_point.dataset.allProjectIcons = "";

        doc.body.append(mount_point);
        const picker = buildIconPicker(gettext_provider, doc);

        expect(picker).toBeNull();
    });

    it("returns the emoji picker if the mount point receive the allowed project emoji", () => {
        const mount_point = doc.createElement("div");
        mount_point.id = "form-group-name-icon-input-container";
        mount_point.dataset.allProjectIcons = `{"categories":["smileys"],"emoji":[{"emoji":"😀","category":0,"name":"grinning face","version":"1.0"}]}`;

        doc.body.append(mount_point);

        const input = doc.createElement("div");
        input.id = "icon-input";
        doc.body.append(input);

        jest.spyOn(icon_picker, "createPopup").mockReturnValue({} as PopupPickerController);

        const picker = buildIconPicker(gettext_provider, doc);

        expect(picker).not.toBeNull();
    });
});
