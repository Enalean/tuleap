/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import { getCommonMarkPreviewErrorIntroduction } from "../gettext-catalog.js";
import { postInterpretCommonMark } from "../api/tuleap-api";

export const textfield_mixin = {
    props: {
        value: Object,
        projectId: Number,
    },
    data() {
        return {
            is_in_preview_mode: false,
            interpreted_commonmark: "",
            is_preview_loading: false,
            is_in_error: false,
            error_text: "",
        };
    },
    computed: {
        format: {
            get() {
                return this.value.format;
            },
        },
        error_introduction() {
            return getCommonMarkPreviewErrorIntroduction();
        },
    },
    methods: {
        reemit(...args) {
            this.$emit("upload-image", ...args);
        },
        async interpretCommonMark(content) {
            this.is_in_error = false;
            this.error_text = "";

            if (this.is_in_preview_mode) {
                this.is_in_preview_mode = !this.is_in_preview_mode;
                return;
            }
            try {
                this.is_preview_loading = true;
                this.interpreted_commonmark = await postInterpretCommonMark(
                    content,
                    this.projectId
                );
            } catch (error) {
                this.is_in_error = true;
                this.error_text = error;
            } finally {
                this.is_in_preview_mode = !this.is_in_preview_mode;
                this.is_preview_loading = false;
            }
        },
    },
};
