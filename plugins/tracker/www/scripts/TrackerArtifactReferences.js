/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

var tuleap              = tuleap || { };
tuleap.tracker          = tuleap.tracker || { };
tuleap.tracker.artifact = tuleap.tracker.artifact || { };

(function($) {

tuleap.tracker.artifact.references = function() {

    var init = function() {
        if (tuleap.browserCompatibility.isIE7()) {
            $('.artifact-references').remove();
            return;
        }
        if ($('.artifact-references').size() > 0) {
            handlePaneToggle();
        }
    };

    var handlePaneToggle = function() {
        $('.artifact-references .grip').click(function() {
            $('.artifact-references').toggleClass('expanded');
            $('.artifact-references .grip i').toggleClass('icon-double-angle-right');
        });
    }

    return {
        init: init
    };
};

$(document).ready(function() {
    var artifact_references = new tuleap.tracker.artifact.references();
    artifact_references.init();
});

})(jQuery);