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

import type { Fault } from "@tuleap/fault";
import { sprintf } from "sprintf-js";
import {
    getLinkFieldFetchErrorMessage,
    getParentFetchErrorMessage,
} from "../../../gettext-catalog";

export type FaultFeedbackPresenter = {
    readonly message: string;
};

const isLinkRetrievalFault = (fault: Fault): boolean =>
    typeof fault.isLinkRetrieval === "function" && fault.isLinkRetrieval();

const isParentRetrievalFault = (fault: Fault): boolean =>
    typeof fault.isParentRetrieval === "function" && fault.isParentRetrieval();

export const FaultFeedbackPresenter = {
    buildEmpty: (): FaultFeedbackPresenter => ({ message: "" }),
    fromFault: (fault: Fault): FaultFeedbackPresenter => {
        if (isLinkRetrievalFault(fault)) {
            return { message: sprintf(getLinkFieldFetchErrorMessage(), fault) };
        }
        if (isParentRetrievalFault(fault)) {
            return { message: sprintf(getParentFetchErrorMessage(), fault) };
        }
        return { message: String(fault) };
    },
};
