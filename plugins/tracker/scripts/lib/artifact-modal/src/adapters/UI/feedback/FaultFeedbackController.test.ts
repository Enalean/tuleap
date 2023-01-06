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
import { EventDispatcher } from "../../../domain/EventDispatcher";
import { WillNotifyFault } from "../../../domain/WillNotifyFault";
import { WillClearFaultNotification } from "../../../domain/WillClearFaultNotification";

describe(`FaultFeedbackController`, () => {
    let event_dispatcher: EventDispatcher;
    beforeEach(() => {
        event_dispatcher = EventDispatcher();
    });

    describe(`when a WillNotifyFault event is observed`, () => {
        it(`will notify its pre-registered handler with a FaultFeedbackPresenter`, () => {
            const controller = FaultFeedbackController(event_dispatcher);
            const handler = jest.fn();
            controller.registerFaultListener(handler);

            const fault = Fault.fromMessage("Ooops");
            event_dispatcher.dispatch(WillNotifyFault(fault));

            const presenter = handler.mock.calls[0][0];
            expect(presenter.message).toBe(String(fault));
        });
    });

    describe(`when a WillClearFaultNotification event is observed`, () => {
        it(`will notify its pre-registered handler with an empty presenter to clear the notification`, () => {
            const controller = FaultFeedbackController(event_dispatcher);
            const handler = jest.fn();
            controller.registerFaultListener(handler);

            event_dispatcher.dispatch(WillClearFaultNotification());

            const presenter = handler.mock.calls[0][0];
            expect(presenter.message).toBe("");
        });
    });
});
