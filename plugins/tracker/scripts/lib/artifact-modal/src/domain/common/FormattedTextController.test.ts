/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import { EventDispatcher } from "../EventDispatcher";
import type { FormattedTextControllerType } from "./FormattedTextController";
import { FormattedTextController } from "./FormattedTextController";
import { DispatchEventsStub } from "../../../tests/stubs/DispatchEventsStub";
import { DidUploadImage } from "../fields/file-field/DidUploadImage";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import { TEXT_FORMAT_COMMONMARK } from "@tuleap/plugin-tracker-constants";
import { Option } from "@tuleap/option";
import { InterpretCommonMarkStub } from "../../../tests/stubs/InterpretCommonMarkStub";
import { WillDisableSubmit } from "../submit/WillDisableSubmit";

const DEFAULT_TEXT_FORMAT: TextFieldFormat = TEXT_FORMAT_COMMONMARK;
const HTML_STRING = "<strong>HTML</strong>";

describe(`FormattedTextController`, () => {
    let dispatcher: EventDispatcher | DispatchEventsStub;

    beforeEach(() => {
        dispatcher = DispatchEventsStub.withRecordOfEventTypes();
    });

    const getController = (): FormattedTextControllerType =>
        FormattedTextController(
            dispatcher,
            InterpretCommonMarkStub.withHTML(HTML_STRING),
            DEFAULT_TEXT_FORMAT
        );

    describe(`getDefaultTextFormat()`, () => {
        it(`returns the default text format (text, html or commonmark)`, () => {
            expect(getController().getDefaultTextFormat()).toBe(DEFAULT_TEXT_FORMAT);
        });
    });

    describe(`getFileUploadSetup()`, () => {
        it(`dispatches an event and returns the contents of that event`, () => {
            dispatcher = EventDispatcher();
            const upload_setup = Option.fromValue({
                max_size_upload: 7000,
                file_creation_uri: "https://example.com/upload",
            });
            dispatcher.addObserver("WillGetFileUploadSetup", (event) => {
                event.setup = upload_setup;
            });

            expect(getController().getFileUploadSetup()).toBe(upload_setup);
        });

        it(`when nobody responds, it returns nothing`, () => {
            expect(getController().getFileUploadSetup().isNothing()).toBe(true);
        });
    });

    describe(`interpretCommonMark()`, () => {
        it(`calls the interpreter and returns the result`, async () => {
            const result = await getController().interpretCommonMark("**CommonMark**");
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toBe(HTML_STRING);
        });
    });

    describe(`onFileUploadStart()`, () => {
        it(`dispatches the event it receives`, () => {
            dispatcher = DispatchEventsStub.withRecordOfEventTypes();
            getController().onFileUploadStart(WillDisableSubmit("Some reason"));

            expect(dispatcher.getDispatchedEventTypes()).toContain("WillDisableSubmit");
        });
    });

    describe(`onFileUploadError()`, () => {
        it(`dispatches an Enable submit event`, () => {
            dispatcher = DispatchEventsStub.withRecordOfEventTypes();
            getController().onFileUploadError();

            expect(dispatcher.getDispatchedEventTypes()).toContain("WillEnableSubmit");
        });
    });

    describe(`onFileUploadSuccess()`, () => {
        it(`dispatches the event it receives and an Enable Submit event`, () => {
            dispatcher = DispatchEventsStub.withRecordOfEventTypes();
            const event = DidUploadImage({
                id: 18,
                download_href: "https://example.com/download/18",
            });
            getController().onFileUploadSuccess(event);

            const dispatched_events = dispatcher.getDispatchedEventTypes();
            expect(dispatched_events).toContain("WillEnableSubmit");
            expect(dispatched_events).toContain("DidUploadImage");
        });
    });
});
