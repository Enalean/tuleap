<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template functional>
    <div class="tlp-modal-body project-admin-services-modal-preview">
        <div class="project-admin-services-modal-preview-sidebar">
            <div class="project-sidebar-nav-item active">
                <i
                    class="fa-fw project-sidebar-nav-item-icon"
                    v-if="props.allowed_icons[props.icon_name]"
                    v-bind:class="[props.allowed_icons[props.icon_name]['fa-icon']]"
                ></i>
                <span
                    class="project-sidebar-nav-item-label project-admin-services-modal-preview-label"
                >
                    {{ props.label }}
                </span>
                <i
                    v-if="props.is_in_new_tab"
                    class="fas fa-arrow-right project-sidebar-nav-item-new-tab"
                ></i>
            </div>
        </div>
    </div>
</template>
