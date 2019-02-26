<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <section class="tlp-popover tlp-popover-danger" ref="popover">
        <div class="tlp-popover-arrow"></div>
        <div class="tlp-popover-header">
            <h1 class="tlp-popover-title" v-translate>Confirm deleting the transition</h1>
        </div>
        <div class="tlp-popover-body">
            <p v-if="props.is_workflow_advanced" key="advanced-text" v-translate>
                Are you sure you want to delete this transition? Its configuration will be lost and
                cannot be recovered.
            </p>
            <p v-else key="simple-text" v-translate>
                Are you sure you want to delete this transition? Since it is the last transition for
                this state, deleting it will also delete the state's configuration. It cannot be
                recovered.
            </p>
        </div>
        <div class="tlp-popover-footer">
            <button
                type="button"
                class="tlp-button-danger tlp-button-outline"
                data-dismiss="popover"
                v-translate
            >
                Cancel
            </button>
            <button
                type="button"
                class="tlp-button-danger"
                v-on:click="props.deleteTransition()"
                data-dismiss="popover"
            >
                <i class="fa fa-trash-o tlp-button-icon"></i>
                <translate>Delete transition</translate>
            </button>
        </div>
    </section>
</template>
