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

import type { WillDisableSubmit } from "./WillDisableSubmit";
import type { WillEnableSubmit } from "./WillEnableSubmit";
import type { WillNotifyFault } from "./WillNotifyFault";
import type { WillClearFaultNotification } from "./WillClearFaultNotification";
import type { DidChangeLinkFieldValue } from "./DidChangeLinkFieldValue";
import type { EventsMap } from "./EventDispatcher";

export interface CommonEvents extends EventsMap {
    WillClearFaultNotification: WillClearFaultNotification;
    WillDisableSubmit: WillDisableSubmit;
    WillEnableSubmit: WillEnableSubmit;
    WillNotifyFault: WillNotifyFault;
    DidChangeLinkFieldValue: DidChangeLinkFieldValue;
}
