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
import type { Fault } from "@tuleap/fault";
import type { EventDispatcher } from "../EventDispatcher";

export type OnFaultHandler = (fault_option: Option<Fault>) => void;

export type FaultFeedbackControllerType = {
    registerFaultListener(handler: OnFaultHandler): void;
};

export const FaultFeedbackController = (
    event_dispatcher: EventDispatcher,
): FaultFeedbackControllerType => ({
    registerFaultListener: (handler: OnFaultHandler): void => {
        event_dispatcher.addObserver("WillNotifyFault", (event) => {
            handler(Option.fromValue(event.fault));
        });
        event_dispatcher.addObserver("WillClearFaultNotification", () => {
            handler(Option.nothing());
        });
    },
});
