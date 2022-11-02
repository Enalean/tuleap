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

import * as image_upload from "@tuleap/ckeditor-image-upload";
import { disablePasteOfImages } from "./paste-image-disabler";
import type { GettextProvider } from "@tuleap/gettext";

type CKEditorEventHandler = (event: CKEDITOR.eventInfo) => void;

const noop = (): void => {
    //Do nothing
};

describe(`disablePasteOfImages()`, () => {
    it("will disable paste of images and show a notification", () => {
        let triggerPaste: CKEditorEventHandler = jest.fn();
        const ckeditor_instance = {
            on(event_name: string, handler: CKEditorEventHandler) {
                triggerPaste = handler;
            },
            showNotification: noop,
            getData() {
                return "data";
            },
        } as unknown as CKEDITOR.editor;

        jest.spyOn(image_upload, "isThereAnImageWithDataURI").mockReturnValue(true);
        const showNotification = jest.spyOn(ckeditor_instance, "showNotification");

        const gettext_provider = {
            gettext: (english: string) => english,
        } as GettextProvider;

        disablePasteOfImages(ckeditor_instance, gettext_provider);

        const event = {
            cancel: noop,
            data: { dataValue: `<p></p>` },
        } as CKEDITOR.eventInfo;

        const cancelEvent = jest.spyOn(event, "cancel");

        triggerPaste(event);

        expect(cancelEvent).toHaveBeenCalled();
        expect(event.data.dataValue).toBe("");
        expect(showNotification).toHaveBeenCalled();
    });
});
