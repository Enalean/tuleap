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
import { RetrieveProjectsStub } from "../../../../../tests/stubs/RetrieveProjectsStub";

describe(`ArtifactCreatorController`, () => {
    let event_dispatcher: DispatchEventsStub, projects_retriever: RetrieveProjects;

    beforeEach(() => {
        event_dispatcher = DispatchEventsStub.withRecordOfEventTypes();
        const first_project: Project = { id: 184, label: "Lucky Sledgehammer" };
        const second_project: Project = { id: 198, label: "Indigo Sun" };
        projects_retriever = RetrieveProjectsStub.withProjects(first_project, second_project);
    });
    const getController = (): ArtifactCreatorController =>
        ArtifactCreatorController(event_dispatcher, projects_retriever);

    describe(`getProjects()`, () => {
        it(`will return a list of projects`, async () => {
            const projects = await getController().getProjects();
            expect(projects).toHaveLength(2);
        });

        it(`when there is a problem,
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
