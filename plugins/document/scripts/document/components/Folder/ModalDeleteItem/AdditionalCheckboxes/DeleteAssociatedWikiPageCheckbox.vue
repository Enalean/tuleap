<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div>
        <div class="tlp-form-element">
            <label class="tlp-label tlp-checkbox">
                <input
                    type="checkbox"
                    data-test="delete-associated-wiki-page-checkbox"
                    v-on:input="$emit('input', { delete_associated_wiki_page: $event.target.checked })"
                >
                <span v-translate>Propagate deletion to wiki service</span>
            </label>
            <p class="tlp-text-info">
                <i class="fa fa-exclamation-triangle"></i>
                <span v-translate>Please note that if you check the option above, the referenced wiki page will no longer exist in the wiki service too.</span>
            </p>
        </div>
        <div class="tlp-alert-warning">
            {{ wiki_deletion_warning }}
        </div>
    </div>
</template>

<script>
import { sprintf } from "sprintf-js";
import { TYPE_WIKI } from "../../../../constants.js";

export default {
    props: {
        item: Object,
        model: Object
    },
    computed: {
        is_item_a_wiki() {
            return this.item.type === TYPE_WIKI;
        },
        wiki_deletion_warning() {
            return sprintf(
                this.$gettext(
                    'You should also be aware that the other wiki documents referencing page "%s" will no longer be valid if you choose to propagate the deletion to the wiki service.'
                ),
                this.item.wiki_properties.page_name
            );
        }
    }
};
</script>
