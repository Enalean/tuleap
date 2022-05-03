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

import type { NotifyFault } from "../../../domain/NotifyFault";
import { FaultFeedbackPresenter } from "./FaultFeedbackPresenter";
import type { ClearFaultNotification } from "../../../domain/ClearFaultNotification";

export type OnFaultHandler = (presenter: FaultFeedbackPresenter) => void;

export type FaultFeedbackControllerType = NotifyFault &
    ClearFaultNotification & {
        registerFaultListener(handler: OnFaultHandler): void;
    };

export const FaultFeedbackController = (): FaultFeedbackControllerType => {
    let _handler: OnFaultHandler | undefined;

    return {
        registerFaultListener: (handler: OnFaultHandler) => (_handler = handler),

        onFault(fault): void {
            _handler?.(FaultFeedbackPresenter.fromFault(fault));
        },

        clearFaultNotification(): void {
            _handler?.(FaultFeedbackPresenter.buildEmpty());
        },
    };
};
