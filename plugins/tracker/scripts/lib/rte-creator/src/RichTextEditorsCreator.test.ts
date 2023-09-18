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

import type { UploadImageFormFactory } from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";
import type { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import { RichTextEditorsCreator } from "./RichTextEditorsCreator";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "@tuleap/plugin-tracker-constants";
import { beforeEach, describe, expect, it, vi } from "vitest";

// Mock @tuleap/mention because it needs jquery in tests
vi.mock("@tuleap/mention", () => {
    return { initMentions: vi.fn() };
});

const noop = (): void => {
    //Do nothing
};

describe(`RichTextEditorsCreator`, () => {
    let doc: Document,
        creator: RichTextEditorsCreator,
        image_upload_factory: UploadImageFormFactory,
        editor_factory: RichTextEditorFactory;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        image_upload_factory = {
            createHelpBlock: (): null => null,
            initiateImageUpload: noop,
            forbidImageUpload: noop,
        };
        editor_factory = {
            createRichTextEditor: vi.fn(),
        } as unknown as RichTextEditorFactory;
        creator = new RichTextEditorsCreator(doc, image_upload_factory, editor_factory);
    });

    describe(`createNewFollowupEditor()`, () => {
        it(`when there is no "new followup" textarea in the document, it does nothing`, () => {
            const createRichTextEditor = vi.spyOn(editor_factory, "createRichTextEditor");

            creator.createNewFollowupEditor();

            expect(createRichTextEditor).not.toHaveBeenCalled();
        });

        describe(`when there is a "new followup" textarea in the document`, () => {
            let textarea: HTMLTextAreaElement;
            beforeEach(() => {
                textarea = doc.createElement("textarea");
                textarea.id = "tracker_followup_comment_new";
                doc.body.append(textarea);
            });

            it(`enables image upload and creates a rich text editor on it`, () => {
                const createRichTextEditor = vi.spyOn(editor_factory, "createRichTextEditor");
                const createHelpBlock = vi.spyOn(image_upload_factory, "createHelpBlock");

                creator.createNewFollowupEditor();

                expect(createHelpBlock).toHaveBeenCalled();
                expect(createRichTextEditor).toHaveBeenCalled();
                const options = createRichTextEditor.mock.calls[0][1];

                expect(options.format_selectbox_id).toBe("new");
            });

            it(`sets up the onEditorInit callback`, () => {
                const initiateImageUpload = vi.spyOn(image_upload_factory, "initiateImageUpload");
                const createRichTextEditor = vi.spyOn(editor_factory, "createRichTextEditor");

                creator.createNewFollowupEditor();

                const options = createRichTextEditor.mock.calls[0][1];
                if (options.onEditorInit === undefined) {
                    throw new Error(
                        "Expected an onEditorInit callback to be passed to rich text editor factory, but none was passed",
                    );
                }
                const fake_ckeditor = {} as CKEDITOR.editor;
                options.onEditorInit(fake_ckeditor, textarea);

                expect(initiateImageUpload).toHaveBeenCalled();
            });
        });
    });

    describe(`createEditFollowupEditor()`, () => {
        it(`when there is no "edit followup" textarea in the document, it does nothing`, () => {
            const createRichTextEditor = vi.spyOn(editor_factory, "createRichTextEditor");

            creator.createEditFollowupEditor(1, "text");

            expect(createRichTextEditor).not.toHaveBeenCalled();
        });

        describe(`when there is an "edit followup" textarea in the document`, () => {
            let textarea: HTMLTextAreaElement;
            beforeEach(() => {
                textarea = doc.createElement("textarea");
                textarea.id = "tracker_followup_comment_edit_1";
                doc.body.append(textarea);
            });

            it(`disables image upload and creates a rich text editor on it`, () => {
                const createRichTextEditor = vi.spyOn(editor_factory, "createRichTextEditor");
                const createHelpBlock = vi.spyOn(image_upload_factory, "createHelpBlock");

                creator.createEditFollowupEditor(1, "html");

                expect(createHelpBlock).not.toHaveBeenCalled();
                expect(createRichTextEditor).toHaveBeenCalled();
                const options = createRichTextEditor.mock.calls[0][1];

                expect(options.format_selectbox_id).toBe("1");
                expect(options.format_selectbox_value).toBe("html");
            });

            it(`sets up the onEditorInit callback`, () => {
                const forbidImageUpload = vi.spyOn(image_upload_factory, "forbidImageUpload");
                const createRichTextEditor = vi.spyOn(editor_factory, "createRichTextEditor");

                creator.createEditFollowupEditor(1, "text");

                const options = createRichTextEditor.mock.calls[0][1];
                if (options.onEditorInit === undefined) {
                    throw new Error(
                        "Expected an onEditorInit callback to be passed to rich text editor factory, but none was passed",
                    );
                }
                const fake_ckeditor = {} as CKEDITOR.editor;
                options.onEditorInit(fake_ckeditor, textarea);

                expect(forbidImageUpload).toHaveBeenCalled();
            });
        });
    });

    describe(`createTextFieldEditors()`, () => {
        beforeEach(() => {
            class FakeIntersectionObserver implements IntersectionObserver {
                constructor(private callback: IntersectionObserverCallback) {}
                readonly root: Element | null = null;
                readonly rootMargin: string = "";
                readonly thresholds: ReadonlyArray<number> = [0];
                disconnect(): void {
                    // Fake implementation, no need to do something
                }
                observe(target: Element): void {
                    this.callback(
                        [
                            {
                                target,
                                isIntersecting: true,
                            } as IntersectionObserverEntry,
                        ],
                        this,
                    );
                }
                takeRecords(): IntersectionObserverEntry[] {
                    return [];
                }
                unobserve(): void {
                    // Fake implementation, no need to do something
                }
            }
            window.IntersectionObserver = FakeIntersectionObserver;
        });

        it(`when there is no text field textarea, it does nothing`, () => {
            const createRichTextEditor = vi.spyOn(editor_factory, "createRichTextEditor");

            creator.createTextFieldEditors();

            expect(createRichTextEditor).not.toHaveBeenCalled();
        });

        it(`when a text field textarea has an id that does not end with underscore and its field id,
            it throws`, () => {
            doc.body.insertAdjacentHTML(
                "beforeend",
                `<div class="tracker_artifact_field"><textarea id="bad_id"></textarea>`,
            );

            expect(() => creator.createTextFieldEditors()).toThrow();
        });

        describe(`when there are text field textareas in the document`, () => {
            it(`and no matching hidden input fields,
                it enables image upload and creates a rich text editor on each one
                and defaults the format to "commonmark"`, () => {
                doc.body.insertAdjacentHTML(
                    "beforeend",
                    `<div class="tracker_artifact_field"><textarea id="field_1234"></textarea>`,
                );
                const createRichTextEditor = vi.spyOn(editor_factory, "createRichTextEditor");
                const createHelpBlock = vi.spyOn(image_upload_factory, "createHelpBlock");

                creator.createTextFieldEditors();

                expect(createHelpBlock).toHaveBeenCalled();
                expect(createRichTextEditor).toHaveBeenCalled();
                const options = createRichTextEditor.mock.calls[0][1];

                expect(options.format_selectbox_id).toBe("field_1234");
                expect(options.format_selectbox_name).toBe("artifact[1234][format]");
                expect(options.format_selectbox_value).toStrictEqual(TEXT_FORMAT_COMMONMARK);
            });

            it(`and matching hidden input fields,
                it will pass the hidden input value as selected format option`, () => {
                doc.body.insertAdjacentHTML(
                    "beforeend",
                    `
                    <div class="tracker_artifact_field">
                        <textarea id="field_1234"></textarea>
                        <input type="hidden" id="artifact[1234]_body_format" value="html">
                    </div>
                    <div class="tracker_artifact_field">
                      <textarea id="field_4567"></textarea>
                      <input type="hidden" id="artifact[4567]_body_format" value="text">
                  </div>`,
                );
                const createRichTextEditor = vi.spyOn(editor_factory, "createRichTextEditor");
                const createHelpBlock = vi.spyOn(image_upload_factory, "createHelpBlock");

                creator.createTextFieldEditors();

                expect(createHelpBlock).toHaveBeenCalled();
                expect(createRichTextEditor).toHaveBeenCalled();
                const first_options = createRichTextEditor.mock.calls[0][1];

                expect(first_options.format_selectbox_id).toBe("field_1234");
                expect(first_options.format_selectbox_name).toBe("artifact[1234][format]");
                expect(first_options.format_selectbox_value).toStrictEqual(TEXT_FORMAT_HTML);

                const second_options = createRichTextEditor.mock.calls[1][1];

                expect(second_options.format_selectbox_id).toBe("field_4567");
                expect(second_options.format_selectbox_name).toBe("artifact[4567][format]");
                expect(second_options.format_selectbox_value).toStrictEqual(TEXT_FORMAT_TEXT);
            });
        });
    });
});
