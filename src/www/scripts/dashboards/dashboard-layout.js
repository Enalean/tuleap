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
        updateParent(el, target);
        reorderWidget(el, el.parentNode);
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

function updateParent(widget, target) {
    if (target && ! target.classList.contains('dashboard-widgets-column')) {
        var column = document.createElement('div');
        column.classList.add('dashboard-widgets-column', 'dragula-container');

        target.insertBefore(column, widget);
        column.appendChild(widget);

        if (column.parentNode && ! column.parentNode.classList.contains('dashboard-widgets-row')) {
            var line = document.createElement('div');
            line.classList.add('dashboard-widgets-row', 'dragula-container', 'tlp-framed-horizontally');

            column.parentNode.insertBefore(line, column);
            line.appendChild(column);
        }
    }
}

function reorderWidget(widget, column) {
    var line            = column.parentNode;
    var csrf_token      = widget.querySelector('input[name=challenge]').value;
    var dashboard_id    = document.querySelector('.dashboard-widgets-container').dataset.dashboardId;
    var widget_id       = widget.dataset.widgetId;
    var new_column_id   = column.dataset.columnId;
    var new_line_id     = line.dataset.lineId;
    var new_widget_rank = getRankOfElement(widget);
    var new_column_rank = getRankOfElement(column);
    var new_line_rank   = getRankOfElement(line);

    if (! dashboard_id || ! widget_id) {
        return;
    }

    $.ajax({
        url : window.location.href,
        type: 'POST',
        dataType: 'json',
        data: {
            'challenge'      : csrf_token,
            'action'         : 'reorder-widgets',
            'dashboard-id'   : dashboard_id,
            'new-line-id'    : new_line_id,
            'new-column-id'  : new_column_id,
            'widget-id'      : widget_id,
            'new-widget-rank': new_widget_rank,
            'new-column-rank': new_column_rank,
            'new-line-rank'  : new_line_rank
        }
    }).done(function (response) {
        if (response.new_line_id) {
            line.setAttribute('data-line-id', response.new_line_id);
        }

        if (response.new_column_id) {
            column.setAttribute('data-column-id', response.new_column_id);
        }
    });
}

function getRankOfElement(element) {
    var parent   = element.parentElement;
    var children = parent.children;
    return [].indexOf.call(children, element);
}
