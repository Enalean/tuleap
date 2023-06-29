/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { under_the_fold_notification_event_source } from "./event/UnderTheFoldNotificationEventDispatcher";

function controller() {
    under_the_fold_notification_event_source.addListener(this);
    const self = this;
    Object.assign(self, {
        is_displayed: false,
        handle: () => {
            self.is_displayed = true;
        },
        hide: () => {
            self.is_displayed = false;
        },
    });
}

export default {
    template: `
<div
    id="under-the-fold-notification"
    class="kanban-notification tlp-alert-success"
    ng-class="{'kanban-notification-shown': $ctrl.is_displayed}"
    ng-on-transitionend="$ctrl.hide()"
>
    <span translate>The card has been created below.</span>
    <i class="fa fa-arrow-down kanban-new-item-under-the-fold-notification-icon"></i>
</div>
    `,
    controller,
};
