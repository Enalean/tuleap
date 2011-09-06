/**
 * Copyright (c) STMicroelectronics 2011. All rights reserved
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

var ProjectHistory = Class.create({
    initialize: function (sub_events_array, selected_sub_events) {
        if (!sub_events_array) {
            throw 'sub_events_array is mandatory!';
        }
        this.sub_events_array = sub_events_array;
        var title = $('history_search_title');
        title.observe('click', this.toggleForm);
        // We may make the form hidden by default
        //$('project_history_search').hide();
        Event.observe($('events_box'), 'change', this.SelectSubEvent.bindAsEventListener(this));
        // Load sub events content when page loads
        this.SelectSubEvent(selected_sub_events);
     },
    toggleForm: function() {
        // Toggle search form
        $('project_history_search').toggle();
        // Switch icon plus/minus
        var icon = $('toggle_form_icon');
        if (icon.src.indexOf('toggle_plus.png') != -1) {
            icon.src = icon.src.replace('toggle_plus.png', 'toggle_minus.png');
        } else {
            icon.src = icon.src.replace('toggle_minus.png', 'toggle_plus.png');
        }
    },
    SelectSubEvent: function(selected_sub_events) {
        this.removeAllOptions($('sub_events_box'));
        this.addOption('choose', 'choose_event', false, true);

        event = $('events_box').value;
        SubEvents = this.sub_events_array[event];
        for (key in SubEvents) {
            this.addOption(event, key, selected_sub_events[key]);
        }
    },
    removeAllOptions: function(selectbox) {
        var i;
        for (i = selectbox.options.length-1; i>=0; i--) {
            selectbox.remove(i);
        }
    },
    addOption: function(event, value, selected, disabled) {
        var optn = Builder.node('option', {'value' : value}, this.sub_events_array[event][value]);
        $('sub_events_box').appendChild(optn);
        if (selected) {
            optn.selected = true;
        } else {
            optn.selected = false;
        }
        if (disabled) {
            optn.disabled = true;
        } else {
            optn.disabled = false;
        }
    }
});