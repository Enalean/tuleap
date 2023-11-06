<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

<template>
    <section class="tlp-modal-body-section">
        <h2 class="tlp-modal-subtitle">{{ $gettext("Conditions of the transition") }}</h2>
        <div class="tlp-form-element">
            <slot name="authorized-ugroups"></slot>
        </div>
        <div class="tlp-form-element" data-test="not-empty-field-form-element">
            <slot name="fields-not-empty"></slot>
        </div>
        <div class="tlp-form-element" v-if="!is_transition_from_new_artifact">
            <slot name="comment-not-empty"></slot>
        </div>
    </section>
</template>

<script>
export default {
    name: "PreConditionsSection",
    props: {
        is_transition_from_new_artifact: Boolean,
    },
};
</script>
