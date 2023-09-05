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

import type { DispatchEvents } from "../DispatchEvents";
import { WillGetFileUploadSetup } from "../fields/file-field/WillGetFileUploadSetup";

export interface FileUploadQuotaControllerType {
    getMaxAllowedUploadSizeInBytes(): Promise<number>;
}

export const FileUploadQuotaController = (
    event_dispatcher: DispatchEvents,
): FileUploadQuotaControllerType => ({
    getMaxAllowedUploadSizeInBytes(): Promise<number> {
        return new Promise((resolve) => {
            // wait for File field controllers to observe the event
            setTimeout(() => {
                const event = WillGetFileUploadSetup();
                event_dispatcher.dispatch(event);
                const max_upload_size = event.setup.mapOr((setup) => setup.max_size_upload, 0);
                resolve(max_upload_size);
            });
        });
    },
});
