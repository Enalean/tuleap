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
import type { WillDisableSubmit } from "../../../submit/WillDisableSubmit";
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
import type { LinkableArtifact } from "../LinkableArtifact";
import type { CurrentTrackerIdentifier } from "../../../CurrentTrackerIdentifier";
import type { CreateLinkableArtifact } from "./CreateLinkableArtifact";
import { ArtifactCreationFault } from "../../../ArtifactCreationFault";

type OnFaultHandler = (fault: Fault) => void;

export type ArtifactCreatorController = {
    registerFaultListener(handler: OnFaultHandler): void;
    getProjects(event: WillDisableSubmit): PromiseLike<readonly Project[]>;
    selectProjectAndGetItsTrackers(
        project_id: ProjectIdentifier,
        event: WillDisableSubmit,
    ): PromiseLike<readonly Tracker[]>;
    selectTracker(tracker_id: TrackerIdentifier): Option<TrackerIdentifier>;
    clearTracker(): Option<TrackerIdentifier>;
    createArtifact(title: string, event: WillDisableSubmit): PromiseLike<Option<LinkableArtifact>>;
    enableSubmit(): void;
    getSelectedProject(): ProjectIdentifier;
    getSelectedTracker(): Option<TrackerIdentifier>;
    getUserLocale(): string;
};

export const ArtifactCreatorController = (
    event_dispatcher: DispatchEvents,
    projects_retriever: RetrieveProjects,
    project_trackers_retriever: RetrieveProjectTrackers,
    artifact_creator: CreateLinkableArtifact,
    current_project_identifier: CurrentProjectIdentifier,
    current_tracker_identifier: CurrentTrackerIdentifier,
    user_locale: string,
): ArtifactCreatorController => {
    let _handler: Option<OnFaultHandler> = Option.nothing(),
        selected_project: ProjectIdentifier = ProjectIdentifier.fromCurrentProject(
            current_project_identifier,
        ),
        selected_tracker: Option<TrackerIdentifier> = Option.fromValue(
            TrackerIdentifier.fromCurrentTracker(current_tracker_identifier),
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

        getProjects(event: WillDisableSubmit): PromiseLike<readonly Project[]> {
            event_dispatcher.dispatch(event);
            return projects_retriever.getProjects().match(
                (projects) => projects,
                (fault) => {
                    _handler.apply((handler) => handler(ProjectsRetrievalFault(fault)));
                    return [];
                },
            );
        },

        selectProjectAndGetItsTrackers(
            project_id,
            event: WillDisableSubmit,
        ): PromiseLike<readonly Tracker[]> {
            const is_new_project = project_id !== selected_project;
            selected_project = project_id;
            event_dispatcher.dispatch(event);
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
                },
            );
        },

        selectTracker(tracker_id): Option<TrackerIdentifier> {
            selected_tracker = Option.fromValue(tracker_id);
            return selected_tracker;
        },

        clearTracker(): Option<TrackerIdentifier> {
            selected_tracker = Option.nothing();
            return selected_tracker;
        },

        createArtifact(title, event: WillDisableSubmit): PromiseLike<Option<LinkableArtifact>> {
            event_dispatcher.dispatch(event);
            return selected_tracker
                .mapOr(
                    (tracker_identifier) =>
                        artifact_creator.createLinkableArtifact(tracker_identifier, title).match(
                            (artifact): Option<LinkableArtifact> => Option.fromValue(artifact),
                            (fault) => {
                                _handler.apply((handler) => handler(ArtifactCreationFault(fault)));
                                return Option.nothing();
                            },
                        ),
                    Promise.resolve(Option.nothing<LinkableArtifact>()),
                )
                .then((option) => {
                    event_dispatcher.dispatch(WillEnableSubmit());
                    return option;
                });
        },

        enableSubmit(): void {
            event_dispatcher.dispatch(WillEnableSubmit());
        },

        getSelectedProject: () => selected_project,

        getSelectedTracker: () => selected_tracker,

        getUserLocale: () => user_locale,
    };
};
