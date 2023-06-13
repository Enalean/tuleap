/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
    getProjectsRetrievalErrorMessage,
    getProjectTrackersRetrievalErrorMessage,
} from "../../../../../gettext-catalog";

export type FaultDisplayer = { formatForDisplay(fault: Fault): string };

const isProjectsRetrievalFault = (fault: Fault): boolean =>
    "isProjectsRetrieval" in fault && fault.isProjectsRetrieval() === true;
const isProjectTrackerRetrievalFault = (fault: Fault): boolean =>
    "isProjectTrackersRetrieval" in fault && fault.isProjectTrackersRetrieval() === true;
const isArtifactCreationFault = (fault: Fault): boolean =>
    "isArtifactCreation" in fault && fault.isArtifactCreation() === true;

export const FaultDisplayer = (): FaultDisplayer => ({
    formatForDisplay(fault): string {
        if (isProjectsRetrievalFault(fault)) {
            return sprintf(getProjectsRetrievalErrorMessage(), fault);
        }
        if (isProjectTrackerRetrievalFault(fault)) {
            return sprintf(getProjectTrackersRetrievalErrorMessage(), fault);
        }
        if (isArtifactCreationFault(fault)) {
            return sprintf(getArtifactCreationErrorMessage(), fault);
        }
        return String(fault);
    },
});
