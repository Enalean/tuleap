/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

/**
 * This script display a hidden div that contains new reminder submission button then listen to any
 * reminder creation request and delegate process ot the right function within tracker class.
 */
var codendi = codendi || { };
codendi.tracker = codendi.tracker || { };

document.observe('dom:loaded', function() {
    $('tracker_reminder').show();
    $('add_reminder').observe('click', function (evt) {
    var url = codendi.tracker.base_url +'?func=display_reminder_form&tracker='+$('add_reminder').value;
    var target = 'tracker_reminder';
    var myAjax = new Ajax.Updater(target, url, {method: 'get'});
    });
});