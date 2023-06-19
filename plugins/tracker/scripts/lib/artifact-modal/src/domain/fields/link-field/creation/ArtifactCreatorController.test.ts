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

import { Fault } from "@tuleap/fault";
import { ArtifactCreatorController } from "./ArtifactCreatorController";
import { DispatchEventsStub } from "../../../../../tests/stubs/DispatchEventsStub";
import type { RetrieveProjects } from "./RetrieveProjects";
import { RetrieveProjectsStub } from "../../../../../tests/stubs/RetrieveProjectsStub";
import { RetrieveProjectTrackersStub } from "../../../../../tests/stubs/RetrieveProjectTrackersStub";
import type { RetrieveProjectTrackers } from "./RetrieveProjectTrackers";
import { ProjectIdentifierStub } from "../../../../../tests/stubs/ProjectIdentifierStub";
import { CurrentProjectIdentifierStub } from "../../../../../tests/stubs/CurrentProjectIdentifierStub";
import type { ProjectIdentifier } from "../../../ProjectIdentifier";
import { en_US_LOCALE } from "@tuleap/core-constants";
import { TrackerStub } from "../../../../../tests/stubs/TrackerStub";
import { ProjectStub } from "../../../../../tests/stubs/ProjectStub";
import { CurrentTrackerIdentifierStub } from "../../../../../tests/stubs/CurrentTrackerIdentifierStub";
import { TrackerIdentifierStub } from "../../../../../tests/stubs/TrackerIdentifierStub";
import { CreateLinkableArtifactStub } from "../../../../../tests/stubs/CreateLinkableArtifactStub";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";
import type { CreateLinkableArtifact } from "./CreateLinkableArtifact";
import { WillDisableSubmit } from "../../../submit/WillDisableSubmit";

const isProjectsRetrieval = (fault: Fault): boolean =>
    "isProjectsRetrieval" in fault && fault.isProjectsRetrieval() === true;
const isProjectTrackersRetrieval = (fault: Fault): boolean =>
    "isProjectTrackersRetrieval" in fault && fault.isProjectTrackersRetrieval() === true;
const isArtifactCreation = (fault: Fault): boolean =>
    "isArtifactCreation" in fault && fault.isArtifactCreation() === true;

describe(`ArtifactCreatorController`, () => {
    const CURRENT_PROJECT_ID = 101,
        CURRENT_TRACKER_ID = 219;
    let event_dispatcher: DispatchEventsStub,
        projects_retriever: RetrieveProjects,
        tracker_retriever: RetrieveProjectTrackers,
        artifact_creator: CreateLinkableArtifact;

    beforeEach(() => {
        event_dispatcher = DispatchEventsStub.withRecordOfEventTypes();
        projects_retriever = RetrieveProjectsStub.withProjects(
            ProjectStub.withDefaults({ id: 184 }),
            ProjectStub.withDefaults({ id: 198 })
        );

        tracker_retriever = RetrieveProjectTrackersStub.withTrackers(
            TrackerStub.withDefaults({ id: 86 }),
            TrackerStub.withDefaults({ id: 98 })
        );
        artifact_creator = CreateLinkableArtifactStub.withArtifact(
            LinkableArtifactStub.withDefaults()
        );
    });

    const getController = (): ArtifactCreatorController =>
        ArtifactCreatorController(
            event_dispatcher,
            projects_retriever,
            tracker_retriever,
            artifact_creator,
            CurrentProjectIdentifierStub.withId(CURRENT_PROJECT_ID),
            CurrentTrackerIdentifierStub.withId(CURRENT_TRACKER_ID),
            en_US_LOCALE
        );

    describe(`getSelectedProject()`, () => {
        it(`will return the current project id`, () => {
            expect(getController().getSelectedProject().id).toBe(CURRENT_PROJECT_ID);
        });

        it(`after calling selectProjectAndGetItsTrackers() with a project id,
            it will memorize it and return it`, () => {
            const project_id = ProjectIdentifierStub.withId(993);
            const controller = getController();

            controller.selectProjectAndGetItsTrackers(
                project_id,
                WillDisableSubmit("Retrieving trackers")
            );

            expect(controller.getSelectedProject()).toBe(project_id);
        });
    });

    describe(`getSelectedTracker()`, () => {
        let event: WillDisableSubmit;
        beforeEach(() => {
            event = WillDisableSubmit("Retrieving trackers");
        });

        it(`will return the current tracker id`, () => {
            expect(getController().getSelectedTracker().unwrapOr(null)?.id).toBe(
                CURRENT_TRACKER_ID
            );
        });

        it(`after calling selectTracker(),
            it will memorize it and return it`, () => {
            const tracker_id = TrackerIdentifierStub.withId(670);
            const controller = getController();

            controller.selectTracker(tracker_id);

            expect(controller.getSelectedTracker().unwrapOr(null)).toBe(tracker_id);
        });

        it(`after calling selectProjectAndGetItsTrackers() with a different project than the one that was selected,
            it will clear the selected tracker`, async () => {
            const controller = getController();

            await controller.selectProjectAndGetItsTrackers(
                ProjectIdentifierStub.withId(835),
                event
            );

            expect(controller.getSelectedTracker().isNothing()).toBe(true);
        });

        it(`after calling selectProjectAndGetItsTrackers() with the project already selected,
            it will keep the selected tracker memorized`, async () => {
            const controller = getController();

            await controller.selectProjectAndGetItsTrackers(controller.getSelectedProject(), event);

            expect(controller.getSelectedTracker().unwrapOr(null)?.id).toBe(CURRENT_TRACKER_ID);
        });

        it(`after calling selectProjectAndGetItsTrackers()
            when the selected tracker is among the project's trackers
            and user cannot create artifacts in it,
            it will clear the selected tracker`, async () => {
            tracker_retriever = RetrieveProjectTrackersStub.withTrackers(
                TrackerStub.withDefaults({ id: 313 }),
                TrackerStub.withDefaults({
                    id: CURRENT_TRACKER_ID,
                    cannot_create_reason: "Another field is required",
                })
            );
            const controller = getController();

            await controller.selectProjectAndGetItsTrackers(controller.getSelectedProject(), event);

            expect(controller.getSelectedTracker().isNothing()).toBe(true);
        });
    });

    describe(`getProjects()`, () => {
        let event: WillDisableSubmit;
        beforeEach(() => {
            event = WillDisableSubmit("Projects are loading");
        });

        it(`will return a list of projects
            and will disable the modal submit while the promise is not resolved`, async () => {
            const promise = getController().getProjects(event);
            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillDisableSubmit");
            const projects = await promise;

            expect(projects).toHaveLength(2);
        });

        it(`when there is a problem when projects are retrieved,
            it will call the previously registered Fault listener
            and it will return an empty array`, async () => {
            projects_retriever = RetrieveProjectsStub.withFault(Fault.fromMessage("Not found"));
            const handler = jest.fn();
            const controller = getController();
            controller.registerFaultListener(handler);

            const projects = await controller.getProjects(event);

            expect(projects).toHaveLength(0);
            expect(handler).toHaveBeenCalled();
            const fault = handler.mock.calls[0][0];
            expect(isProjectsRetrieval(fault)).toBe(true);
        });
    });

    describe(`selectProjectAndGetItsTrackers()`, () => {
        let project_id: ProjectIdentifier, event: WillDisableSubmit;
        beforeEach(() => {
            project_id = ProjectIdentifierStub.withId(184);
            event = WillDisableSubmit("Retrieving trackers");
        });

        it(`will return a list of trackers
            and will disable the modal submit while the promise is not resolved`, async () => {
            const promise = getController().selectProjectAndGetItsTrackers(project_id, event);
            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillDisableSubmit");
            const projects = await promise;

            expect(projects).toHaveLength(2);
        });

        it(`when there is a problem when trackers are retrieved,
            it will call the previously registered Fault listener
            and it will return an empty array`, async () => {
            tracker_retriever = RetrieveProjectTrackersStub.withFault(
                Fault.fromMessage("Not found")
            );
            const handler = jest.fn();
            const controller = getController();
            controller.registerFaultListener(handler);

            const trackers = await controller.selectProjectAndGetItsTrackers(project_id, event);

            expect(trackers).toHaveLength(0);
            expect(handler).toHaveBeenCalled();
            const fault = handler.mock.calls[0][0];
            expect(isProjectTrackersRetrieval(fault)).toBe(true);
        });
    });

    describe(`createArtifact()`, () => {
        let title: string, event: WillDisableSubmit;
        beforeEach(() => {
            title = "nonmathematical procontinuation";
            event = WillDisableSubmit("Creating artifact");
        });

        it(`will create an artifact with the given title and the selected tracker
            and will disable the modal submit while the creation is ongoing
            and will return a LinkableArtifact`, async () => {
            const expected_artifact = LinkableArtifactStub.withDefaults({ title });
            artifact_creator = CreateLinkableArtifactStub.withArtifact(expected_artifact);

            const promise = getController().createArtifact(title, event);
            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillDisableSubmit");
            const result = await promise;

            expect(result.unwrapOr(null)).toBe(expected_artifact);
            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillEnableSubmit");
        });

        it(`when there is no selected tracker, it will return Nothing`, async () => {
            const controller = getController();
            await controller.selectProjectAndGetItsTrackers(
                ProjectIdentifierStub.withId(835),
                WillDisableSubmit("Retrieving trackers")
            );

            const result = await controller.createArtifact(title, event);

            expect(result.isNothing()).toBe(true);
            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillEnableSubmit");
        });

        it(`when there is a problem,
            it will call the previously registered Fault listener
            and will return Nothing`, async () => {
            artifact_creator = CreateLinkableArtifactStub.withFault(
                Fault.fromMessage("Bad Request")
            );
            const handler = jest.fn();
            const controller = getController();
            controller.registerFaultListener(handler);

            const result = await controller.createArtifact(title, event);

            expect(result.isNothing()).toBe(true);
            expect(handler).toHaveBeenCalled();
            const fault = handler.mock.calls[0][0];
            expect(isArtifactCreation(fault)).toBe(true);
            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillEnableSubmit");
        });
    });

    describe(`enableSubmit()`, () => {
        it(`will dispatch an event to enable the modal submit`, () => {
            getController().enableSubmit();
            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillEnableSubmit");
        });
    });
});
