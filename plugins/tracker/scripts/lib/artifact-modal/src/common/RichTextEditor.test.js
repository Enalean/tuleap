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

import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import { MaxSizeUploadExceededError, UploadError } from "@tuleap/ckeditor-image-upload";
import * as image_upload from "@tuleap/ckeditor-image-upload";
import { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import * as is_uploading_in_ckeditor_state from "../fields/file-field/is-uploading-in-ckeditor-state.js";
import store_options from "../store/index.js";
import localVue from "../helpers/local-vue.js";
import RichTextEditor from "./RichTextEditor.vue";
import { setCatalog } from "../gettext-catalog";

jest.mock("@tuleap/ckeditor-image-upload", () => {
    const actual_module = jest.requireActual("@tuleap/ckeditor-image-upload");
    return {
        MaxSizeUploadExceededError: actual_module.MaxSizeUploadExceededError,
        UploadError: actual_module.UploadError,
        buildFileUploadHandler: jest.fn(),
        isThereAnImageWithDataURI: jest.fn(),
    };
});

let store,
    format,
    value,
    disabled,
    required,
    editor_factory,
    editor,
    ckeditor,
    buildFileUploadHandler,
    setIsUploadingInCKEditor,
    setIsNotUploadingInCKEditor,
    isThereAnImageWithDataURI;

function getInstance() {
    return shallowMount(RichTextEditor, {
        localVue,
        mocks: {
            $store: store,
        },
        propsData: {
            id: "unique-id",
            format,
            value,
            disabled,
            required,
        },
    });
}

describe(`RichTextEditor`, () => {
    beforeEach(() => {
        jest.resetAllMocks();
        setCatalog({ getString: () => "" });
        store = createStoreMock(store_options);

        buildFileUploadHandler = jest.spyOn(image_upload, "buildFileUploadHandler");

        setIsUploadingInCKEditor = jest.spyOn(
            is_uploading_in_ckeditor_state,
            "setIsUploadingInCKEditor"
        );
        setIsNotUploadingInCKEditor = jest.spyOn(
            is_uploading_in_ckeditor_state,
            "setIsNotUploadingInCKEditor"
        );

        isThereAnImageWithDataURI = jest.spyOn(image_upload, "isThereAnImageWithDataURI");

        ckeditor = {
            on: jest.fn(),
            showNotification: jest.fn(),
        };
        editor = {
            destroy: jest.fn(),
        };
        editor_factory = {
            createRichTextEditor: (textarea, options) => {
                options.onFormatChange(format);
                options.onEditorInit(ckeditor);
                return editor;
            },
        };
        jest.spyOn(
            RichTextEditorFactory,
            "forBurningParrotWithExistingFormatSelector"
        ).mockReturnValue(editor_factory);

        disabled = false;
        format = "text";
        value = "";
    });

    describe(`when the editor's format is "html"`, () => {
        beforeEach(() => {
            format = "html";
        });
        describe(`mounted()`, () => {
            it(`and when the editor dispatched the "change" event,
                and the editor's data was different from its prop
                then it will dispatch an "input" event with the new content`, () => {
                let triggerChange;
                ckeditor.on.mockImplementation((event_name, handler) => {
                    if (event_name === "change") {
                        triggerChange = handler;
                        return undefined;
                    }
                    if (event_name === "mode" || event_name === "fileUploadRequest") {
                        return undefined;
                    }
                    throw new Error("Unexpected event name: " + event_name);
                });
                ckeditor.getData = () => "caramba";

                const wrapper = getInstance();
                triggerChange();

                expect(wrapper.emitted().input[0]).toEqual(["caramba"]);
            });

            it(`and when the editor dispatched the "mode" event,
                and the editor was in "source" mode (direct HTML edition)
                and the editor's editable textarea dispatched the "input" event,
                then it will dispatch an "input" event with the new content`, () => {
                let triggerMode, triggerEditableInput;
                ckeditor.on.mockImplementation((event_name, handler) => {
                    if (event_name === "mode") {
                        triggerMode = handler;
                        return undefined;
                    }
                    if (event_name === "change" || event_name === "fileUploadRequest") {
                        return undefined;
                    }
                    throw new Error("Unexpected event name: " + event_name);
                });
                ckeditor.mode = "source";
                const editable = {
                    attachListener: jest.fn(),
                };
                editable.attachListener.mockImplementation((element, event_name, handler) => {
                    triggerEditableInput = handler;
                });
                ckeditor.editable = () => editable;
                ckeditor.getData = () => "noniodized";

                const wrapper = getInstance();
                triggerMode();
                triggerEditableInput();

                expect(editable.attachListener).toHaveBeenCalledWith(
                    expect.anything(),
                    "input",
                    expect.any(Function)
                );
                expect(wrapper.emitted().input[0]).toEqual(["noniodized"]);
            });
        });

        describe(`beforeDestroy()`, () => {
            it(`if the editor was created, then it will destroy the editor`, () => {
                const wrapper = getInstance();
                wrapper.destroy();

                expect(editor.destroy).toHaveBeenCalled();
            });
        });

        describe(`when uploading is not possible`, () => {
            beforeEach(() => {
                store.getters.first_file_field = null;
            });

            it(`removes the uploadimage plugin from ckeditor's configuration`, () => {
                const createEditor = jest.spyOn(editor_factory, "createRichTextEditor");

                getInstance();

                const editor_options = createEditor.mock.calls[0][1];
                const ckeditor_options = editor_options.getAdditionalOptions();
                expect(ckeditor_options.extraPlugins).not.toBeDefined();
                expect(ckeditor_options.uploadUrl).not.toBeDefined();
            });

            it(`disables the paste event for images and shows an error message`, () => {
                let triggerPaste;
                ckeditor.on.mockImplementation((event_name, handler) => {
                    if (event_name === "paste") {
                        triggerPaste = handler;
                        return undefined;
                    } else if (event_name === "change" || event_name === "mode") {
                        return undefined;
                    }
                    throw new Error("Unexpected event name: " + event_name);
                });
                isThereAnImageWithDataURI.mockReturnValue(true);

                getInstance();
                const event = {
                    cancel: jest.fn(),
                    data: { dataValue: `<p></p>` },
                };
                triggerPaste(event);

                expect(event.cancel).toHaveBeenCalled();
                expect(ckeditor.showNotification).toHaveBeenCalled();
            });

            it(`does not set up image upload`, () => {
                const wrapper = getInstance();
                wrapper.vm.setupImageUpload(ckeditor);

                expect(buildFileUploadHandler).not.toHaveBeenCalled();
            });
        });

        describe(`setupImageUpload() when uploading is possible`, () => {
            let file_field;
            beforeEach(() => {
                file_field = {
                    field_id: 197,
                    max_size_upload: 3000,
                };
                store.getters.first_file_field = file_field;
            });

            it(`informs users that they can paste images`, async () => {
                const wrapper = getInstance();
                await wrapper.vm.$nextTick();
                const help = wrapper.get("[data-test=help]");

                expect(help.exists()).toBe(true);
            });

            describe(`when CKEditor instance is ready`, () => {
                let wrapper;
                beforeEach(() => {
                    wrapper = getInstance();
                });

                it(`builds the file upload handler and registers it on the CKEditor instance`, () => {
                    expect(buildFileUploadHandler).toHaveBeenCalledWith({
                        ckeditor_instance: ckeditor,
                        max_size_upload: 3000,
                        onStartCallback: expect.any(Function),
                        onErrorCallback: expect.any(Function),
                        onSuccessCallback: expect.any(Function),
                    });
                });

                describe(`when the upload starts`, () => {
                    let triggerStart;
                    beforeEach(() => {
                        const options = buildFileUploadHandler.mock.calls[0][0];
                        triggerStart = options.onStartCallback;
                        triggerStart();
                    });

                    it(`disables form submits`, () =>
                        expect(setIsUploadingInCKEditor).toHaveBeenCalled());
                });

                describe(`when the upload succeeds`, () => {
                    let triggerSuccess;
                    beforeEach(() => {
                        const options = buildFileUploadHandler.mock.calls[0][0];
                        triggerSuccess = options.onSuccessCallback;
                        triggerSuccess(64, "http://example.com/sacrilegiously");
                    });

                    it(`emits an upload-image event`, () => {
                        const expected_file = {
                            id: 64,
                            download_href: "http://example.com/sacrilegiously",
                        };
                        const event = wrapper.emitted("upload-image")[0];
                        expect(event).toEqual([file_field.field_id, expected_file]);
                    });

                    it(`enables back form submits`, () =>
                        expect(setIsNotUploadingInCKEditor).toHaveBeenCalled());
                });

                describe(`when the upload fails`, () => {
                    let triggerError;
                    beforeEach(() => {
                        const options = buildFileUploadHandler.mock.calls[0][0];
                        triggerError = options.onErrorCallback;
                    });

                    it(`enables back form submits`, () => {
                        triggerError();

                        expect(setIsNotUploadingInCKEditor).toHaveBeenCalled();
                    });

                    it(`and the max size has been exceeded,
                    then it shows an error message`, () => {
                        const error = new MaxSizeUploadExceededError(3000, {});
                        triggerError(error);

                        expect(error.loader.message).toBeDefined();
                    });

                    it(`and the upload failed, then it shows an error message`, () => {
                        const error = new UploadError({});
                        triggerError(error);

                        expect(error.loader.message).toBeDefined();
                    });
                });
            });
        });
    });

    describe(`when the field's format is "text"`, () => {
        beforeEach(() => {
            format = "text";
        });

        describe(`and I wrote text in the textarea`, () => {
            it(`will dispatch an "input" event with the new content`, () => {
                const wrapper = getInstance();
                wrapper.vm.content = "flattening";

                expect(wrapper.emitted("input")[0]).toEqual(["flattening"]);
            });
        });
    });

    it(`when the format is anything else, it throws`, () => {
        const wrapper = getInstance();
        expect(wrapper.vm.$options.props.format.validator("markdown")).toBe(false);
    });

    describe(`disabled`, () => {
        beforeEach(() => {
            disabled = true;
        });

        it(`will compute CKEditor's readOnly configuration from the "disabled" prop`, () => {
            const createEditor = jest.spyOn(editor_factory, "createRichTextEditor");

            getInstance();

            const editor_options = createEditor.mock.calls[0][1];
            const ckeditor_options = editor_options.getAdditionalOptions();
            expect(ckeditor_options.readOnly).toBe(true);
        });

        it(`will set the textarea to disabled`, () => {
            const wrapper = getInstance();
            const textarea = wrapper.get("[data-test=textarea]");
            expect(textarea.attributes("disabled")).toBe("disabled");
        });
    });

    describe(`required`, () => {
        let wrapper;
        beforeEach(() => {
            required = true;
            wrapper = getInstance();
        });

        it(`will set the textarea to required`, () => {
            const textarea = wrapper.get("[data-test=textarea]");
            expect(textarea.attributes("required")).toBe("required");
        });
    });
});
