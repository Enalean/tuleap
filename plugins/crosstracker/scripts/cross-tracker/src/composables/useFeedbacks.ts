/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import type { Ref } from "vue";
import { ref } from "vue";
import type { Fault } from "@tuleap/fault";
import { Option } from "@tuleap/option";

export type NotifyFaultCallback = (fault: Fault) => void;
export type ClearFeedbacksCallback = () => void;

export type FeedbacksController = {
    current_fault: Ref<Option<Fault>>;
    current_success: Ref<Option<string>>;
    notifyFault: NotifyFaultCallback;
    notifySuccess(message: string): void;
    clearFeedbacks: ClearFeedbacksCallback;
};

export const useFeedbacks = (): FeedbacksController => {
    const current_fault = ref<Option<Fault>>(Option.nothing());
    const current_success = ref<Option<string>>(Option.nothing());

    function notifyFault(fault: Fault): void {
        current_fault.value = Option.fromValue(fault);
    }

    function notifySuccess(message: string): void {
        current_success.value = Option.fromValue(message);
    }

    function clearFeedbacks(): void {
        current_fault.value = Option.nothing();
        current_success.value = Option.nothing();
    }

    return {
        current_fault,
        current_success,
        notifyFault,
        notifySuccess,
        clearFeedbacks,
    };
};
