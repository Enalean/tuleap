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
import { Option } from "@tuleap/option";
import type { DispatchEvents } from "../../../DispatchEvents";
import { WillDisableSubmit } from "../../../submit/WillDisableSubmit";
import { WillEnableSubmit } from "../../../submit/WillEnableSubmit";
import type { RetrieveProjects } from "./RetrieveProjects";
import type { Project } from "../../../Project";
import { ProjectsRetrievalFault } from "./ProjectsRetrievalFault";

type OnFaultHandler = (fault: Fault) => void;

export type ArtifactCreatorController = {
    registerFaultListener(handler: OnFaultHandler): void;
    getProjects(): PromiseLike<readonly Project[]>;
    disableSubmit(reason: string): void;
    enableSubmit(): void;
};

export const ArtifactCreatorController = (
    event_dispatcher: DispatchEvents,
    projects_retriever: RetrieveProjects
): ArtifactCreatorController => {
    let _handler: Option<OnFaultHandler> = Option.nothing();
    return {
        registerFaultListener: (handler): void => {
            _handler = Option.fromValue(handler);
        },

        getProjects: () =>
            projects_retriever.getProjects().match(
                (projects) => projects,
                (fault) => {
                    _handler.apply((handler) => handler(ProjectsRetrievalFault(fault)));
                    return [];
                }
            ),

        disableSubmit(reason): void {
            event_dispatcher.dispatch(WillDisableSubmit(reason));
        },

        enableSubmit(): void {
            event_dispatcher.dispatch(WillEnableSubmit());
        },
    };
};
