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

import * as element_adapter from "./element-adapter.js";
import * as gettext_factory from "../gettext/gettext-factory.js";
import { MaxSizeUploadExceededError, UploadError } from "./file-upload-handler-factory.js";
import * as file_upload_handler_factory from "./file-upload-handler-factory.js";
import * as form_adapter from "./form-adapter.js";
import * as consistent_uploaded_files_before_submit_checker from "./consistent-uploaded-files-before-submit-checker.js";
import * as image_urls_finder from "./image-urls-finder.js";
import { getUploadImageOptions, initiateUploadImage } from "./get-upload-image-options.js";

describe(`get-upload-image-options`, () => {
    let isUploadEnabled,
        gettext_provider = {
            gettext: () => "",
        };

    beforeEach(() => {
        isUploadEnabled = jest
            .spyOn(element_adapter, "isUploadEnabled")
            .mockImplementation(() => true);
        jest.spyOn(gettext_factory, "initGettext").mockImplementation(() => gettext_provider);
    });

    describe(`initiateUploadImage()`, () => {
        let ckeditor_instance;
        const options = {};

        beforeEach(() => {
            ckeditor_instance = {
                on: jest.fn(),
                showNotification: jest.fn(),
            };
        });

        it(`when upload is disabled, it will disable paste of images
            and show a notification`, async () => {
            isUploadEnabled.mockReturnValue(false);
            const element = {
                dataset: { uploadUrl: "https://example.com/disprobabilize/gavyuti" },
            };
            let triggerPaste;
            ckeditor_instance.on.mockImplementation((event_name, handler) => {
                triggerPaste = handler;
            });
            jest.spyOn(image_urls_finder, "isThereAnImageWithDataURI").mockReturnValue(true);

            await initiateUploadImage(ckeditor_instance, options, element);

            const event = {
                cancel: jest.fn(),
                data: { dataValue: `<p></p>` },
            };
            triggerPaste(event);

            expect(event.cancel).toHaveBeenCalled();
            expect(ckeditor_instance.showNotification).toHaveBeenCalled();
        });

        describe(`when upload is enabled`, () => {
            let form, element;
            beforeEach(() => {
                form = {
                    querySelectorAll: jest.fn(),
                    addEventListener: jest.fn(),
                    appendChild: jest.fn(),
                };
                element = {
                    dataset: {
                        uploadUrl: "https://example.com/disprobabilize/gavyuti",
                        uploadFieldName: "satrapess",
                        uploadMaxSize: "1024",
                    },
                    form,
                };
            });

            it(`informs users that they can paste images`, async () => {
                const informUsersThatTheyCanPasteImagesInEditor = jest.spyOn(
                    element_adapter,
                    "informUsersThatTheyCanPasteImagesInEditor"
                );

                await initiateUploadImage(ckeditor_instance, options, element);

                expect(informUsersThatTheyCanPasteImagesInEditor).toHaveBeenCalled();
            });

            it(`builds the file upload handler and registers it on the CKEditor instance`, async () => {
                const buildFileUploadHandler = jest.spyOn(
                    file_upload_handler_factory,
                    "buildFileUploadHandler"
                );

                await initiateUploadImage(ckeditor_instance, options, element);

                const expected_options = {
                    ckeditor_instance,
                    max_size_upload: 1024,
                    onStartCallback: expect.any(Function),
                    onErrorCallback: expect.any(Function),
                    onSuccessCallback: expect.any(Function),
                };
                expect(buildFileUploadHandler).toHaveBeenCalledWith(expected_options);
            });

            it(`when the upload starts, it disables form submits`, async () => {
                let triggerStart;
                jest.spyOn(
                    file_upload_handler_factory,
                    "buildFileUploadHandler"
                ).mockImplementation(({ onStartCallback }) => {
                    triggerStart = onStartCallback;
                });
                const disableFormSubmit = jest
                    .spyOn(form_adapter, "disableFormSubmit")
                    .mockImplementation(() => {});

                await initiateUploadImage(ckeditor_instance, options, element);
                triggerStart();

                expect(disableFormSubmit).toHaveBeenCalled();
            });

            describe(`when the upload succeeds`, () => {
                let triggerSuccess, enableFormSubmit;
                beforeEach(async () => {
                    jest.spyOn(
                        file_upload_handler_factory,
                        "buildFileUploadHandler"
                    ).mockImplementation(({ onSuccessCallback }) => {
                        triggerSuccess = onSuccessCallback;
                    });
                    jest.spyOn(form, "appendChild").mockImplementation();
                    enableFormSubmit = jest
                        .spyOn(form_adapter, "enableFormSubmit")
                        .mockImplementation(() => {});
                    await initiateUploadImage(ckeditor_instance, options, element);
                    triggerSuccess(182, "http://example.com/scenary");
                });

                it(`appends a hidden field on the form`, () => {
                    expect(form.appendChild).toHaveBeenCalled();
                    const input = form.appendChild.mock.calls[0][0];
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
                let triggerError, enableFormSubmit;
                beforeEach(async () => {
                    jest.spyOn(
                        file_upload_handler_factory,
                        "buildFileUploadHandler"
                    ).mockImplementation(({ onErrorCallback }) => {
                        triggerError = onErrorCallback;
                    });
                    enableFormSubmit = jest
                        .spyOn(form_adapter, "enableFormSubmit")
                        .mockImplementation(() => {});

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
                const addInstance = jest.spyOn(
                    consistent_uploaded_files_before_submit_checker,
                    "addInstance"
                );
                await initiateUploadImage(ckeditor_instance, options, element);

                expect(addInstance).toHaveBeenCalledWith(form, ckeditor_instance, "satrapess");
            });
        });
    });

    describe(`getUploadImageOptions()`, () => {
        let element;

        it(`when upload is disabled, it returns an empty object`, () => {
            isUploadEnabled.mockReturnValue(false);

            expect(getUploadImageOptions(element)).toEqual({});
        });

        it(`when upload is enabled, it returns CKEditor options`, () => {
            element = {
                dataset: {
                    uploadUrl: "https://example.com/disprobabilize/gavyuti",
                },
            };

            const result = getUploadImageOptions(element);

            expect(result).toEqual({
                extraPlugins: "uploadimage",
                uploadUrl: "https://example.com/disprobabilize/gavyuti",
            });
        });
    });
});
