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
 * @TODO Write something meaningful here
 */
var codendi = codendi || { };
codendi.tracker = codendi.tracker || { };

document.observe('dom:loaded', function() {
    this.url = codendi.tracker.base_url +'/www/dateReminder.php';
    $('add_reminder').observe('click', function (evt) {
        var reminderDiv = new Element('div');
        reminderDiv.insert(this.url);
        Element.insert($('tracker_reminder'), reminderDiv);
        Event.stop(evt);
        return false;
    });
    $('update_reminder').observe('click', function (evt) {
        var reminderDiv = new Element('div');
        reminderDiv.insert(this.url);
        Element.insert($('update_reminder'), reminderDiv);
        Event.stop(evt);
        return false;
    });
});