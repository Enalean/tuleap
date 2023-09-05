/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/* global ProtoMultiSelect:readonly $:readonly $$:readonly */

var codendi = codendi || {};
codendi.tracker = codendi.tracker || {};

codendi.tracker.textboxlist = {
    init: function () {
        $$(".textboxlist-auto").each(function (textbox) {
            if (textbox.id && textbox.id.match(/_\d+$/)) {
                var id = textbox.id.match(/_(\d+)$/)[1];
                if ($("tracker_field_" + id)) {
                    codendi.tracker.textboxlist[id] = new ProtoMultiSelect(
                        "tracker_field_" + id,
                        textbox.id,
                        {
                            fetchFile:
                                codendi.tracker.base_url +
                                "?formElement=" +
                                id +
                                "&func=textboxlist",
                            loadOnInit: false,
                            newValues: true,
                            newValuePrefix: "!",
                            encodeEntities: true,
                        },
                    );
                }
            }
        });
    },
};

document.observe("dom:loaded", function () {
    codendi.tracker.textboxlist.init();
});
