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

import { setCatalog } from "../../../gettext-catalog";
import type { HostElement } from "./FollowupEditor";
import { FollowupEditor } from "./FollowupEditor";
import { FormattedTextController } from "../../../domain/common/FormattedTextController";
import { DispatchEventsStub } from "../../../../tests/stubs/DispatchEventsStub";
import { TEXT_FORMAT_TEXT } from "@tuleap/plugin-tracker-constants";
import { InterpretCommonMarkStub } from "../../../../tests/stubs/InterpretCommonMarkStub";

function getHost(data: Partial<HostElement>): HostElement {
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

describe(`FollowupEditor`, () => {
    let target: ShadowRoot;
    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;
        setCatalog({ getString: (msgid) => msgid });
    });

    describe(`events`, () => {
        let host: HostElement;
        beforeEach(() => {
            host = getHost({ contentValue: "previous content", format: "text" });
            const update = FollowupEditor.content(host);
            update(host, target);
        });

        it(`when the RichTextEditor emits a "content-change" event,
            it will emit a "value-changed" event with the new content`, () => {
            const dispatch = jest.spyOn(host, "dispatchEvent");
            getSelector("[data-test=text-editor]").dispatchEvent(
                new CustomEvent("content-change", {
                    detail: { content: "chrysopid" },
                }),
            );

            const value_changed = dispatch.mock.calls[0][0];
            if (!(value_changed instanceof CustomEvent)) {
                throw new Error("Expected a CustomEvent");
            }
            expect(value_changed.type).toBe("value-changed");
            expect(value_changed.detail.body).toBe("chrysopid");
            expect(value_changed.detail.format).toBe("text");
            expect(host.contentValue).toBe("chrysopid");
        });

        it(`when the RichTextEditor emits a "format-change" event,
            it will emit a "value-changed" event with the new format and the new content`, () => {
            const dispatch = jest.spyOn(host, "dispatchEvent");
            getSelector("[data-test=text-editor]").dispatchEvent(
                new CustomEvent("format-change", {
                    detail: { format: "commonmark", content: "chrysopid" },
                }),
            );

            const value_changed = dispatch.mock.calls[0][0];
            if (!(value_changed instanceof CustomEvent)) {
                throw new Error("Expected a CustomEvent");
            }
            expect(value_changed.type).toBe("value-changed");
            expect(value_changed.detail.body).toBe("chrysopid");
            expect(value_changed.detail.format).toBe("commonmark");
            expect(host.format).toBe("commonmark");
            expect(host.contentValue).toBe("chrysopid");
        });
    });

    function getSelector(selector: string): HTMLElement {
        const selected = target.querySelector(selector);
        if (!(selected instanceof HTMLElement)) {
            throw new Error("Could not select element");
        }
        return selected;
    }
});
