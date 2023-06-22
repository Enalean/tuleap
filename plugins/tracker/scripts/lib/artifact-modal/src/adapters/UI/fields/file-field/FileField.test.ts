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

import type { HostElement } from "./FileField";
import {
    FileField,
    getActionButton,
    getAddNewFileToAttachButtonTemplate,
    getAttachedFileTemplate,
    isRequired,
} from "./FileField";
import type { AttachedFileDescription } from "../../../../domain/fields/file-field/AttachedFileDescription";
import { setCatalog } from "../../../../gettext-catalog";
import { AttachedFileDescriptionStub } from "../../../../../tests/stubs/AttachedFileDescriptionStub";
import { NewFileToAttach } from "../../../../domain/fields/file-field/NewFileToAttach";
import type { FileFieldType } from "../../../../domain/fields/file-field/FileFieldType";
import type { FileFieldValueModel } from "../../../../domain/fields/file-field/FileFieldValueModel";
import type { AttachedFileCollection } from "../../../../domain/fields/file-field/FileFieldController";
import { FileFieldController } from "../../../../domain/fields/file-field/FileFieldController";
import { EventDispatcher } from "../../../../domain/EventDispatcher";

jest.mock("pretty-kibibytes", () => {
    return {
        default: (size: number): string => size + " B",
    };
});

describe(`FileField`, () => {
    beforeEach(() => {
        setCatalog({
            getString: (msgid): string => msgid,
        });
    });

    describe.each([[true, false]])(`Action button`, (is_marked_for_removal: boolean) => {
        const selector = is_marked_for_removal
            ? "[data-test=cancel-removal]"
            : "[data-test=mark-for-removal]";
        const file = {
            marked_for_removal: is_marked_for_removal,
        } as AttachedFileDescription;

        const renderButton = (is_disabled: boolean): HTMLButtonElement => {
            const doc = document.implementation.createHTMLDocument();
            const field = {} as FileFieldType;
            const value_model = {
                temporary_files: [file],
                value: [],
            } as unknown as FileFieldValueModel;
            const host = {
                controller: FileFieldController(field, value_model, EventDispatcher()),
            } as unknown as HostElement;

            const target = doc.createElement("div") as unknown as ShadowRoot;
            const render = getActionButton(file, is_disabled);
            render(host, target);

            const button = target.querySelector(selector);
            if (!(button instanceof HTMLButtonElement)) {
                throw new Error("Unable to find an expected element in DOM");
            }
            return button;
        };

        it(`renders a button to mark a file for removal / cancel removal`, () => {
            const button = renderButton(false);
            button.click();
            expect(file.marked_for_removal).toBe(is_marked_for_removal);
        });

        it(`renders a disabled button`, () => {
            const button = renderButton(true);
            expect(button.disabled).toBe(true);
        });
    });

    describe(`Attached file template`, () => {
        const FILE_ID = 503;
        const FILE_NAME = "chelydroid.png";
        const FILE_DESCRIPTION = "wished bezantee";

        const renderFile = (file: AttachedFileDescription): ShadowRoot => {
            const doc = document.implementation.createHTMLDocument();
            const host = {} as HostElement;

            const target = doc.createElement("div") as unknown as ShadowRoot;
            const render = getAttachedFileTemplate(file, false);
            render(host, target);

            return target;
        };

        it(`renders an attached file`, () => {
            const file = AttachedFileDescriptionStub.withImage({
                id: FILE_ID,
                name: FILE_NAME,
                description: FILE_DESCRIPTION,
                marked_for_removal: false,
            });

            const target = renderFile(file);
            const container = target.querySelector("[data-test=attached-file]");
            const preview = target.querySelector("[data-test=attached-file-preview]");
            const preview_link = target.querySelector("[data-test=attached-file-preview-link]");
            const link = target.querySelector("[data-test=attached-file-link]");
            const description = target.querySelector("[data-test=attached-file-description]");

            if (
                !(container instanceof HTMLElement) ||
                !(preview instanceof HTMLElement) ||
                !(preview_link instanceof HTMLAnchorElement) ||
                !(link instanceof HTMLAnchorElement) ||
                !(description instanceof HTMLElement)
            ) {
                throw new Error("Unable to find an expected element in DOM");
            }

            expect(container.classList.contains("marked-for-removal")).toBe(false);
            expect(preview.style.backgroundImage).toContain(file.html_preview_url);
            expect(preview_link.href).toBe(file.html_url);
            expect(link.href).toBe(file.html_url);
            expect(link.textContent?.trim()).toBe(file.name);
            expect(description.textContent?.trim()).toBe(file.description);
        });

        it(`marks a file for removal`, () => {
            const file = AttachedFileDescriptionStub.withImage({ marked_for_removal: true });

            const target = renderFile(file);

            const container = target.querySelector("[data-test=attached-file]");
            if (!(container instanceof HTMLElement)) {
                throw new Error("Unable to find an expected element in DOM");
            }

            expect(container.classList.contains("marked-for-removal")).toBe(true);
        });

        it(`does not set a background image for a file that is not an image`, () => {
            const file = AttachedFileDescriptionStub.withNotAnImage();

            const target = renderFile(file);

            const preview = target.querySelector("[data-test=attached-file-preview]");
            if (!(preview instanceof HTMLElement)) {
                throw new Error("Unable to find an expected element in DOM");
            }

            expect(preview.style.backgroundImage).toBe("");
        });
    });

    describe(`New File to attach button`, () => {
        const value_model = { temporary_files: [] } as unknown as FileFieldValueModel;

        const renderButton = (disabled: boolean): HTMLButtonElement => {
            const doc = document.implementation.createHTMLDocument();
            const target = doc.createElement("div") as unknown as ShadowRoot;
            const field = {} as FileFieldType;
            const host = {
                field,
                disabled,
                controller: FileFieldController(field, value_model, EventDispatcher()),
            } as unknown as HostElement;

            const render = getAddNewFileToAttachButtonTemplate(host);
            render(host, target);

            const button = target.querySelector("[data-test=add-new-file]");
            if (!(button instanceof HTMLButtonElement)) {
                throw new Error("Unable to find an expected element in DOM");
            }
            return button;
        };

        it(`renders a button to add a new file to attach`, () => {
            const button = renderButton(false);
            button.click();
            expect(value_model.temporary_files).toHaveLength(1);
        });

        it(`disables the button when the field is disabled`, () => {
            const button = renderButton(true);
            expect(button.disabled).toBe(true);
        });
    });

    describe(`File field template`, () => {
        const FIELD_LABEL = "Attachments";
        const value_model = {
            temporary_files: [NewFileToAttach.build()],
        } as unknown as FileFieldValueModel;

        const renderField = (): ShadowRoot => {
            const doc = document.implementation.createHTMLDocument();
            const target = doc.createElement("div") as unknown as ShadowRoot;
            const field = {
                label: FIELD_LABEL,
                file_descriptions: undefined,
                required: false,
            } as FileFieldType;
            const host = {
                field,
                disabled: false,
                controller: FileFieldController(field, value_model, EventDispatcher()),
                new_files: value_model.temporary_files,
                attached_files: undefined,
            } as unknown as HostElement;

            const render = FileField.content(host);
            render(host, target);

            return target;
        };

        it(`renders a file field`, () => {
            const target = renderField();
            const label = target.querySelector("[data-test=file-field-label]");
            if (!(label instanceof HTMLElement)) {
                throw new Error("Unable to find an expected element in DOM");
            }

            expect(label.textContent?.trim()).toBe(FIELD_LABEL);
        });

        const triggerEvent = (event: CustomEvent): void => {
            const target = renderField();
            const new_file_element = target.querySelector("[data-test=new-file-to-attach]");
            if (!(new_file_element instanceof HTMLElement)) {
                throw new Error("Unable to find an expected element in DOM");
            }
            new_file_element.dispatchEvent(event);
        };

        it(`when it receives a "file-changed" event from the New file to attach element,
            it will dispatch a "file-changed" event with added info`, () => {
            const file = new File([], "a_file.txt");
            triggerEvent(new CustomEvent("file-changed", { detail: { file } }));

            expect(value_model.temporary_files[0].file).toBe(file);
        });

        it(`when it receives a "description-changed" event from the New file to attach element,
            it will dispatch a "description-changed" event with added info`, () => {
            const DESCRIPTION = "acidify aminoid";
            triggerEvent(
                new CustomEvent("description-changed", { detail: { description: DESCRIPTION } })
            );

            expect(value_model.temporary_files[0].description).toBe(DESCRIPTION);
        });

        it(`when it receives a "reset" event from the New file to attach element,
            it will dispatch a "reset-file" event with added info`, () => {
            triggerEvent(new CustomEvent("reset"));

            expect(value_model.temporary_files[0]).toStrictEqual(NewFileToAttach.build());
        });
    });

    describe(`File field is required`, () => {
        it(`when the field is configured as required and there is no attached file then the field is required`, () => {
            const host = {
                attached_files: undefined,
                field: {
                    required: true,
                } as FileFieldType,
            } as HostElement;

            expect(isRequired(host)).toBe(true);
        });

        it(`when the field is configured as not required and there is no attached file then the field is not required`, () => {
            const host = {
                attached_files: undefined,
                field: {
                    required: false,
                } as FileFieldType,
            } as HostElement;

            expect(isRequired(host)).toBe(false);
        });

        it(`when the field is configured as required and there is at least an uploaded file not marked as removed then the field is not required`, () => {
            const attached_files: AttachedFileCollection = [
                { marked_for_removal: false } as AttachedFileDescription,
                { marked_for_removal: true } as AttachedFileDescription,
            ];

            const host = {
                attached_files,
                field: {
                    required: true,
                } as FileFieldType,
            } as HostElement;

            expect(isRequired(host)).toBe(false);
        });

        it(`when the field is configured as required and all uploaded file are marked as removed then the field is required`, () => {
            const attached_files: AttachedFileCollection = [
                { marked_for_removal: true } as AttachedFileDescription,
                { marked_for_removal: true } as AttachedFileDescription,
            ];

            const host = {
                attached_files,
                field: {
                    required: true,
                } as FileFieldType,
            } as HostElement;

            expect(isRequired(host)).toBe(true);
        });
    });
});
