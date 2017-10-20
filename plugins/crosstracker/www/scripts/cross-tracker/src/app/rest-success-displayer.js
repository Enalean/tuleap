/**
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

export default class RestSuccessDisplayer {
    constructor(widget_content) {
        this.success_element = widget_content.querySelector('.dashboard-widget-content-cross-tracker-success');
        this.tlp_alert       = this.success_element.children[0];
    }

    displaySuccess(success_message) {
        this.tlp_alert.textContent = success_message;
        this.success_element.classList.add('shown');
    }

    hideSuccess() {
        this.success_element.classList.remove('shown');
    }
}
