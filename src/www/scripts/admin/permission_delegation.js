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
        showAddGroup: function() {
            $.ajax({
                url: 'permission_delegation.php?action=show-add-group',
                beforeSend: tuleap.modal.showLoad

            }).done(function(data) {
                tuleap.modal.hideLoad();

                $('body').append(data);
                $('#add-group-modal').modal();
                $('#add-group-modal input:first-child').focus();
                $('#add-group-modal').on('hidden', function () {
                    $(this).remove();
                });

            }).fail(function() {
                codendi.feedback.log('error', 'An error has occured');
            });
        },

        showEditGroup: function(group_id) {
            $.ajax({
                url: 'permission_delegation.php?action=show-edit-group&group-id=' + group_id,
                beforeSend: tuleap.modal.showLoad

            }).done(function(data) {
                tuleap.modal.hideLoad();

                $('body').append(data);
                $('#add-group-modal').modal();
                $('#add-group-modal input:first-child').focus();
                $('#add-group-modal').on('hidden', function () {
                    $(this).remove();
                });

            }).fail(function() {
                codendi.feedback.log('error', 'An error has occured');
            });
        },

        showDeleteGroup: function(group_id) {
            $.ajax({
                url: 'permission_delegation.php?action=show-delete-group&group-id=' + group_id,
                beforeSend: tuleap.modal.showLoad

            }).done(function(data) {
                tuleap.modal.hideLoad();

                $('body').append(data);
                $('#delete-group-modal').modal();
                $('#delete-group-modal input[type="submit"]').focus();
                $('#delete-group-modal').on('hidden', function () {
                    $(this).remove();
                });

            }).fail(function() {
                codendi.feedback.log('error', 'An error has occured');
            });
        }
    };

    $(document).ready(function () {
        $('#add-group').click(function(e) {
            e.preventDefault();
            tuleap.admin.permissionDelegation.showAddGroup();
        });

        $('#edit-group').click(function(e) {
            e.preventDefault();
            tuleap.admin.permissionDelegation.showEditGroup($(this).attr('data-group-id'));
        });

        $('#delete-group').click(function(e) {
            e.preventDefault();
            tuleap.admin.permissionDelegation.showDeleteGroup($(this).attr('data-group-id'));
        });
    });
})(jQuery);
