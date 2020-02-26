/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

/* global codendi:readonly */

!(function($) {
    $(document).ready(function() {
        loadAvatarReset();
        loadAvatarPreview();
        updateHeightValue();

        $(window).resize(updateHeightValue);

        initApiAccessKeyExpirationDatePicker();
    });

    function initApiAccessKeyExpirationDatePicker() {
        var field_times = document.querySelectorAll(".access-key-expiration-date-input");
        if (field_times.length !== 0) {
            [].forEach.call(field_times, function(date_picker) {
                const dateToday = new Date();
                $(date_picker).datetimepicker({
                    language: codendi.locale,
                    pickTime: false,
                    pickSeconds: false,
                    pickDate: true,
                    startDate: dateToday
                });
            });
        }
    }

    function getResizedImageUrl(url) {
        var tmp_img = document.createElement("img");
        tmp_img.src = url;

        var canvas = document.createElement("canvas"),
            max_size = 100,
            width = tmp_img.width,
            height = tmp_img.height,
            source_x = 0,
            source_y = 0,
            source_width = width,
            source_height = height;

        if (width > max_size || height > max_size) {
            var size = Math.min(width, height);
            source_x = Math.round((width - size) / 2);
            source_y = Math.round((height - size) / 2);
            source_width = size;
            source_height = size;
        }
        canvas.width = max_size;
        canvas.height = max_size;
        var ctx = canvas.getContext("2d");
        ctx.drawImage(
            tmp_img,
            source_x,
            source_y,
            source_width,
            source_height,
            0,
            0,
            max_size,
            max_size
        );

        return canvas.toDataURL("image/png");
    }

    function setAvatarPreviewUrl(url) {
        var preview = document.querySelector(".change-avatar-modal-content > .avatar > img");
        if (!preview) {
            preview = document.createElement("img");
            document.querySelector(".change-avatar-modal-content > .avatar").appendChild(preview);
        }

        preview.src = url;
    }

    function loadAvatarReset() {
        var btn = document.getElementById("use-default-avatar-btn");

        if (!btn) {
            return;
        }

        btn.addEventListener("click", function() {
            document
                .querySelector(".change-avatar-modal-content")
                .classList.remove("change-avatar-modal-content-preview");
            document.querySelector(".change-avatar-modal-content > .avatar > img").remove();

            var use_default_avatar = document.getElementById("use-default-avatar");
            use_default_avatar.form.reset();
            use_default_avatar.value = 1;
        });
    }

    function useImageInPreviewIfItIsValid(url) {
        var img = document.createElement("img");

        img.onload = function() {
            var resized_image_url = getResizedImageUrl(url);
            setAvatarPreviewUrl(resized_image_url);
            document
                .querySelector(".change-avatar-modal-content")
                .classList.add("change-avatar-modal-content-preview");
            document.getElementById("use-default-avatar").value = 0;
        };
        img.src = url;
    }

    function loadAvatarPreview() {
        var input_file = document.getElementById("change-avatar-modal-actions-select-file");

        if (!input_file) {
            return;
        }

        input_file.addEventListener("change", function() {
            var url = URL.createObjectURL(this.files[0]);
            useImageInPreviewIfItIsValid(url);
        });
    }

    function updateHeightValue() {
        $("#account-maintenance, #account-preferences").height("auto");

        var new_height = Math.max(
            $("#account-maintenance").height(),
            $("#account-preferences").height()
        );

        $("#account-preferences").height(new_height);
    }
})(window.jQuery);
