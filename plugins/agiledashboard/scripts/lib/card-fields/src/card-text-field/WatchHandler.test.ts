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

import type { RequestAnimationFrame, WatchHandlerType, CardField } from "./WatchHandler";
import { WatchHandler } from "./WatchHandler";
import { PurifyHTMLStub } from "../../tests/stubs/PurifyHTMLStub";

describe(`WatchHandler`, () => {
    let doc: Document, target_element: Element, purifier: PurifyHTMLStub, card_field: CardField;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        target_element = doc.createElement("span");
        purifier = PurifyHTMLStub.withParserAndCount(doc);

        card_field = {
            format: "html",
            value: `Researcher <a href="https://example.com">A link</a>`,
        };
    });

    const getHandler = (): WatchHandlerType => {
        const request_animation_frame_stub: RequestAnimationFrame = {
            requestAnimationFrame(callback: () => void) {
                callback();
            },
        };
        return WatchHandler(doc, request_animation_frame_stub, target_element, purifier);
    };

    describe(`init()`, () => {
        const runInit = (): void => {
            getHandler().init(card_field);
        };

        it(`when the card field's format is "html",
            it will purify its value and replace the children of target_element
            by the resulting DocumentFragment's children`, () => {
            runInit();
            expect(target_element.childNodes).toHaveLength(2);
        });

        it(`when the card field's format is "text",
            it will create a Text node from its value
            and replace the children of target_element by the Text node`, () => {
            card_field = { format: "text", value: "Some text content" };
            runInit();
            expect(target_element.textContent).toBe(card_field.value);
        });
    });

    describe(`onWatch()`, () => {
        const SEARCH = "sear";
        let previous_card_field: CardField, previous_search: string;

        beforeEach(() => {
            previous_card_field = { format: "html", value: "" };
            previous_search = "";
        });

        const runOnWatch = (): void => {
            getHandler().onWatch([SEARCH, card_field], [previous_search, previous_card_field]);
        };

        it(`when neither the filter nor the card field has changed, it will do nothing`, () => {
            previous_card_field = card_field;
            previous_search = SEARCH;
            runOnWatch();
            expect(target_element.childNodes).toHaveLength(0);
        });

        it(`when the card field has changed, it will invalidate the purifier cache
            and replace the children of target_element by highlighted nodes`, () => {
            runOnWatch();
            expect(purifier.getCallCount()).toBe(1);
            expect(target_element.childNodes).toHaveLength(4);
        });

        it(`when the card field's format is "html", it will purify its value
            and replace the children of target_element by highlighted nodes`, () => {
            runOnWatch();
            expect(target_element.childNodes).toHaveLength(4);
        });

        it(`when the card field's format is "text",
            it will replace the children of target_element by highlighted nodes`, () => {
            card_field = { format: "text", value: "Researcher" };
            runOnWatch();
            expect(target_element.childNodes).toHaveLength(3);
        });
    });
});
