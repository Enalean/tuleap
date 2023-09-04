/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/* global codendi:readonly $:readonly $$:readonly Ajax:readonly */
/**
 * Hide references from the current item to other items
 */
function hide_references_to() {
    var references = $$(".not-condensed .reference_to");
    references.each(function (li) {
        // hide all <li> with class "reference_to"
        li.hide();
        if (
            !li
                .up()
                .childElements()
                .find(function (other_li) {
                    return other_li.visible();
                })
        ) {
            // if no other <li> are visible, hide also <ul> and nature of the reference (previous)
            li.up().hide();
            li.up().previous().hide();
        }
    });
    // display 'show link'
    if (references.size() > 0) {
        $("cross_references_legend").replace(
            '<p id="cross_references_legend">' +
                codendi.getText("cross_ref_fact_include", "legend_referenced_by") +
                ' <span><a href="#" onclick="show_references_to(); return false;">' +
                codendi.getText("cross_ref_fact_include", "show_references_to") +
                "</span></p>",
        );
    }
}

/**
 * Show references from the current item to other items
 */
function show_references_to() {
    var references = $$(".not-condensed .reference_to");
    references.each(function (li) {
        // show all <li> with class "reference_to"
        li.show();
        // shwo also <ul> and nature of the reference (previous)
        li.up().show();
        li.up().previous().show();
    });
    // display 'hide link'
    if (references.size() > 0) {
        $("cross_references_legend").replace(
            '<p id="cross_references_legend">' +
                codendi.getText("cross_ref_fact_include", "legend") +
                ' <span><a href="#" onclick="hide_references_to(); return false;">' +
                codendi.getText("cross_ref_fact_include", "hide_references_to") +
                "</span></p>",
        );
    }
}
window.show_references_to = show_references_to;

function delete_ref(id, message, event) {
    event.stopPropagation();

    //eslint-disable-next-line no-alert
    if (confirm(message)) {
        const opt = {
            method: "get",
            onComplete: function () {
                const is_the_deleted_reference_the_last_one = isTheDeletedReferenceTheLastOne();
                const is_the_full_cross_references_section_empty =
                    isTheFullCrossReferencesSectionEmpty();

                if (
                    is_the_deleted_reference_the_last_one &&
                    !is_the_full_cross_references_section_empty
                ) {
                    $(id).up().hide();
                } else if (
                    is_the_deleted_reference_the_last_one &&
                    is_the_full_cross_references_section_empty
                ) {
                    $(id).up(".nature").hide();
                } else {
                    $(id).remove();
                }

                function isTheFullCrossReferencesSectionEmpty() {
                    if ($(id).up().siblings().length === 0) {
                        return true;
                    }

                    if ($(id).up().siblings().length > 1) {
                        return false;
                    }

                    return $(id).up().siblings().first().tagName.toLowerCase() === "div";
                }

                function isTheDeletedReferenceTheLastOne() {
                    if ($(id).siblings().length === 0) {
                        return true;
                    }

                    if ($(id).siblings().length > 1) {
                        return false;
                    }

                    // New cross references can have a section label
                    // If there is a one sibling of the deleted element and it's h2 element,
                    // then that was the last element
                    if ($(id).tagName.toLowerCase() === "div") {
                        return $(id).siblings().first().tagName.toLowerCase() === "h2";
                    }

                    // In legacy cross references, icon is a sibling of the deleted element
                    // If there is only one sibling of deleted element and it's icon,
                    // then that was the last element
                    return $(id).siblings().first().tagName.toLowerCase() === "img";
                }
            },
        };
        new Ajax.Updater("id", $(id).down(".delete_ref").readAttribute("data-href"), opt);
    }
    return false;
}
window.delete_ref = delete_ref;

document.observe("dom:loaded", function () {
    //hide reference to item to clean the ui
    if ($("cross_references_legend")) {
        hide_references_to();
    }

    //hide the delete ref icon to clean the ui
    $$(".link_to_ref").each(function (l) {
        if (l.down(".delete_ref")) {
            var a = l.down(".delete_ref");
            var img = a.down("img");
            img.src = img.src.replace("cross.png", "cross-disabled.png");
            img.observe("mouseover", function () {
                img.src = img.src.replace("cross-disabled.png", "cross.png");
            });
            img.observe("mouseout", function () {
                img.src = img.src.replace("cross.png", "cross-disabled.png");
            });
        }
    });
});
