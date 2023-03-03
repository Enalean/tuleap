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

import type { DidChangeListFieldValue } from "./fields/select-box-field/DidChangeListFieldValue";
import type { WillDisableSubmit } from "./submit/WillDisableSubmit";
import type { WillEnableSubmit } from "./submit/WillEnableSubmit";
import type { WillNotifyFault } from "./WillNotifyFault";
import type { WillClearFaultNotification } from "./WillClearFaultNotification";
import type { DidChangeAllowedValues } from "./fields/select-box-field/DidChangeAllowedValues";
import type { WillGetFileUploadSetup } from "./fields/file-field/WillGetFileUploadSetup";
import type { DidUploadImage } from "./fields/file-field/DidUploadImage";

export type AllEvents = {
    DidChangeAllowedValues: DidChangeAllowedValues;
    DidChangeListFieldValue: DidChangeListFieldValue;
    WillClearFaultNotification: WillClearFaultNotification;
    WillDisableSubmit: WillDisableSubmit;
    WillEnableSubmit: WillEnableSubmit;
    WillNotifyFault: WillNotifyFault;
    WillGetFileUploadSetup: WillGetFileUploadSetup;
    DidUploadImage: DidUploadImage;
};

export type EventType = keyof AllEvents;

export type DomainEvent<TypeOfEvent extends EventType> = {
    readonly type: TypeOfEvent;
};
