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

describe(`FaultFeedbackController`, () => {
    describe(`onFault`, () => {
        it(`will notify its pre-registered handler with a FaultFeedbackPresenter`, () => {
            const controller = FaultFeedbackController();
            const handler = jest.fn();
            controller.registerFaultListener(handler);

            const fault = Fault.fromMessage("Ooops");
            controller.onFault(fault);

            const presenter = handler.mock.calls[0][0];
            expect(presenter.message).toBe(String(fault));
        });
    });

    describe(`clearFaultNotification`, () => {
        it(`will notify its pre-registered handler with an empty presenter to clear the notification`, () => {
            const controller = FaultFeedbackController();
            const handler = jest.fn();
            controller.registerFaultListener(handler);

            controller.clearFaultNotification();

            const presenter = handler.mock.calls[0][0];
            expect(presenter.message).toBe("");
        });
    });
});
