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

import { rewire$getGettextProvider, restore as restoreGettext } from "./gettext-factory.js";
import { disablePasteOfImages, getUsedUploadedImagesURLs } from "./ckeditor-adapter.js";

describe(`ckeditor-adapter`, () => {
    beforeEach(() => {
        const gettext_provider = jasmine.createSpyObj("gettext_provider", ["gettext"]);
        rewire$getGettextProvider(() => gettext_provider);
    });

    afterEach(() => {
        restoreGettext();
    });

    describe(`getUsedUploadedImagesURLs()`, () => {
        it(`returns an array of URLs extracted from the [src] attribute
            of all the <img> tags in CKEditor's data`, () => {
            const first_url = "https://example.com/unaccountability/advertently.jpg";
            const second_url = "http://example.com/sightlessly/hypersystole.png";
            const ckeditor_instance = {
                getData: jasmine.createSpy("getData").and.returnValue(`<p>
                <img src="${first_url}">
                <img src="${second_url}">
            </p>`)
            };

            const result = getUsedUploadedImagesURLs(ckeditor_instance);

            expect(result).toEqual([first_url, second_url]);
        });

        it(`given there isn't any image in CKEditor's data,
            it returns an empty array`, () => {
            const ckeditor_instance = {
                getData: jasmine.createSpy("getData").and.returnValue(`<p></p>`)
            };

            const result = getUsedUploadedImagesURLs(ckeditor_instance);

            expect(result).toEqual([]);
        });
    });

    describe(`disablePasteOfImages()`, () => {
        let triggerPaste, ckeditor_instance;

        beforeEach(() => {
            ckeditor_instance = jasmine.createSpyObj("ckeditor_instance", [
                "on",
                "showNotification"
            ]);
            ckeditor_instance.on.and.callFake((event_name, handler) => {
                triggerPaste = handler;
            });
            disablePasteOfImages(ckeditor_instance);
        });

        it(`when there is a "paste" event on ckeditor and it contains an <img> tag
            with a [src] attribute with base-64 encoded image,
            then it stops the event and shows a notification to the user`, () => {
            const paste_event = {
                data: {
                    dataValue: `<p><img src="data:RWx2aXJh"></p>`
                },
                cancel: jasmine.createSpy("cancel")
            };
            triggerPaste(paste_event);

            expect(paste_event.cancel).toHaveBeenCalled();
            expect(ckeditor_instance.showNotification).toHaveBeenCalled();
        });

        it(`when there is a "paste" event on ckeditor and the image's [src] attribute
            is not a base-64 encoded image,
            then it does not stop the event`, () => {
            const paste_event = {
                data: {
                    dataValue: `<p><img src="http://example.com/sightlessly/hypersystole.png"></p>`
                },
                cancel: jasmine.createSpy("cancel")
            };
            triggerPaste(paste_event);

            expect(paste_event.cancel).not.toHaveBeenCalled();
            expect(ckeditor_instance.showNotification).not.toHaveBeenCalled();
        });
    });
});
