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
    getMatchingArtifactErrorMessage,
    getPossibleParentErrorMessage,
    getSearchArtifactsErrorMessage,
    getUserHistoryErrorMessage,
} from "../../gettext-catalog";

export interface ErrorMessageFormatter {
    format(fault: Fault): string;
}

const isLinkRetrievalFault = (fault: Fault): boolean =>
    "isLinkRetrieval" in fault && fault.isLinkRetrieval() === true;
const isMatchingArtifactFault = (fault: Fault): boolean =>
    "isMatchingArtifactRetrieval" in fault && fault.isMatchingArtifactRetrieval() === true;
const isPossibleParentsFault = (fault: Fault): boolean =>
    "isPossibleParentsRetrieval" in fault && fault.isPossibleParentsRetrieval() === true;
const isUserHistoryFault = (fault: Fault): boolean =>
    "isUserHistoryRetrieval" in fault && fault.isUserHistoryRetrieval() === true;
const isSearchArtifactsFault = (fault: Fault): boolean =>
    "isSearchArtifacts" in fault && fault.isSearchArtifacts() === true;

export const ErrorMessageFormatter = (): ErrorMessageFormatter => ({
    format(fault): string {
        if (isLinkRetrievalFault(fault)) {
            return sprintf(getLinkFieldFetchErrorMessage(), fault);
        }
        if (isMatchingArtifactFault(fault)) {
            return sprintf(getMatchingArtifactErrorMessage(), fault);
        }
        if (isPossibleParentsFault(fault)) {
            return sprintf(getPossibleParentErrorMessage(), fault);
        }
        if (isUserHistoryFault(fault)) {
            return sprintf(getUserHistoryErrorMessage(), fault);
        }
        if (isSearchArtifactsFault(fault)) {
            return sprintf(getSearchArtifactsErrorMessage(), fault);
        }
        return String(fault);
    },
});
