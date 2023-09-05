/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
 *
 * Originally written by Nicolas Terray, 2008
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

/* global $$:readonly Ajax:readonly $:readonly CKEDITOR:readonly Ajax:readonly */

var codendi = codendi || {};
codendi.tracker = codendi.tracker || {};
codendi.tracker.artifact = {};

var tuleap = tuleap || {};
tuleap.textarea = tuleap.textarea || {};

codendi.tracker.artifact.editor = {
    warnOnPageLeave: function () {
        if (
            "tracker" in tuleap &&
            "artifact" in tuleap.tracker &&
            "editionSwitcher" in tuleap.tracker.artifact
        ) {
            var edition_switcher = new tuleap.tracker.artifact.editionSwitcher();

            if (edition_switcher.submissionBarIsAlreadyActive()) {
                return codendi.locales.tracker_formelement_admin.lose_follows;
            }
        }
    },
};

function invertFollowups(followupSection) {
    var element = followupSection.down(".tracker_artifact_followups").cleanWhitespace();
    var elements = [];
    var len = element.childNodes.length;
    for (var i = len - 1; i >= 0; --i) {
        const child_node = element.childNodes[i];
        child_node.remove();
        elements.push(child_node);
    }
    for (var j = 0; j < len; ++j) {
        element.appendChild(elements[j]);
    }
}

document.observe("dom:loaded", function () {
    function bindShowHideFieldsets() {
        $$(".show-fieldsets").each(function (button) {
            function showFieldsets() {
                $$(".tracker_artifact_fieldset_hidden").each(function (fieldset) {
                    fieldset.removeClassName("tracker_artifact_fieldset_hidden");
                    fieldset.addClassName("tracker_artifact_fieldset_hidden_visible");
                });
            }

            button.observe("click", function () {
                showFieldsets();
            });
        });

        $$(".hide-fieldsets").each(function (button) {
            function hideFieldsets() {
                $$(".tracker_artifact_fieldset_hidden_visible").each(function (fieldset) {
                    fieldset.addClassName("tracker_artifact_fieldset_hidden");
                    fieldset.removeClassName("tracker_artifact_fieldset_hidden_visible");
                });
            }

            button.observe("click", function () {
                hideFieldsets();
            });
        });
    }

    document.addEventListener("EditModalLoaded", function () {
        bindShowHideFieldsets();
    });
    bindShowHideFieldsets();

    $$(".tracker_artifact_followup_header").each(function (header) {
        if (header.up().next()) {
            header.observe("mouseover", function () {
                header.setStyle({ cursor: "pointer" });
            });
            header.observe("click", function (evt) {
                if (
                    Event.element(evt).hasClassName("tracker_artifact_followup_permalink") ||
                    Event.element(evt).hasClassName("fa-link")
                ) {
                    header.nextSiblings().invoke("show");
                } else {
                    header.down(".tracker_artifact_followup_comment_controls").toggle();
                    header.nextSiblings().invoke("toggle");
                    header.previousSiblings().invoke("toggle");
                }
            });
        }
    });

    $$("#tracker_artifact_followup_comments").each(function (followup_section) {
        //We only have one followup_section but I'm too lazy to do a if()

        function toggleCheckForCommentOrder() {
            $("invert-order-menu-item").down("i").toggle();
        }
        function toggleCheckForDisplayChanges() {
            $("display-changes-menu-item").down("i").toggle();
        }

        var display_changes_classname = "tracker_artifact_followup_comments-display_changes";

        $("invert-order-menu-item")
            .up()
            .observe("click", function (evt) {
                toggleCheckForCommentOrder();
                invertFollowups(followup_section);
                new Ajax.Request(codendi.tracker.base_url + "invert_comments_order.php", {
                    parameters: {
                        tracker: $("tracker_id").value,
                    },
                });
                Event.stop(evt);
                return false;
            });

        $("display-changes-menu-item")
            .up()
            .observe("click", function (evt) {
                followup_section.toggleClassName(display_changes_classname);
                toggleCheckForDisplayChanges();
                new Ajax.Request(codendi.tracker.base_url + "invert_display_changes.php");
                Event.stop(evt);
            });
    });

    $$(".tracker_artifact_add_attachment").each(function (attachment) {
        var add = new Element("a", {
            href: "#add-another-file",
        })
            .update(codendi.locales.tracker_formelement_admin.add_another_file)
            .observe("click", function (evt) {
                Event.stop(evt);

                //clone the first attachment selector (file and description inputs)
                var new_attachment = $(attachment.cloneNode(true));

                //clear the cloned input
                new_attachment.select("input").each(function (input) {
                    input.value = "";
                });

                //Add the remove button
                new_attachment.down("p").insert(
                    new Element("div").insert(
                        new Element("a", { href: "#remove-attachment" })
                            .addClassName("tracker_artifact_remove_attachment")
                            .update("<span>remove</span>")
                            .observe("click", function (evt) {
                                Event.stop(evt);
                                new_attachment.remove();
                            }),
                    ),
                );
                //insert the new attachment selector
                add.insert({ before: new_attachment });
            });
        attachment.insert({ after: add });
    });

    if ($("tracker_artifact_canned_response_sb")) {
        var artifact_followup_comment_has_changed = $("tracker_followup_comment_new").value !== "";
        $("tracker_followup_comment_new").observe("change", function () {
            artifact_followup_comment_has_changed = $("tracker_followup_comment_new").value !== "";
        });
        $("tracker_artifact_canned_response_sb").observe("change", function (evt) {
            var sb = Event.element(evt);
            var value = "";
            if (artifact_followup_comment_has_changed) {
                value += $("tracker_followup_comment_new").value;
                if (sb.getValue()) {
                    if (value.substring(value.length - 1) !== "\n") {
                        value += "\n\n";
                    } else if (value.substring(value.length - 2) !== "\n\n") {
                        value += "\n";
                    }
                }
            }
            value += sb.getValue();
            $("tracker_followup_comment_new").value = value;
            if (CKEDITOR.instances && CKEDITOR.instances.tracker_followup_comment_new) {
                CKEDITOR.instances.tracker_followup_comment_new.setData(
                    CKEDITOR.instances.tracker_followup_comment_new.getData() +
                        "<p>" +
                        value +
                        "</p>",
                );
            }
        });
    }

    if ($("tracker_select_tracker")) {
        $("tracker_select_tracker").observe("change", function () {
            const url = this.ownerDocument.location.href;
            const matches = url.match(/tracker=(\d+)/);
            if (parseInt(matches[1], 10) === parseInt(this.value, 10)) {
                return;
            }

            this.ownerDocument.location.href = this.ownerDocument.location.href.gsub(
                /tracker=\d+/,
                "tracker=" + this.value,
            );
        });
    }

    function toggle_tracker_artifact_attachment_delete(elem) {
        if (elem.checked) {
            elem.up().siblings().invoke("addClassName", "tracker_artifact_attachment_deleted");
        } else {
            elem.up().siblings().invoke("removeClassName", "tracker_artifact_attachment_deleted");
        }
    }

    $$(".tracker_artifact_attachment_delete > input[type=checkbox]").each(function (elem) {
        //on load strike (useful when the checkbox is already checked on dom:loaded. (Missing required field for example)
        toggle_tracker_artifact_attachment_delete(elem);
        elem.observe("click", function () {
            toggle_tracker_artifact_attachment_delete(elem);
        });
    });

    // We know it is crappy, but you know what?
    // IE/Chrome/Firefox don't behave the same way!
    // So if you have a better solution…

    window.onbeforeunload = codendi.tracker.artifact.editor.warnOnPageLeave;
});
