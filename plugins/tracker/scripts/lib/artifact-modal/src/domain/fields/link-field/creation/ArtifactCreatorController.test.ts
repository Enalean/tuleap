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
import type { Project } from "../../../Project";
import type { Tracker } from "../../../Tracker";
import { RetrieveProjectsStub } from "../../../../../tests/stubs/RetrieveProjectsStub";
import { RetrieveProjectTrackersStub } from "../../../../../tests/stubs/RetrieveProjectTrackersStub";
import type { RetrieveProjectTrackers } from "./RetrieveProjectTrackers";
import type { ProjectIdentifier } from "../../../ProjectIdentifier";
import { ProjectIdentifierStub } from "../../../../../tests/stubs/ProjectIdentifierStub";

describe(`ArtifactCreatorController`, () => {
    let event_dispatcher: DispatchEventsStub,
        projects_retriever: RetrieveProjects,
        tracker_retriever: RetrieveProjectTrackers,
        project_id: ProjectIdentifier;

    beforeEach(() => {
        event_dispatcher = DispatchEventsStub.withRecordOfEventTypes();
        const first_project: Project = { id: 184, label: "Lucky Sledgehammer" };
        const second_project: Project = { id: 198, label: "Indigo Sun" };
        projects_retriever = RetrieveProjectsStub.withProjects(first_project, second_project);

        const first_tracker: Tracker = { color_name: "deep-blue", label: "V-Series.R" };
        const second_tracker: Tracker = { color_name: "graffiti-yellow", label: "963" };
        tracker_retriever = RetrieveProjectTrackersStub.withTrackers(first_tracker, second_tracker);

        project_id = ProjectIdentifierStub.withId(102);
    });
    const getController = (): ArtifactCreatorController =>
        ArtifactCreatorController(event_dispatcher, projects_retriever, tracker_retriever, "en_US");

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

    describe(`getTrackers()`, () => {
        it(`will return a list of trackers`, async () => {
            const projects = await getController().getTrackers(project_id);
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
            const trackers = await controller.getTrackers(project_id);

            expect(trackers).toHaveLength(0);
            expect(handler).toHaveBeenCalled();
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
