/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import Vue from "vue";
import VueRouter from "vue-router";
import StepOne from "../components/steps/step-one/StepOne.vue";
import StepTwo from "../components/steps/step-two/StepTwo.vue";

Vue.use(VueRouter);

export function createRouter(project_unix_name: string): VueRouter {
    const STEP_1_NAME = "step-1";
    const STEP_2_NAME = "step-2";
    const base = `/plugins/tracker/${project_unix_name}`;

    const router = new VueRouter({
        base,
        mode: "history",
        routes: [
            {
                path: "/new",
                name: STEP_1_NAME,
                component: StepOne,
            },
            {
                path: "/new-information",
                name: STEP_2_NAME,
                component: StepTwo,
            },
        ],
    });

    router.beforeEach((to, from, next) => {
        // when user refreshes the page on step-2 then return to step-1
        if (to.name === STEP_2_NAME && from.name !== STEP_1_NAME) {
            next({ name: STEP_1_NAME });
        } else {
            next();
        }
    });

    return router;
}
