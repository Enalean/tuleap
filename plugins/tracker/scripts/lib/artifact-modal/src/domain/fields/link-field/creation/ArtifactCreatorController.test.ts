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

describe(`ArtifactCreatorController`, () => {
    const CURRENT_PROJECT_ID = 101,
        CURRENT_TRACKER_ID = 219;
    let event_dispatcher: DispatchEventsStub,
        projects_retriever: RetrieveProjects,
        tracker_retriever: RetrieveProjectTrackers;

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
    });

    const getController = (): ArtifactCreatorController =>
        ArtifactCreatorController(
            event_dispatcher,
            projects_retriever,
            tracker_retriever,
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

            controller.selectProjectAndGetItsTrackers(project_id);

            expect(controller.getSelectedProject()).toBe(project_id);
        });
    });

    describe(`getSelectedTracker()`, () => {
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

            await controller.selectProjectAndGetItsTrackers(ProjectIdentifierStub.withId(835));

            expect(controller.getSelectedTracker().isNothing()).toBe(true);
        });

        it(`after calling selectProjectAndGetItsTrackers() with the project already selected,
            it will keep the selected tracker memorized`, async () => {
            const controller = getController();

            await controller.selectProjectAndGetItsTrackers(controller.getSelectedProject());

            expect(controller.getSelectedTracker().unwrapOr(null)?.id).toBe(CURRENT_TRACKER_ID);
        });
    });

    describe(`getProjects()`, () => {
        it(`will return a list of projects`, async () => {
            const projects = await getController().getProjects();
            expect(projects).toHaveLength(2);
        });

        it(`when there is a problem when projects are retrieved,
            it will call the previously registered Fault listener
            and it will return an empty array`, async () => {
            const fault = Fault.fromMessage("Not found");
            projects_retriever = RetrieveProjectsStub.withFault(fault);
            const handler = jest.fn();

            const controller = getController();
            controller.registerFaultListener(handler);
            const projects = await controller.getProjects();

            expect(projects).toHaveLength(0);
            expect(handler).toHaveBeenCalled();
        });
    });

    describe(`selectProjectAndGetItsTrackers()`, () => {
        let project_id: ProjectIdentifier;
        beforeEach(() => {
            project_id = ProjectIdentifierStub.withId(184);
        });

        it(`will return a list of trackers`, async () => {
            const projects = await getController().selectProjectAndGetItsTrackers(project_id);
            expect(projects).toHaveLength(2);
        });

        it(`when there is a problem when trackers are retrieved,
            it will call the previously registered Fault listener
            and it will return an empty array`, async () => {
            const fault = Fault.fromMessage("Not found");
            tracker_retriever = RetrieveProjectTrackersStub.withFault(fault);
            const handler = jest.fn();

            const controller = getController();
            controller.registerFaultListener(handler);
            const trackers = await controller.selectProjectAndGetItsTrackers(project_id);

            expect(trackers).toHaveLength(0);
            expect(handler).toHaveBeenCalled();
        });
    });

    describe(`createArtifact()`, () => {
        it(`will create an artifact with the given title
            and will return a LinkableArtifact`, async () => {
            const title = "nonmathematical procontinuation";
            const artifact = await getController().createArtifact(title);

            expect(artifact.title).toBe(title);
        });
    });

    describe(`disableSubmit()`, () => {
        it(`will dispatch an event to disable the modal submit`, () => {
            getController().disableSubmit("No you cannot");
            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillDisableSubmit");
        });
    });

    describe(`enableSubmit()`, () => {
        it(`will dispatch an event to enable the modal submit`, () => {
            getController().enableSubmit();
            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillEnableSubmit");
        });
    });
});
