/*
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

import dragula from 'dragula';
import $ from 'jquery';

export default init;

function init() {
    dragulaInit();
}

function dragulaInit() {
    var drake = dragula({
        isContainer: function (el) {
            return el.classList.contains('dragula-container');
        },
        moves: function (el, source, handle, sibling) {
            return handle.dataset.draggable === 'true';
        }
    });

    cancelDropOnEscape(drake);

    drake.on('drop', function(el, target) {
        reorderWidget(el, target);
    });
}

function cancelDropOnEscape(drake) {
    document.onkeydown = function(event) {
        event = event || window.event;
        if (event.keyCode === 27) {
            drake.cancel(true);
        }
    };
}

function reorderWidget(widget, target) {
    var csrf_token    = widget.querySelector('input[name=challenge]').value;
    var dashboard_id  = document.querySelector('.dashboard-widgets-container').dataset.dashboardId;
    var widget_id     = widget.dataset.widgetId;
    var new_column_id = target.dataset.columnId;
    var new_rank      = 0;

    if (! dashboard_id || ! widget_id || ! new_column_id) {
        return;
    }

    var elements = target.querySelectorAll('.dashboard-widget');
    [].forEach.call(elements, function (widget, index) {
        if (widget.dataset.widgetId === widget_id) {
            new_rank = index;
        }
    });

    $.ajax({
        url : window.location.href,
        type: 'POST',
        data: {
            'challenge'    : csrf_token,
            'action'       : 'reorder-widgets',
            'dashboard-id' : dashboard_id,
            'new-column-id': new_column_id,
            'widget-id'    : widget_id,
            'new-rank'     : new_rank
        }
    });
}
