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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import type { Vue } from "vue/types/vue";
import Vuex from "vuex";
import VueDOMPurifyHTML from "@tuleap/vue2-dompurify-html";
import { createLocalVue } from "@vue/test-utils";
import { initVueGettext } from "@tuleap/vue2-gettext-init";

export async function createTaskboardLocalVue(): Promise<typeof Vue> {
    const local_vue = createLocalVue();
    await initVueGettext(local_vue, () => {
        throw new Error("Fallback to default");
    });
    local_vue.use(Vuex);
    // @ts-expect-error Vue 2.7.8 and 2.7.16 types do not play well together
    local_vue.use(VueDOMPurifyHTML);

    return local_vue;
}
