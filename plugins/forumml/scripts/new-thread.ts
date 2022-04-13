/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { openTargetModalIdOnClick } from "tlp";
import App from "./new-thread/src/components/App.vue";
import Vue from "vue";
import { getPOFileFromLocale, initVueGettext } from "@tuleap/vue2-gettext-init";

document.addEventListener("DOMContentLoaded", async () => {
    openTargetModalIdOnClick(document, "forumml-post-new-thread-button");

    const cc_files_mountpoint = document.getElementById(
        "forumml-post-new-thread-cc-files-mountpoint"
    );
    if (cc_files_mountpoint) {
        await initVueGettext(
            Vue,
            (locale: string) =>
                import(
                    /* webpackChunkName: "new-thread-po-" */ "./new-thread/po/" +
                        getPOFileFromLocale(locale)
                )
        );

        const AppComponent = Vue.extend(App);
        new AppComponent().$mount(cc_files_mountpoint);
    }
});
