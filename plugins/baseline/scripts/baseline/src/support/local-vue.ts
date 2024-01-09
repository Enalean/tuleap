/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import type Vue from "vue";
import { createLocalVue } from "@vue/test-utils";
import Vuex from "vuex";
import VueDOMPurifyHTML from "vue-dompurify-html";
import VueRouter from "vue-router";
import { initVueGettext } from "@tuleap/vue2-gettext-init";

export const createLocalVueForTests = async (): Promise<typeof Vue> => {
    const local_vue = createLocalVue();
    local_vue.use(Vuex);
    local_vue.use(VueDOMPurifyHTML);
    local_vue.use(VueRouter);
    await initVueGettext(local_vue, () => {
        throw new Error("Fallback to default");
    });
    return local_vue;
};
