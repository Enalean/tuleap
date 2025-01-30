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

import { Fault } from "@tuleap/fault";
import { FaultFeedbackController } from "./FaultFeedbackController";
import type { EventDispatcherType } from "../AllEvents";
import { EventDispatcher, WillClearFaultNotification, WillNotifyFault } from "../AllEvents";

describe(`FaultFeedbackController`, () => {
    let event_dispatcher: EventDispatcherType;
    beforeEach(() => {
        event_dispatcher = EventDispatcher();
    });

    describe(`when it receives a WillNotifyFault event`, () => {
        it(`will notify its pre-registered handler with a Fault`, () => {
            const controller = FaultFeedbackController(event_dispatcher);
            const handler = jest.fn();
            controller.registerFaultListener(handler);

            const fault = Fault.fromMessage("Ooops");
            event_dispatcher.dispatch(WillNotifyFault(fault));

            const fault_option = handler.mock.calls[0][0];
            expect(fault_option.unwrapOr(null)).toBe(fault);
        });
    });

    describe(`when it receives a WillClearFaultNotification event`, () => {
        it(`will notify its pre-registered handler with nothing to clear the notification`, () => {
            const controller = FaultFeedbackController(event_dispatcher);
            const handler = jest.fn();
            controller.registerFaultListener(handler);

            event_dispatcher.dispatch(WillClearFaultNotification());

            const fault_option = handler.mock.calls[0][0];
            expect(fault_option.isNothing()).toBe(true);
        });
    });
});
