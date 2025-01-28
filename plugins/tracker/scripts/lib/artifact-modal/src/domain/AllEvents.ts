/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import type {
    CommonEvents,
    DispatchEvents as DispatchCommon,
    EventDispatcher as CommonDispatcher,
    ObserveEvents as ObserveCommon,
} from "@tuleap/plugin-tracker-artifact-common";
import type { DidChangeListFieldValue } from "./fields/select-box-field/DidChangeListFieldValue";
import type { DidChangeAllowedValues } from "./fields/select-box-field/DidChangeAllowedValues";
import type { DidUploadImage } from "./fields/file-field/DidUploadImage";
import type { WillGetFileUploadSetup } from "./fields/file-field/WillGetFileUploadSetup";

export {
    EventDispatcher,
    WillClearFaultNotification,
    WillDisableSubmit,
    WillEnableSubmit,
    WillNotifyFault,
} from "@tuleap/plugin-tracker-artifact-common";
export type { DomainEvent } from "@tuleap/plugin-tracker-artifact-common";

export interface AllEvents extends CommonEvents {
    DidChangeListFieldValue: DidChangeListFieldValue;
    DidChangeAllowedValues: DidChangeAllowedValues;
    DidUploadImage: DidUploadImage;
    WillGetFileUploadSetup: WillGetFileUploadSetup;
}

export type DispatchEvents = DispatchCommon<AllEvents>;
export type ObserveEvents = ObserveCommon<AllEvents>;
export type EventDispatcherType = CommonDispatcher<AllEvents>;
