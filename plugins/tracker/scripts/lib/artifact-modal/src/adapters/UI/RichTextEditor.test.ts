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

import * as image_upload from "@tuleap/ckeditor-image-upload";
import { MaxSizeUploadExceededError, UploadError } from "@tuleap/ckeditor-image-upload";
import type {
    RichTextEditorOptions,
    TextEditorInterface,
} from "@tuleap/plugin-tracker-rich-text-editor";
import { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "@tuleap/plugin-tracker-constants";
import { Option } from "@tuleap/option";
import { setCatalog } from "../../gettext-catalog";
import type { HostElement } from "./RichTextEditor";
import {
    connect,
    createEditor,
    getValidFormat,
    onInstanceReady,
    onTextareaInput,
    RichTextEditor,
    setupImageUpload,
} from "./RichTextEditor";
import { FormattedTextController } from "../../domain/common/FormattedTextController";
import { DispatchEventsStub } from "../../../tests/stubs/DispatchEventsStub";
import type { FileUploadSetup } from "../../domain/fields/file-field/FileUploadSetup";
import { InterpretCommonMarkStub } from "../../../tests/stubs/InterpretCommonMarkStub";

type CKEditorEventHandler = (event: CKEDITOR.eventInfo) => void;

jest.mock("@tuleap/ckeditor-image-upload", () => {
    const actual_module = jest.requireActual("@tuleap/ckeditor-image-upload");
    return {
        MaxSizeUploadExceededError: actual_module.MaxSizeUploadExceededError,
        UploadError: actual_module.UploadError,
        buildFileUploadHandler: jest.fn(),
        isThereAnImageWithDataURI: jest.fn(),
    };
});
jest.mock("pretty-kibibytes", () => {
    return {
        default: (bytes: number): string => String(bytes),
    };
});

const noop = (): void => {
    // Do nothing
};

const noopHandler = (event: CKEDITOR.eventInfo): void => {
    if (event) {
        // do nothing unless overwritten
    }
};
const null_event = {} as CKEDITOR.eventInfo;

let format: TextFieldFormat,
    value: string,
    disabled: boolean,
    required: boolean,
    editor_factory: RichTextEditorFactory,
    editor: TextEditorInterface,
    ckeditor: CKEDITOR.editor,
    upload_setup: Option<FileUploadSetup>,
    buildFileUploadHandler: jest.SpyInstance,
    isThereAnImageWithDataURI: jest.SpyInstance,
    event_dispatcher: DispatchEventsStub;

describe(`RichTextEditor`, () => {
    let doc: Document;
    beforeEach(() => {
        jest.resetAllMocks();
        setCatalog({ getString: (msgid) => msgid });
        doc = document.implementation.createHTMLDocument();

        buildFileUploadHandler = jest.spyOn(image_upload, "buildFileUploadHandler");

        event_dispatcher = DispatchEventsStub.withRecordOfEventTypes();

        isThereAnImageWithDataURI = jest.spyOn(image_upload, "isThereAnImageWithDataURI");

        ckeditor = {
            // eslint-disable-next-line @typescript-eslint/no-unused-vars
            on(event_name: string, handler: CKEditorEventHandler) {
                // Do nothing
            },
            getData() {
                return value;
            },
        } as CKEDITOR.editor;
        editor = {
            destroy: jest.fn(),
        } as unknown as TextEditorInterface;
        editor_factory = {
            createRichTextEditor: (
                textarea: HTMLTextAreaElement,
                options: RichTextEditorOptions
            ): TextEditorInterface => {
                if (typeof options.onFormatChange !== "function") {
                    throw new Error("Expected onFormatChange to be a function");
                }
                options.onFormatChange(format);
                if (typeof options.onEditorInit !== "function") {
                    throw new Error("Expected onEditorInit to be a function");
                }
                options.onEditorInit(ckeditor, textarea);
                return editor;
            },
        } as RichTextEditorFactory;
        jest.spyOn(
            RichTextEditorFactory,
            "forBurningParrotWithExistingFormatSelector"
        ).mockReturnValue(editor_factory);

        required = false;
        disabled = false;
        format = "text";
        value = "";
        upload_setup = Option.fromValue({
            max_size_upload: 3000,
            file_creation_uri: "https://example.com/upload",
        });
    });

    function getHost(): HostElement {
        const element = doc.createElement("div");
        return Object.assign(element, {
            identifier: "unique-id",
            format,
            contentValue: value,
            disabled,
            required,
            rows: 5,
            textarea: {} as HTMLTextAreaElement,
            is_help_shown: false,
            upload_setup,
            controller: FormattedTextController(
                event_dispatcher,
                InterpretCommonMarkStub.withHTML(`<p>HTML</p>`),
                TEXT_FORMAT_TEXT
            ),
        } as HostElement);
    }

    describe(`when the editor's format is "html"`, () => {
        beforeEach(() => {
            format = "html";
        });
        describe(`onInstanceReady()`, () => {
            it(`and when the editor dispatched the "change" event,
                and the editor's data was different from its contentValue
                then it will dispatch a bubbling "change" event
                and a "content-change" event with the new content`, () => {
                let triggerChange = noopHandler;
                ckeditor = {
                    on(
                        event_name: string,
                        handler: CKEditorEventHandler
                    ): CKEDITOR.listenerRegistration {
                        if (event_name === "change") {
                            triggerChange = handler;
                            return { removeListener: noop };
                        }
                        if (event_name === "mode" || event_name === "fileUploadRequest") {
                            return { removeListener: noop };
                        }
                        throw new Error("Unexpected event name: " + event_name);
                    },
                    getData() {
                        return "caramba";
                    },
                } as CKEDITOR.editor;
                const host = getHost();
                const dispatchEvent = jest.spyOn(host, "dispatchEvent");

                onInstanceReady(host, ckeditor);
                triggerChange(null_event);

                const event = dispatchEvent.mock.calls[0][0];
                expect(event.type).toBe("change");
                expect(event.bubbles).toBe(true);

                const custom_event = dispatchEvent.mock.calls[1][0];
                if (!(custom_event instanceof CustomEvent)) {
                    throw Error("Expected a CustomEvent");
                }
                expect(custom_event.type).toBe("content-change");
                expect(custom_event.detail.content).toBe("caramba");
            });

            it(`and when the editor dispatched the "mode" event,
                and the editor was in "source" mode (direct HTML edition)
                and the editor's editable textarea dispatched the "input" event,
                then it will dispatch a bubbling "change" event
                and a "content-change" event with the new content`, () => {
                let triggerMode = noopHandler,
                    triggerEditableInput = noopHandler;
                const editable = {
                    attachListener(editable, event_name, handler) {
                        triggerEditableInput = handler;
                    },
                } as CKEDITOR.editable;

                ckeditor = {
                    mode: "source",
                    on(event_name: string, handler: CKEditorEventHandler): void {
                        if (event_name === "mode") {
                            triggerMode = handler;
                            return undefined;
                        }
                        if (event_name === "change" || event_name === "fileUploadRequest") {
                            return undefined;
                        }
                        throw new Error("Unexpected event name: " + event_name);
                    },
                    editable() {
                        return editable;
                    },
                    getData() {
                        return "noniodized";
                    },
                } as CKEDITOR.editor;
                const attachListener = jest.spyOn(editable, "attachListener");
                const host = getHost();
                const dispatchEvent = jest.spyOn(host, "dispatchEvent");

                onInstanceReady(host, ckeditor);
                triggerMode(null_event);
                triggerEditableInput(null_event);

                expect(attachListener).toHaveBeenCalledWith(
                    expect.anything(),
                    "input",
                    expect.any(Function)
                );

                const event = dispatchEvent.mock.calls[0][0];
                expect(event.type).toBe("change");
                expect(event.bubbles).toBe(true);

                const custom_event = dispatchEvent.mock.calls[1][0];
                if (!(custom_event instanceof CustomEvent)) {
                    throw Error("Expected a CustomEvent");
                }
                expect(custom_event.type).toBe("content-change");
                expect(custom_event.detail.content).toBe("noniodized");
            });
        });

        describe(`disconnect()`, () => {
            it(`if the editor was created, then it will destroy the editor`, () => {
                const host = getHost();
                const disconnect = connect(host);
                host.editor = editor;
                disconnect();

                expect(editor.destroy).toHaveBeenCalled();
            });
        });

        describe(`createEditor()`, () => {
            it(`will not create an editor if given identifier is empty`, () => {
                const host = getHost();
                host.identifier = "";
                host.textarea = {} as HTMLTextAreaElement;
                host.editor = createEditor(host);

                expect(host.editor).toBeUndefined();
            });
        });

        describe(`when uploading is not possible`, () => {
            beforeEach(() => {
                upload_setup = Option.nothing();
            });

            it(`removes the uploadimage plugin from ckeditor's configuration`, () => {
                const createRichEditor = jest.spyOn(editor_factory, "createRichTextEditor");

                const host = getHost();
                host.textarea = {} as HTMLTextAreaElement;
                host.editor = createEditor(host);

                const editor_options = createRichEditor.mock.calls[0][1];
                if (typeof editor_options.getAdditionalOptions !== "function") {
                    throw new Error("Expected getAdditionalOptions to be a function");
                }
                const ckeditor_options = editor_options.getAdditionalOptions(host.textarea);
                expect(ckeditor_options.extraPlugins).toBeUndefined();
                expect(ckeditor_options.uploadUrl).toBeUndefined();
            });

            it(`disables the paste event for images and shows an error message`, () => {
                let triggerPaste = noopHandler;
                ckeditor = {
                    on(event_name: string, handler: CKEditorEventHandler): void {
                        if (event_name === "paste") {
                            triggerPaste = handler;
                            return undefined;
                        } else if (event_name === "change" || event_name === "mode") {
                            return undefined;
                        }
                        throw new Error("Unexpected event name: " + event_name);
                    },
                    /* eslint-disable @typescript-eslint/no-unused-vars */
                    showNotification(
                        message: string,
                        type: CKEDITOR.plugins.notification.type,
                        duration: number
                    ): void {
                        // side-effects
                    },
                    /* eslint-enable @typescript-eslint/no-unused-vars */
                } as CKEDITOR.editor;
                isThereAnImageWithDataURI.mockReturnValue(true);
                const showNotification = jest.spyOn(ckeditor, "showNotification");

                onInstanceReady(getHost(), ckeditor);

                const event = {
                    cancel: noop,
                    data: { dataValue: `<p></p>` },
                } as CKEDITOR.eventInfo;
                const cancel = jest.spyOn(event, "cancel");
                triggerPaste(event);

                expect(cancel).toHaveBeenCalled();
                expect(showNotification).toHaveBeenCalled();
            });

            it(`does not set up image upload`, () => {
                setupImageUpload(getHost(), ckeditor);

                expect(buildFileUploadHandler).not.toHaveBeenCalled();
            });
        });

        describe(`setupImageUpload() when uploading is possible`, () => {
            it(`informs users that they can paste images`, () => {
                const target = doc.createElement("div") as unknown as ShadowRoot;
                const host = getHost();
                host.is_help_shown = true;
                const update = RichTextEditor.content(host);
                update(host, target);

                const help = target.querySelector("[data-test=help]");
                expect(help).not.toBeNull();
            });

            describe(`when CKEditor instance is ready`, () => {
                beforeEach(() => {
                    onInstanceReady(getHost(), ckeditor);
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
                    beforeEach(() => {
                        const options = buildFileUploadHandler.mock.calls[0][0];
                        options.onStartCallback();
                    });

                    it(`disables form submits`, () => {
                        expect(event_dispatcher.getDispatchedEventTypes()).toContain(
                            "WillDisableSubmit"
                        );
                    });
                });

                describe(`when the upload succeeds`, () => {
                    beforeEach(() => {
                        const options = buildFileUploadHandler.mock.calls[0][0];
                        options.onSuccessCallback(64, "http://example.com/sacrilegiously");
                    });

                    it(`enables back form submits and asks the file field to attach the uploaded image`, () => {
                        const dispatched_events = event_dispatcher.getDispatchedEventTypes();
                        expect(dispatched_events).toContain("WillEnableSubmit");
                        expect(dispatched_events).toContain("DidUploadImage");
                    });
                });

                describe(`when the upload fails`, () => {
                    // eslint-disable-next-line @typescript-eslint/no-unused-vars
                    let triggerError = (error: MaxSizeUploadExceededError | UploadError): void => {
                        // Do nothing unless overwritten
                    };
                    beforeEach(() => {
                        onInstanceReady(getHost(), ckeditor);
                        const options = buildFileUploadHandler.mock.calls[0][0];
                        triggerError = options.onErrorCallback;
                    });

                    it(`and the max size has been exceeded,
                        then it shows an error message and enables back form submits`, () => {
                        const error = new MaxSizeUploadExceededError(
                            3000,
                            {} as CKEDITOR.fileTools.fileLoader
                        );
                        triggerError(error);

                        expect(error.loader.message).toBeDefined();
                        expect(event_dispatcher.getDispatchedEventTypes()).toContain(
                            "WillEnableSubmit"
                        );
                    });

                    it(`and the upload failed,
                        then it shows an error message and enables back form submits`, () => {
                        const error = new UploadError({} as CKEDITOR.fileTools.fileLoader);
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
            it(`will dispatch a "content-change" event with the new content`, () => {
                const inner_textarea = doc.createElement("textarea");
                const host = getHost();
                inner_textarea.addEventListener("input", (event) => onTextareaInput(host, event));
                const dispatchEvent = jest.spyOn(host, "dispatchEvent");

                inner_textarea.value = "flattening";
                inner_textarea.dispatchEvent(new InputEvent("input"));

                const event = dispatchEvent.mock.calls[0][0];
                if (!(event instanceof CustomEvent)) {
                    throw Error("Expected a CustomEvent");
                }
                expect(event.type).toBe("content-change");
                expect(event.detail.content).toBe("flattening");
            });
        });
    });

    describe(`disabled`, () => {
        beforeEach(() => {
            disabled = true;
        });

        it(`will compute CKEditor's readOnly configuration from the "disabled" prop`, () => {
            const createRichEditor = jest.spyOn(editor_factory, "createRichTextEditor");

            const host = getHost();
            host.textarea = {} as HTMLTextAreaElement;
            host.editor = createEditor(host);

            const editor_options = createRichEditor.mock.calls[0][1];
            if (typeof editor_options.getAdditionalOptions !== "function") {
                throw new Error("Expected getAdditionalOptions to be a function");
            }
            const ckeditor_options = editor_options.getAdditionalOptions(host.textarea);
            expect(ckeditor_options.readOnly).toBe(true);
        });

        it(`will set the textarea to disabled`, () => {
            const target = doc.createElement("div") as unknown as ShadowRoot;
            const host = getHost();
            const update = RichTextEditor.content(host);
            update(host, target);

            const textarea = getTextarea(target);
            expect(textarea.disabled).toBe(true);
        });
    });

    describe(`required`, () => {
        beforeEach(() => {
            required = true;
        });

        it(`will set the textarea to required`, () => {
            const target = doc.createElement("div") as unknown as ShadowRoot;
            const host = getHost();
            const update = RichTextEditor.content(host);
            update(host, target);

            const textarea = getTextarea(target);
            expect(textarea.required).toBe(true);
        });
    });

    describe(`getValidFormat()`, () => {
        it.each([[TEXT_FORMAT_TEXT], [TEXT_FORMAT_HTML], [TEXT_FORMAT_COMMONMARK]])(
            `when value is a valid format, it will return it`,
            (format) => {
                expect(getValidFormat({}, format, TEXT_FORMAT_TEXT)).toBe(format);
            }
        );

        it(`when value is not a valid format, it will return last value`, () => {
            expect(getValidFormat({}, "invalid_format", TEXT_FORMAT_TEXT)).toBe(TEXT_FORMAT_TEXT);
        });

        it(`when last value is undefined (no previous cached value), it will default to Commonmark format`, () => {
            expect(getValidFormat({}, "invalid_format", undefined)).toBe(TEXT_FORMAT_COMMONMARK);
        });
    });
});

function getTextarea(target: ShadowRoot): HTMLTextAreaElement {
    const textarea = target.querySelector("[data-test=textarea]");
    if (!(textarea instanceof HTMLTextAreaElement)) {
        throw new Error("Could not find textarea in component");
    }
    return textarea;
}
