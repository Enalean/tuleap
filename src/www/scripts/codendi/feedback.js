/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

/* global $:readonly $$:readonly */

/*
 * use this to provide feedback to the user :
 * it inserts the given string into the first element of class feedback_ in the current page
 */

var codendi = codendi || {};

codendi.feedback = {
    log: function (level, msg) {
        var feedback = $("feedback");

        if (!feedback) {
            var main = $$("main")[0];
            feedback = new Element("div", { id: "feedback" });

            if (main) {
                main.insert({ top: feedback });
            } else {
                var content = $$(".main .content")[0];

                if (content) {
                    content.insert({ before: feedback });
                } else {
                    //eslint-disable-next-line no-alert
                    alert(level + ": " + msg);
                    return;
                }
            }
        }

        var current = null;
        if (
            feedback.childElements().size() &&
            (current = feedback.childElements().reverse(0)[0]) &&
            current.hasClassName("feedback_" + level)
        ) {
            current.insert(new Element("li").update(msg));
        } else {
            feedback.insert(
                new Element("ul")
                    .addClassName("feedback_" + level)
                    .insert(new Element("li").update(msg)),
            );
        }
    },
    clear: function () {
        var feedback = $("feedback");
        if (feedback) {
            feedback.empty();
        }
    },
};
