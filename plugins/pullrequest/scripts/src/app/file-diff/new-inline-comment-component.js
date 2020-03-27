/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import "./new-inline-comment.html";

export default {
    templateUrl: "new-inline-comment.html",
    controller,
    bindings: {
        submitCallback: "<",
        codemirrorWidget: "<",
    },
};

export const NAME = "new-inline-comment";

function controller() {
    const self = this;
    Object.assign(self, {
        comment: "",
        is_loading: false,
        submit,
        cancel,
    });

    function submit() {
        self.is_loading = true;
        self.submitCallback(self.comment)
            .then(() => {
                self.codemirrorWidget.clear();
            })
            .finally(() => {
                self.is_loading = false;
            });
    }

    function cancel() {
        self.codemirrorWidget.clear();
    }
}
