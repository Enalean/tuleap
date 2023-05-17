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

import { ArtifactCreatorController } from "./ArtifactCreatorController";
import { DispatchEventsStub } from "../../../../../tests/stubs/DispatchEventsStub";

describe(`ArtifactCreatorController`, () => {
    let event_dispatcher: DispatchEventsStub;

    beforeEach(() => {
        event_dispatcher = DispatchEventsStub.withRecordOfEventTypes();
    });
    const getController = (): ArtifactCreatorController =>
        ArtifactCreatorController(event_dispatcher);

    describe(`disableSubmit()`, () => {
        it(`will dispatch an event to disable the modal submit`, () => {
            getController().disableSubmit("No you cannot");
            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillDisableSubmit");
        });
    });

    describe(`enableSubmit()`, () => {
        it(`will dispatch an event to enable the modal submit`, () => {
            getController().enableSubmit();
            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillEnableSubmit");
        });
    });
});
