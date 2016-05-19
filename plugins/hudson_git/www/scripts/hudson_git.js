/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

(function ($) {
    function confirmJenkinsDeletionPopover() {
        $('.remove-hook').popover({
            title: codendi.getText('hudson_git', 'remove_jenkins_title'),
            content: $('#remove-jenkins-popover').html()
        });
    }

    function dismissPopover() {
        $('.remove-hook').popover('hide');
    }

    $(function () {
        $('.only-one-jenkins').tooltip();

        confirmJenkinsDeletionPopover();

        $('body').on('click', function(event) {
            if ($(event.target).hasClass('dismiss-popover')) {
                dismissPopover();
            }

            if ($(event.target).data('toggle') !== 'popover' &&
                $(event.target).parents('.popover.in').length === 0 &&
                $(event.target).parents('[data-toggle="popover"]').length === 0
            ) {
                dismissPopover();
            }
        });
    });
}(jQuery));
