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

import type { HostElement } from "./TextField";
import { getClasses, getIdentifier, TextField } from "./TextField";
import { FormattedTextController } from "../../../../domain/common/FormattedTextController";
import { DispatchEventsStub } from "../../../../../tests/stubs/DispatchEventsStub";
import { TEXT_FORMAT_TEXT } from "@tuleap/plugin-tracker-constants";
import { InterpretCommonMarkStub } from "../../../../../tests/stubs/InterpretCommonMarkStub";

function getHost(data?: Partial<HostElement>): HostElement {
    return {
        ...data,
        controller: FormattedTextController(
            DispatchEventsStub.buildNoOp(),
            InterpretCommonMarkStub.withHTML(`<p>HTML</p>`),
            TEXT_FORMAT_TEXT,
        ),
        dispatchEvent: jest.fn(),
    } as HostElement;
}

describe(`TextField`, () => {
    describe(`getClasses()`, () => {
        it(`returns the base class`, () => {
            const classes = getClasses(getHost());
            expect(classes["tlp-form-element"]).toBe(true);
        });

        it(`returns the base class and the "disabled" class when the field is disabled`, () => {
            const classes = getClasses(getHost({ disabled: true }));
            expect(classes["tlp-form-element"]).toBe(true);
            expect(classes["tlp-form-element-disabled"]).toBe(true);
        });

        it(`returns the base class and the "error" class when the field is required
            and the content is an empty string`, () => {
            const classes = getClasses(getHost({ required: true, contentValue: "" }));
            expect(classes["tlp-form-element"]).toBe(true);
            expect(classes["tlp-form-element-error"]).toBe(true);
        });

        it(`does not return the "error" class when the field is required and not empty`, () => {
            const classes = getClasses(getHost({ required: true, contentValue: "tendril" }));
            expect(classes["tlp-form-element-error"]).toBe(false);
        });
    });

    it(`prefixes the field's id by "tracker_field_" to identify the editor and format selector`, () => {
        expect(getIdentifier(getHost({ fieldId: 5906 }))).toBe("tracker_field_5906");
    });

    describe(`events`, () => {
        let field_id: number, host: HostElement, target: ShadowRoot;
        beforeEach(() => {
            field_id = 956;
            const doc = document.implementation.createHTMLDocument();
            target = doc.createElement("div") as unknown as ShadowRoot;
            host = getHost({ fieldId: field_id, contentValue: "previous content", format: "text" });
            const update = TextField.content(host);
            update(host, target);
        });

        it(`when the RichTextEditor emits a "content-change" event,
            it will emit a "value-changed" event with the new content`, () => {
            const dispatch = jest.spyOn(host, "dispatchEvent");
            getSelector("[data-test=text-editor]").dispatchEvent(
                new CustomEvent("content-change", {
                    detail: { content: "unhostilely" },
                }),
            );

            const value_changed = dispatch.mock.calls[0][0];
            if (!(value_changed instanceof CustomEvent)) {
                throw new Error("Expected a CustomEvent");
            }
            expect(value_changed.type).toBe("value-changed");
            expect(value_changed.detail.field_id).toBe(field_id);
            expect(value_changed.detail.value.content).toBe("unhostilely");
            expect(value_changed.detail.value.format).toBe("text");
            expect(host.contentValue).toBe("unhostilely");
        });

        it(`when the RichTextEditor emits a "format-change" event,
            it will emit a "value-changed" event with the new format and the new content`, () => {
            const dispatch = jest.spyOn(host, "dispatchEvent");
            getSelector("[data-test=text-editor]").dispatchEvent(
                new CustomEvent("format-change", {
                    detail: { format: "commonmark", content: "unhostilely" },
                }),
            );

            const value_changed = dispatch.mock.calls[0][0];
            if (!(value_changed instanceof CustomEvent)) {
                throw new Error("Expected a CustomEvent");
            }
            expect(value_changed.type).toBe("value-changed");
            expect(value_changed.detail.field_id).toBe(field_id);
            expect(value_changed.detail.value.content).toBe("unhostilely");
            expect(value_changed.detail.value.format).toBe("commonmark");
            expect(host.format).toBe("commonmark");
            expect(host.contentValue).toBe("unhostilely");
        });

        function getSelector(selector: string): HTMLElement {
            const selected = target.querySelector(selector);
            if (!(selected instanceof HTMLElement)) {
                throw new Error("Could not select element");
            }
            return selected;
        }
    });
});
