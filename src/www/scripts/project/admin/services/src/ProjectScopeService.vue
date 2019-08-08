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

<template>
    <div class="tlp-modal-body">
        <input type="hidden" name="service_id" v-bind:value="service.id">

        <div class="tlp-form-element">
            <label class="tlp-label" for="project-admin-services-custom-edit-modal-label">
                <translate>Label</translate>
                <i class="fa fa-asterisk"></i>
            </label>
            <input
                type="text"
                class="tlp-input"
                id="project-admin-services-custom-edit-modal-label"
                name="label"
                v-bind:placeholder="label_placeholder"
                size="30"
                maxlength="40"
                required
                v-model="service.label"
            >
        </div>

        <div class="tlp-form-element">
            <label class="tlp-label" for="project-admin-services-custom-edit-modal-link">
                <translate>Link</translate>
                <i class="fa fa-asterisk"></i>
            </label>
            <input
                type="text"
                class="tlp-input"
                id="project-admin-services-custom-edit-modal-link"
                name="link"
                placeholder="https://example.com/my-service/"
                maxlength="255"
                pattern="(https?://|#|/|\?).+"
                v-bind:title="link_title"
                required
                v-model="service.link"
            >
        </div>

        <div class="tlp-form-element">
            <label
                class="tlp-label"
                for="project-admin-services-custom-edit-modal-description"
                v-translate
            >
                Description
            </label>
            <input
                type="text"
                class="tlp-input"
                id="project-admin-services-custom-edit-modal-description"
                name="description"
                v-bind:placeholder="description_placeholder"
                size="70"
                maxlength="255"
                v-model="service.description"
            >
        </div>

        <hr class="tlp-modal-separator">

        <input type="hidden" name="is_active" v-bind:value="is_active_string">

        <div class="tlp-form-element">
            <label class="tlp-label tlp-checkbox">
                <input
                    type="checkbox"
                    name="is_used"
                    value="1"
                    v-bind:checked="service.is_used"
                    data-test="service-is-used"
                >
                <translate>Enabled</translate>
            </label>
        </div>

        <div class="tlp-form-element">
            <label class="tlp-label tlp-checkbox">
                <input
                    type="checkbox"
                    name="is_in_iframe"
                    value="1"
                    v-bind:checked="service.is_in_iframe"
                >
                <translate>Display in iframe</translate>
            </label>
        </div>

        <div class="tlp-form-element">
            <label class="tlp-label" for="project-admin-services-custom-edit-modal-rank">
                <translate>Rank</translate>
                <i class="fa fa-asterisk"></i>
            </label>
            <input
                type="number"
                class="tlp-input"
                id="project-admin-services-custom-edit-modal-rank"
                name="rank"
                placeholder="150"
                size="5"
                maxlength="5"
                v-bind:min="minimal_rank"
                required
                v-model="service.rank"
            >
        </div>
    </div>
</template>
<script>
export default {
    name: "ProjectScopeService",
    props: {
        minimal_rank: {
            type: String,
            required: true
        },
        service: {
            type: Object,
            required: true
        }
    },
    computed: {
        label_placeholder() {
            return this.$gettext("My service");
        },
        link_title() {
            return this.$gettext("Please, enter a http:// or https:// link");
        },
        description_placeholder() {
            return this.$gettext("Awesome service to manage extra stuff");
        },
        is_active_string() {
            return this.service.is_active ? "1" : "0";
        }
    }
};
</script>
