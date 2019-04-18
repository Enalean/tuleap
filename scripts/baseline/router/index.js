/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

import Vue from "vue";
import VueRouter from "vue-router";
import NotFoundPage from "./NotFoundPage.vue";
import BaselineContentPage from "../components/baseline-content/ContentPage.vue";
import HomePage from "../components/HomePage.vue";
import TransientComparisonPage from "../components/comparison/TransientComparisonPage.vue";
import ComparisonPageAsync from "../components/comparison/ComparisonPageAsync.vue";
import store from "../store";

Vue.use(VueRouter);

function toInt(string) {
    return parseInt(string, 10);
}

const router = new VueRouter({
    mode: "history",
    routes: [
        {
            path: "*",
            component: NotFoundPage
        },

        {
            path: "/plugins/baseline/:project_name",
            name: "HomePage",
            component: HomePage
        },

        {
            path: "/plugins/baseline/:project_name/baselines/:baseline_id",
            name: "BaselineContentPage",
            component: BaselineContentPage,
            props: route => ({
                baseline_id: toInt(route.params.baseline_id)
            })
        },

        {
            path: "/plugins/baseline/:project_name/comparisons/:from_baseline_id/:to_baseline_id",
            name: "TransientComparisonPage",
            component: TransientComparisonPage,
            props: route => ({
                from_baseline_id: toInt(route.params.from_baseline_id),
                to_baseline_id: toInt(route.params.to_baseline_id)
            })
        },

        {
            path: "/plugins/baseline/:project_name/comparisons/:comparison_id",
            name: "ComparisonPage",
            component: ComparisonPageAsync,
            props: route => ({
                comparison_id: toInt(route.params.comparison_id)
            })
        }
    ],
    scrollBehavior: (to, from, savedPosition) => {
        if (savedPosition) {
            return savedPosition;
        }

        return { x: 0, y: 0 };
    }
});

router.beforeEach((to, from, next) => {
    store.commit("clearNotification");
    next();
});

export default router;
