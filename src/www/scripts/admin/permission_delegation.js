/**
 * Copyright (c) Enalean SAS - 2014. All rights reserved
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

var tuleap = tuleap || { };
tuleap.admin = tuleap.admin || { };

(function ($) {
    tuleap.admin.permissionDelegation = {
        showModal: function(ajax_url, modal_id, callback) {
            $.ajax({
                url: ajax_url,
                beforeSend: tuleap.modal.showLoad

            }).done(function(data) {
                tuleap.modal.hideLoad();

                $('body').append(data);
                $(modal_id).modal();
                $(modal_id + ' input:first-child').focus();
                $(modal_id).on('hidden', function () {
                    $(this).remove();
                });
                callback();

            }).fail(function() {
                codendi.feedback.log('error', 'An error has occured');
            });
        },

        handlePermissionsState: function(source_elements, target_element) {
            $(source_elements).change(function() {
                if ($(source_elements + ':checked').length > 0) {
                    $(target_element).removeAttr('disabled');
                } else {
                    $(target_element).attr('disabled', true);
                }
            });
        }
    };

    $(document).ready(function () {
        $('#add-group').click(function(e) {
            e.preventDefault();
            tuleap.admin.permissionDelegation.showModal('permission_delegation.php?action=show-add-group', '#add-group-modal');
        });
        $('#edit-group').click(function(e) {
            e.preventDefault();
            tuleap.admin.permissionDelegation.showModal('permission_delegation.php?action=show-edit-group&group-id=' + $(this).attr('data-group-id'), '#add-group-modal');
        });
        $('#delete-group').click(function(e) {
            e.preventDefault();
            tuleap.admin.permissionDelegation.showModal('permission_delegation.php?action=show-delete-group&group-id=' + $(this).attr('data-group-id'), '#delete-group-modal');
        });

        $('#add-permissions').click(function(e) {
            e.preventDefault();
            tuleap.admin.permissionDelegation.showModal('permission_delegation.php?action=show-add-permissions&group-id=' + $(this).attr('data-group-id'), '#add-permissions-modal', function() {
                tuleap.admin.permissionDelegation.handlePermissionsState('.available-permission', '#submit-permissions');
            });
        });
        tuleap.admin.permissionDelegation.handlePermissionsState('.permission', '#delete-permissions');

        tuleap.admin.permissionDelegation.handlePermissionsState('.user', '#delete-users');
    });

    var autocomplete_user = new UserAutoCompleter('add-user', codendi.imgroot, false);
})(jQuery);
