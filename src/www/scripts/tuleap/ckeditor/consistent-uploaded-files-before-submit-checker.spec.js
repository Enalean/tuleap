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

import { addInstance } from "./consistent-uploaded-files-before-submit-checker.js";
import { rewire$findAllHiddenInputByNames, restore as restoreForm } from "./form-adapter.js";
import {
    rewire$getUsedUploadedImagesURLs,
    restore as restoreCKEditor
} from "./ckeditor-adapter.js";

describe(`consistent-uploaded-files-before-submit-checker`, () => {
    let findAllHiddenInputByNames, getUsedUploadedImagesURLs;

    beforeEach(() => {
        findAllHiddenInputByNames = jasmine
            .createSpy("findAllHiddenInputByNames")
            .and.returnValue([]);
        rewire$findAllHiddenInputByNames(findAllHiddenInputByNames);
        getUsedUploadedImagesURLs = jasmine
            .createSpy("getUsedUploadedImagesURLs")
            .and.returnValue([]);
        rewire$getUsedUploadedImagesURLs(getUsedUploadedImagesURLs);
    });

    afterEach(() => {
        restoreForm();
        restoreCKEditor();
    });

    describe(`addInstance()`, () => {
        let form, triggerFormSubmit;
        const ckeditor_instance = {};
        const field_name = "Attachments";

        beforeEach(() => {
            form = {
                addEventListener: jasmine
                    .createSpy("addEventListener")
                    .and.callFake((event_name, handler) => {
                        triggerFormSubmit = handler.bind(form);
                    })
            };
        });

        it(`removes all hidden inputs in the form that don't have a matching
            <img> tag in any ckeditor instance`, () => {
            const file_input = {
                dataset: { url: "https://example.com/advertently.jpg" },
                parentNode: {
                    removeChild: jasmine.createSpy("parentNode.removeChild")
                }
            };
            const unused_file_input = {
                dataset: { url: "http://example.com/hypersystole.png" },
                parentNode: {
                    removeChild: jasmine.createSpy("parentNode.removeChild")
                }
            };
            findAllHiddenInputByNames.and.returnValue([file_input, unused_file_input]);
            getUsedUploadedImagesURLs.and.returnValue(["https://example.com/advertently.jpg"]);

            addInstance(form, ckeditor_instance, field_name);
            triggerFormSubmit();

            expect(file_input.parentNode.removeChild).not.toHaveBeenCalled();
            expect(unused_file_input.parentNode.removeChild).toHaveBeenCalled();
        });

        it(`takes into account hidden inputs from multiple forms`, () => {
            let triggerSecondFormSubmit;
            const other_form = {
                addEventListener: jasmine
                    .createSpy("addEventListener")
                    .and.callFake((event_name, handler) => {
                        triggerSecondFormSubmit = handler.bind(other_form);
                    })
            };

            addInstance(form, ckeditor_instance, field_name);
            addInstance(other_form, ckeditor_instance, field_name);
            triggerFormSubmit();
            triggerSecondFormSubmit();

            expect(form.addEventListener).toHaveBeenCalled();
            expect(other_form.addEventListener).toHaveBeenCalled();

            expect(findAllHiddenInputByNames.calls.count()).toBe(2);
        });

        it(`takes into account images from multiple CKEditor instances`, () => {
            const other_ckeditor_instance = {};
            const other_field_name = "anisopodal";

            addInstance(form, ckeditor_instance, field_name);
            addInstance(form, other_ckeditor_instance, other_field_name);
            triggerFormSubmit();

            expect(getUsedUploadedImagesURLs.calls.count()).toBe(2);
        });
    });
});
