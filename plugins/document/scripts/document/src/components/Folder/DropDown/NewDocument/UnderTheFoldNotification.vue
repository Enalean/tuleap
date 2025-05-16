<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
    <div
        class="document-notification tlp-alert-success"
        v-bind:class="notification_class"
        v-if="is_displayed"
    >
        <template v-if="is_folder">{{ $gettext("The folder has been created below.") }}</template>
        <template v-else>{{ $gettext("The document has been created below.") }}</template>
        <i class="fa-solid fa-arrow-down document-new-item-under-the-fold-notification-icon"></i>
    </div>
</template>

<script>
import { isFolder } from "../../../../helpers/type-check-helper";
import emitter from "../../../../helpers/emitter";

export default {
    data() {
        return {
            is_displayed: false,
            is_folder: false,
            is_fadeout: false,
            is_fast_fadeout: false,
            fadeout_timeout_id: null,
            hidden_timeout_id: null,
        };
    },
    computed: {
        notification_class() {
            return {
                "document-notification-fadeout": this.is_fadeout,
                "document-notification-fast-fadeout": this.is_fast_fadeout,
            };
        },
    },
    created() {
        emitter.on("item-has-been-created-under-the-fold", this.show);
    },
    beforeUnmount() {
        emitter.off("item-has-been-created-under-the-fold", this.show);
    },
    methods: {
        show(event) {
            this.is_folder = isFolder(event.detail.item);

            if (this.is_displayed) {
                clearTimeout(this.fadeout_timeout_id);
                clearTimeout(this.hidden_timeout_id);
            } else {
                window.addEventListener("scroll", this.scroll, { passive: true });
            }

            this.is_displayed = true;
            this.is_fadeout = false;
            this.is_fast_fadeout = false;
            this.fadeout_timeout_id = setTimeout(() => {
                this.is_fadeout = true;
            }, 2000);
            this.hidden_timeout_id = setTimeout(() => {
                this.is_displayed = false;
            }, 3000);
        },
        scroll() {
            window.removeEventListener("scroll", this.scroll, { passive: true });
            clearTimeout(this.fadeout_timeout_id);
            clearTimeout(this.hidden_timeout_id);
            this.fadeout_timeout_id = setTimeout(() => {
                this.is_fast_fadeout = true;
            }, 0);
            this.hidden_timeout_id = setTimeout(() => {
                this.is_displayed = false;
            }, 250);
        },
    },
};
</script>
