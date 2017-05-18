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

import dragula                  from 'dragula';
import { ajax }                 from 'jquery';
import { applyAutomaticLayout } from './dashboard-layout.js';
import { findAncestor }         from './dom-tree-walker.js';

export default init;

function init() {
    var drake = dragula({
        isContainer: function (el) {
            return el.classList.contains('dragula-container');
        },
        moves: function (el, source, handle, sibling) {
            return handle.dataset.draggable === 'true';
        }
    });

    cancelDropOnEscape(drake);

    drake.on('drop', function(el, target, source) {
        updateParent(el, target);
        moveDropdownElementToTheEnd(el.parentElement);
        var source_row = findAncestor(source, 'dashboard-widgets-row');
        var target_row = findAncestor(el.parentElement, 'dashboard-widgets-row');

        reorderWidget(el, el.parentElement).then(function() {
            applyAutomaticLayoutToRow(source_row);
            applyAutomaticLayoutToRow(target_row);
        });
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

        if (column.parentElement && ! column.parentElement.classList.contains('dashboard-widgets-row')) {
            var line = document.createElement('div');
            line.classList.add('dashboard-widgets-row', 'dragula-container');

            column.parentElement.insertBefore(line, column);
            line.appendChild(column);
        }
    }
}

function moveDropdownElementToTheEnd(dragula_container) {
    var row      = findAncestor(dragula_container, 'dashboard-widgets-row');
    var dropdown = row.querySelector('.dashboard-row-dropdown');
    if (! dropdown) { return; }

    dropdown.remove();
    row.appendChild(dropdown);
}

function applyAutomaticLayoutToRow(row) {
    if (! row) { return; }

    applyAutomaticLayout(row);
}

function reorderWidget(widget, column) {
    var line            = column.parentElement;
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

    return ajax({
        url     : window.location.href,
        type    : 'POST',
        dataType: 'json',
        data    : {
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
    }).then(function (response) {
        if (response.new_ids && response.new_ids.new_line_id) {
            line.setAttribute('data-line-id', response.new_ids.new_line_id);
        }

        if (response.new_ids && response.new_ids.new_column_id) {
            column.setAttribute('data-column-id', response.new_ids.new_column_id);
        }

        if (response.deleted_ids && response.deleted_ids.deleted_line_id) {
            var line_to_delete = document.querySelector("[data-line-id='" + response.deleted_ids.deleted_line_id + "']");
            if (line_to_delete) {
                line_to_delete.parentNode.removeChild(line_to_delete);
            }
        }

        if (response.deleted_ids && response.deleted_ids.deleted_column_id) {
            var column_to_delete = document.querySelector("[data-column-id='" + response.deleted_ids.deleted_column_id + "']");
            if (column_to_delete) {
                column_to_delete.parentNode.removeChild(column_to_delete);
            }
        }
    });
}

function getRankOfElement(element) {
    var parent   = element.parentElement;
    var children = parent.children;
    return [].indexOf.call(children, element);
}
