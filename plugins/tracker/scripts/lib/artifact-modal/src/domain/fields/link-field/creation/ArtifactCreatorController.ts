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
import type { Tracker } from "../../../Tracker";
import { ProjectsRetrievalFault } from "./ProjectsRetrievalFault";
import type { RetrieveProjectTrackers } from "./RetrieveProjectTrackers";
import { ProjectTrackersRetrievalFault } from "./ProjectTrackersRetrievalFault";
import { ProjectIdentifier } from "../../../ProjectIdentifier";
import type { CurrentProjectIdentifier } from "../../../CurrentProjectIdentifier";
import { TrackerIdentifier } from "../../../TrackerIdentifier";
import type { ArtifactCrossReference } from "../../../ArtifactCrossReference";
import type { LinkableArtifact } from "../LinkableArtifact";
import type { CurrentTrackerIdentifier } from "../../../CurrentTrackerIdentifier";

type OnFaultHandler = (fault: Fault) => void;

export type ArtifactCreatorController = {
    registerFaultListener(handler: OnFaultHandler): void;
    getProjects(): PromiseLike<readonly Project[]>;
    selectProjectAndGetItsTrackers(project_id: ProjectIdentifier): PromiseLike<readonly Tracker[]>;
    selectTracker(tracker_id: TrackerIdentifier): void;
    createArtifact(title: string): PromiseLike<LinkableArtifact>;
    disableSubmit(reason: string): void;
    enableSubmit(): void;
    getSelectedProject(): ProjectIdentifier;
    getSelectedTracker(): Option<TrackerIdentifier>;
    getUserLocale(): string;
};

export const ArtifactCreatorController = (
    event_dispatcher: DispatchEvents,
    projects_retriever: RetrieveProjects,
    project_trackers_retriever: RetrieveProjectTrackers,
    current_project_identifier: CurrentProjectIdentifier,
    current_tracker_identifier: CurrentTrackerIdentifier,
    user_locale: string
): ArtifactCreatorController => {
    let _handler: Option<OnFaultHandler> = Option.nothing(),
        selected_project: ProjectIdentifier = ProjectIdentifier.fromCurrentProject(
            current_project_identifier
        ),
        selected_tracker: Option<TrackerIdentifier> = Option.fromValue(
            TrackerIdentifier.fromCurrentTracker(current_tracker_identifier)
        );

    const findSelectedTracker = (trackers: ReadonlyArray<Tracker>): Option<Tracker> =>
        selected_tracker.andThen((selected) => {
            const selected_tracker_found = trackers.find((tracker) => selected.id === tracker.id);
            return selected_tracker_found
                ? Option.fromValue(selected_tracker_found)
                : Option.nothing();
        });

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

        selectProjectAndGetItsTrackers(project_id): PromiseLike<readonly Tracker[]> {
            const is_new_project = project_id !== selected_project;
            selected_project = project_id;
            return project_trackers_retriever.getTrackersByProject(selected_project).match(
                (trackers) => {
                    if (is_new_project) {
                        selected_tracker = Option.nothing();
                    }
                    findSelectedTracker(trackers).apply((tracker) => {
                        if (tracker.cannot_create_reason !== "") {
                            selected_tracker = Option.nothing();
                        }
                    });
                    return trackers;
                },
                (fault) => {
                    _handler.apply((handler) => handler(ProjectTrackersRetrievalFault(fault)));
                    return [];
                }
            );
        },

        selectTracker(tracker_id): void {
            selected_tracker = Option.fromValue(tracker_id);
        },

        createArtifact(title): PromiseLike<LinkableArtifact> {
            const fake_cross_reference: ArtifactCrossReference = {
                ref: "art #-1",
                color: "inca-silver",
            };
            const fake_new_artifact: LinkableArtifact = {
                id: -1,
                xref: fake_cross_reference,
                title,
                uri: "/",
                is_open: true,
                status: { value: "Ongoing", color: "sherwood-green" },
                project: { id: selected_project.id, label: "Fake Project for development purpose" },
            };

            return Promise.resolve(fake_new_artifact);
        },

        disableSubmit(reason): void {
            event_dispatcher.dispatch(WillDisableSubmit(reason));
        },

        enableSubmit(): void {
            event_dispatcher.dispatch(WillEnableSubmit());
        },

        getSelectedProject: () => selected_project,

        getSelectedTracker: () => selected_tracker,

        getUserLocale: () => user_locale,
    };
};
