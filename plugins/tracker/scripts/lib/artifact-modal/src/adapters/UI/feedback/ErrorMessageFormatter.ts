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
    getArtifactCreationErrorMessage,
    getCommentsRetrievalErrorMessage,
    getFileUploadErrorMessage,
    getLinkFieldFetchErrorMessage,
    getMatchingArtifactErrorMessage,
    getParentFetchErrorMessage,
    getPossibleParentErrorMessage,
    getSearchArtifactsErrorMessage,
    getUserHistoryErrorMessage,
} from "../../../gettext-catalog";

export type ErrorMessageFormatter = {
    format(fault: Fault): string;
};

const isLinkRetrievalFault = (fault: Fault): boolean =>
    "isLinkRetrieval" in fault && fault.isLinkRetrieval() === true;
const isParentRetrievalFault = (fault: Fault): boolean =>
    "isParentRetrieval" in fault && fault.isParentRetrieval() === true;
const isMatchingArtifactRetrievalFault = (fault: Fault): boolean =>
    "isMatchingArtifactRetrieval" in fault && fault.isMatchingArtifactRetrieval() === true;
const isPossibleParentsRetrievalFault = (fault: Fault): boolean =>
    "isPossibleParentsRetrieval" in fault && fault.isPossibleParentsRetrieval() === true;
const isUserHistoryFault = (fault: Fault): boolean =>
    "isUserHistoryRetrieval" in fault && fault.isUserHistoryRetrieval() === true;
const isSearchArtifacts = (fault: Fault): boolean =>
    "isSearchArtifacts" in fault && fault.isSearchArtifacts() === true;
const isCommentsRetrieval = (fault: Fault): boolean =>
    "isCommentsRetrieval" in fault && fault.isCommentsRetrieval() === true;
const isArtifactCreation = (fault: Fault): boolean =>
    "isArtifactCreation" in fault && fault.isArtifactCreation() === true;
const isFileUploadFault = (fault: Fault): boolean =>
    "isFileUpload" in fault && fault.isFileUpload() === true;

export const ErrorMessageFormatter = (): ErrorMessageFormatter => ({
    format: (fault): string => {
        if (isLinkRetrievalFault(fault)) {
            return sprintf(getLinkFieldFetchErrorMessage(), fault);
        }
        if (isParentRetrievalFault(fault)) {
            return sprintf(getParentFetchErrorMessage(), fault);
        }
        if (isMatchingArtifactRetrievalFault(fault)) {
            return sprintf(getMatchingArtifactErrorMessage(), fault);
        }
        if (isPossibleParentsRetrievalFault(fault)) {
            return sprintf(getPossibleParentErrorMessage(), fault);
        }
        if (isUserHistoryFault(fault)) {
            return sprintf(getUserHistoryErrorMessage(), fault);
        }
        if (isSearchArtifacts(fault)) {
            return sprintf(getSearchArtifactsErrorMessage(), fault);
        }
        if (isCommentsRetrieval(fault)) {
            return sprintf(getCommentsRetrievalErrorMessage(), fault);
        }
        if (isArtifactCreation(fault)) {
            return sprintf(getArtifactCreationErrorMessage(), fault);
        }
        if (isFileUploadFault(fault)) {
            return sprintf(getFileUploadErrorMessage(), {
                file_name: fault.getFileName(),
                error: fault,
            });
        }
        return String(fault);
    },
});
