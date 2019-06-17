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

import {
    rewire$isUploadEnabled,
    rewire$informUsersThatTheyCanPasteImagesInEditor,
    restore as restoreDocument
} from "./element-adapter.js";
import { rewire$initGettext, restore as restoreGettext } from "./gettext-factory.js";
import {
    rewire$buildFileUploadHandler,
    restore as restoreHandlerFactory,
    MaxSizeUploadExceededError,
    UploadError
} from "./file-upload-handler-factory.js";
import {
    rewire$addInstance,
    restore as restoreChecker
} from "./consistent-uploaded-files-before-submit-checker.js";
import {
    rewire$disableFormSubmit,
    rewire$enableFormSubmit,
    restore as restoreFormAdapter
} from "./form-adapter.js";
import { rewire$isThereAnImageWithDataURI, restore as restoreFinder } from "./image-urls-finder.js";
import { getUploadImageOptions, initiateUploadImage } from "./get-upload-image-options.js";

describe(`get-upload-image-options`, () => {
    let isUploadEnabled,
        initGettext,
        gettext_provider = {
            gettext: () => ""
        },
        informUsersThatTheyCanPasteImagesInEditor,
        addInstance,
        isThereAnImageWithDataURI,
        buildFileUploadHandler,
        disableFormSubmit,
        enableFormSubmit;

    beforeEach(() => {
        isUploadEnabled = jasmine.createSpy("isUploadEnabled").and.returnValue(true);
        rewire$isUploadEnabled(isUploadEnabled);
        informUsersThatTheyCanPasteImagesInEditor = jasmine.createSpy(
            "informUsersThatTheyCanPasteImagesInEditor"
        );
        rewire$informUsersThatTheyCanPasteImagesInEditor(informUsersThatTheyCanPasteImagesInEditor);

        addInstance = jasmine.createSpy("addInstance");
        rewire$addInstance(addInstance);

        buildFileUploadHandler = jasmine.createSpy("buildFileUploadHandler");
        rewire$buildFileUploadHandler(buildFileUploadHandler);

        isThereAnImageWithDataURI = jasmine.createSpy("isThereAnImageWithDataURI");
        rewire$isThereAnImageWithDataURI(isThereAnImageWithDataURI);

        disableFormSubmit = jasmine.createSpy("disableFormSubmit");
        enableFormSubmit = jasmine.createSpy("enableFormSubmit");
        rewire$disableFormSubmit(disableFormSubmit);
        rewire$enableFormSubmit(enableFormSubmit);

        initGettext = jasmine.createSpy("initGettext").and.returnValue(gettext_provider);
        rewire$initGettext(initGettext);
    });

    afterEach(() => {
        restoreDocument();
        restoreGettext();
        restoreChecker();
        restoreFormAdapter();
        restoreHandlerFactory();
        restoreFinder();
    });

    describe(`initiateUploadImage()`, () => {
        let ckeditor_instance;
        const options = {};

        beforeEach(() => {
            ckeditor_instance = jasmine.createSpyObj("ckeditor_instance", [
                "on",
                "showNotification"
            ]);
        });

        it(`when upload is disabled, it will disable paste of images
            and show a notification`, async () => {
            isUploadEnabled.and.returnValue(false);
            const element = {
                dataset: { uploadUrl: "https://example.com/disprobabilize/gavyuti" }
            };
            let triggerPaste;
            ckeditor_instance.on.and.callFake((event_name, handler) => {
                triggerPaste = handler;
            });
            isThereAnImageWithDataURI.and.returnValue(true);

            await initiateUploadImage(ckeditor_instance, options, element);

            const event = {
                cancel: jasmine.createSpy("event.cancel"),
                data: { dataValue: `<p></p>` }
            };
            triggerPaste(event);

            expect(event.cancel).toHaveBeenCalled();
            expect(ckeditor_instance.showNotification).toHaveBeenCalled();
        });

        describe(`when upload is enabled`, () => {
            let form, element;
            beforeEach(() => {
                form = jasmine.createSpyObj("form", ["querySelectorAll"]);
                element = {
                    dataset: {
                        uploadUrl: "https://example.com/disprobabilize/gavyuti",
                        uploadFieldName: "satrapess",
                        uploadMaxSize: "1024"
                    },
                    form
                };
            });

            it(`informs users that they can paste images`, async () => {
                await initiateUploadImage(ckeditor_instance, options, element);

                expect(informUsersThatTheyCanPasteImagesInEditor).toHaveBeenCalled();
            });

            it(`builds the file upload handler and registers it on the CKEditor instance`, async () => {
                await initiateUploadImage(ckeditor_instance, options, element);

                const expected_options = {
                    ckeditor_instance,
                    max_size_upload: 1024,
                    onStartCallback: jasmine.any(Function),
                    onErrorCallback: jasmine.any(Function),
                    onSuccessCallback: jasmine.any(Function)
                };
                expect(buildFileUploadHandler).toHaveBeenCalledWith(expected_options);
            });

            it(`when the upload starts, it disables form submits`, async () => {
                let triggerStart;
                buildFileUploadHandler.and.callFake(({ onStartCallback }) => {
                    triggerStart = onStartCallback;
                });

                await initiateUploadImage(ckeditor_instance, options, element);
                triggerStart();

                expect(disableFormSubmit).toHaveBeenCalled();
            });

            describe(`when the upload succeeds`, () => {
                let triggerSuccess;
                beforeEach(async () => {
                    buildFileUploadHandler.and.callFake(({ onSuccessCallback }) => {
                        triggerSuccess = onSuccessCallback;
                    });
                    form.appendChild = jasmine.createSpy("form.appendChild");
                    await initiateUploadImage(ckeditor_instance, options, element);
                    triggerSuccess(182, "http://example.com/scenary");
                });

                it(`appends a hidden field on the form`, () => {
                    expect(form.appendChild).toHaveBeenCalled();
                    const input = form.appendChild.calls.first().args[0];
                    expect(input.type).toEqual("hidden");
                    expect(input.name).toEqual("satrapess");
                    expect(input.value).toEqual("182");
                    expect(input.dataset.url).toEqual("http://example.com/scenary");
                });

                it(`enables back form submits`, () => {
                    expect(enableFormSubmit).toHaveBeenCalled();
                });
            });

            describe(`when the upload fails`, () => {
                let triggerError;
                beforeEach(async () => {
                    buildFileUploadHandler.and.callFake(({ onErrorCallback }) => {
                        triggerError = onErrorCallback;
                    });

                    await initiateUploadImage(ckeditor_instance, options, element);
                });

                it(`enables back form submits`, () => {
                    triggerError();
                    expect(enableFormSubmit).toHaveBeenCalled();
                });

                it(`and the max size has been exceeded,
                    then it sets the loader error message`, () => {
                    const loader = {};
                    const error = new MaxSizeUploadExceededError(300, loader);
                    triggerError(error);

                    expect(loader.message).toBeDefined();
                });

                it(`and the upload failed, then it sets the loader error message`, () => {
                    const loader = {};
                    const error = new UploadError(loader);
                    triggerError(error);

                    expect(loader.message).toBeDefined();
                });
            });

            it(`registers the CKEditor instance to clear unused uploaded files`, async () => {
                await initiateUploadImage(ckeditor_instance, options, element);

                expect(addInstance).toHaveBeenCalledWith(form, ckeditor_instance, "satrapess");
            });
        });
    });

    describe(`getUploadImageOptions()`, () => {
        let element;

        it(`when upload is disabled, it returns an empty object`, () => {
            isUploadEnabled.and.returnValue(false);

            expect(getUploadImageOptions(element)).toEqual({});
        });

        it(`when upload is enabled, it returns CKEditor options`, () => {
            element = {
                dataset: {
                    uploadUrl: "https://example.com/disprobabilize/gavyuti"
                }
            };

            const result = getUploadImageOptions(element);

            expect(result).toEqual({
                extraPlugins: "uploadimage",
                uploadUrl: "https://example.com/disprobabilize/gavyuti"
            });
        });
    });
});
