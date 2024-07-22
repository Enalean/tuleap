/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */
import { createRouter, createWebHistory } from "vue-router";
import AllProjectTemplates from "../components/Template/AllProjectTemplates.vue";
import ProjectInformation from "../components/Information/ProjectInformation.vue";
import ProjectApproval from "../components/Approval/ProjectApproval.vue";
import ProjectOngoingCreation from "../components/Template/Advanced/FromProjectArchive/ProjectOnGoingCreation/ProjectOngoingCreation.vue";

export const router = createRouter({
    history: createWebHistory("/project"),
    routes: [
        {
            path: "/new",
            name: "template",
            component: AllProjectTemplates,
        },
        {
            path: "/new-information",
            name: "information",
            component: ProjectInformation,
        },
        {
            path: "/approval",
            name: "approval",
            component: ProjectApproval,
        },
        {
            path: "/from-archive-creation/:project_id",
            name: "from-archive-creation",
            component: ProjectOngoingCreation,
            props: (route): { project_id: number } => ({
                project_id: Number(route.params.project_id),
            }),
        },
    ],
});
