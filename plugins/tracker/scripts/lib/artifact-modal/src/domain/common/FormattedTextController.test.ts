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
import type { FileUploadSetup } from "../fields/file-field/FileUploadSetup";
import { DispatchEventsStub } from "../../../tests/stubs/DispatchEventsStub";
import { DidUploadImage } from "../fields/file-field/DidUploadImage";

describe(`FormattedTextController`, () => {
    let dispatcher: EventDispatcher | DispatchEventsStub;

    beforeEach(() => {
        dispatcher = DispatchEventsStub.withRecordOfEventTypes();
    });

    const getController = (): FormattedTextControllerType => FormattedTextController(dispatcher);

    describe(`getFileUploadSetup()`, () => {
        it(`dispatches an event and returns the contents of that event`, () => {
            dispatcher = EventDispatcher();
            const upload_setup: FileUploadSetup = {
                max_size_upload: 7000,
                file_creation_uri: "https://example.com/upload",
            };
            dispatcher.addObserver("WillGetFileUploadSetup", (event) => {
                event.setup = upload_setup;
            });

            expect(getController().getFileUploadSetup()).toBe(upload_setup);
        });

        it(`when nobody responds, it returns null`, () => {
            expect(getController().getFileUploadSetup()).toBeNull();
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
