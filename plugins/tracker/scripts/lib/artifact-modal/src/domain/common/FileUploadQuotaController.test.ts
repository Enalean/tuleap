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

import { Option } from "@tuleap/option";
import { FileUploadQuotaController } from "./FileUploadQuotaController";
import type { EventDispatcherType } from "../AllEvents";
import { EventDispatcher } from "../AllEvents";

const MAX_SIZE_UPLOAD = 7000;
describe(`FileUploadQuotaController`, () => {
    let event_dispatcher: EventDispatcherType;

    beforeEach(() => {
        event_dispatcher = EventDispatcher();
    });

    describe(`getMaxAllowedUploadSizeInBytes()`, () => {
        const getMaxUploadSize = (): Promise<number> => {
            const controller = FileUploadQuotaController(event_dispatcher);
            return controller.getMaxAllowedUploadSizeInBytes();
        };

        it(`will dispatch an event and return the max upload size`, async () => {
            event_dispatcher.addObserver("WillGetFileUploadSetup", (event) => {
                event.setup = Option.fromValue({
                    max_size_upload: MAX_SIZE_UPLOAD,
                    file_creation_uri: "https://example.com/upload",
                });
            });

            await expect(getMaxUploadSize()).resolves.toBe(MAX_SIZE_UPLOAD);
        });

        it(`when there is no File field, it will return zero`, async () => {
            await expect(getMaxUploadSize()).resolves.toBe(0);
        });
    });
});
