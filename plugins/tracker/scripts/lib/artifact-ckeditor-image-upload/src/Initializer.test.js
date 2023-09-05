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

import { MaxSizeUploadExceededError, UploadError } from "@tuleap/ckeditor-image-upload";
import * as image_upload from "@tuleap/ckeditor-image-upload";
import { Initializer } from "./Initializer";
import * as form_adapter from "./form-adapter.js";
import * as consistent_uploaded_files_before_submit_checker from "./consistent-uploaded-files-before-submit-checker.js";
import { UploadEnabledDetector } from "./UploadEnabledDetector";

jest.mock("@tuleap/ckeditor-image-upload", () => {
    const actual_module = jest.requireActual("@tuleap/ckeditor-image-upload");
    return {
        MaxSizeUploadExceededError: actual_module.MaxSizeUploadExceededError,
        UploadError: actual_module.UploadError,
        buildFileUploadHandler: jest.fn(),
        isThereAnImageWithDataURI: jest.fn(),
    };
});

const createDocument = () => document.implementation.createHTMLDocument();

describe(`Initializer`, () => {
    let doc, textarea, initializer, gettext_provider, detector;

    beforeEach(() => {
        doc = createDocument();
        gettext_provider = {
            gettext: (english) => english,
        };
        textarea = doc.createElement("textarea");
        doc.body.append(textarea);
        detector = new UploadEnabledDetector(doc, textarea);
        initializer = new Initializer(doc, gettext_provider, detector);
    });

    describe(`init()`, () => {
        let ckeditor_instance;

        beforeEach(() => {
            ckeditor_instance = {
                on: jest.fn(),
                showNotification: jest.fn(),
            };
        });

        it(`when upload is disabled, it will disable paste of images
            and show a notification`, () => {
            jest.spyOn(detector, "isUploadEnabled").mockReturnValue(false);
            textarea.dataset.uploadUrl = "https://example.com/disprobabilize/gavyuti";
            let triggerPaste;
            ckeditor_instance.on.mockImplementation((event_name, handler) => {
                triggerPaste = handler;
            });
            jest.spyOn(image_upload, "isThereAnImageWithDataURI").mockReturnValue(true);

            initializer.init(ckeditor_instance, textarea);

            const event = {
                cancel: jest.fn(),
                data: { dataValue: `<p></p>` },
            };
            triggerPaste(event);

            expect(event.cancel).toHaveBeenCalled();
            expect(ckeditor_instance.showNotification).toHaveBeenCalled();
        });

        describe(`when upload is enabled`, () => {
            let form;
            beforeEach(() => {
                form = {
                    querySelectorAll: jest.fn(),
                    addEventListener: jest.fn(),
                    appendChild: jest.fn(),
                };
                textarea = {
                    dataset: {
                        uploadUrl: "https://example.com/disprobabilize/gavyuti",
                        uploadFieldName: "satrapess",
                        uploadMaxSize: "1024",
                    },
                    form,
                };
                jest.spyOn(detector, "isUploadEnabled").mockReturnValue(true);
            });

            it(`builds the file upload handler and registers it on the CKEditor instance`, () => {
                const buildFileUploadHandler = jest.spyOn(image_upload, "buildFileUploadHandler");

                initializer.init(ckeditor_instance, textarea);

                const expected_options = {
                    ckeditor_instance,
                    max_size_upload: 1024,
                    onStartCallback: expect.any(Function),
                    onErrorCallback: expect.any(Function),
                    onSuccessCallback: expect.any(Function),
                };
                expect(buildFileUploadHandler).toHaveBeenCalledWith(expected_options);
            });

            it(`when the upload starts, it disables form submits`, () => {
                let triggerStart;
                jest.spyOn(image_upload, "buildFileUploadHandler").mockImplementation(
                    ({ onStartCallback }) => {
                        triggerStart = onStartCallback;
                    },
                );
                const disableFormSubmit = jest
                    .spyOn(form_adapter, "disableFormSubmit")
                    .mockImplementation(() => {});

                initializer.init(ckeditor_instance, textarea);
                triggerStart();

                expect(disableFormSubmit).toHaveBeenCalled();
            });

            describe(`when the upload succeeds`, () => {
                let triggerSuccess, enableFormSubmit;
                beforeEach(() => {
                    jest.spyOn(image_upload, "buildFileUploadHandler").mockImplementation(
                        ({ onSuccessCallback }) => {
                            triggerSuccess = onSuccessCallback;
                        },
                    );
                    jest.spyOn(form, "appendChild").mockImplementation();
                    enableFormSubmit = jest
                        .spyOn(form_adapter, "enableFormSubmit")
                        .mockImplementation(() => {});
                    initializer.init(ckeditor_instance, textarea);
                    triggerSuccess(182, "http://example.com/scenary");
                });

                it(`appends a hidden field on the form`, () => {
                    expect(form.appendChild).toHaveBeenCalled();
                    const input = form.appendChild.mock.calls[0][0];
                    expect(input.type).toBe("hidden");
                    expect(input.name).toBe("satrapess");
                    expect(input.value).toBe("182");
                    expect(input.dataset.url).toBe("http://example.com/scenary");
                });

                it(`enables back form submits`, () => {
                    expect(enableFormSubmit).toHaveBeenCalled();
                });
            });

            describe(`when the upload fails`, () => {
                let triggerError, enableFormSubmit;
                beforeEach(() => {
                    jest.spyOn(image_upload, "buildFileUploadHandler").mockImplementation(
                        ({ onErrorCallback }) => {
                            triggerError = onErrorCallback;
                        },
                    );
                    enableFormSubmit = jest
                        .spyOn(form_adapter, "enableFormSubmit")
                        .mockImplementation(() => {});

                    initializer.init(ckeditor_instance, textarea);
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

            it(`registers the CKEditor instance to clear unused uploaded files`, () => {
                const addInstance = jest.spyOn(
                    consistent_uploaded_files_before_submit_checker,
                    "addInstance",
                );
                initializer.init(ckeditor_instance, textarea);

                expect(addInstance).toHaveBeenCalledWith(form, ckeditor_instance, "satrapess");
            });
        });
    });
});
