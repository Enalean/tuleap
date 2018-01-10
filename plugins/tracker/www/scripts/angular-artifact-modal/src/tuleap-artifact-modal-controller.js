import _                    from 'lodash';
import { isInCreationMode } from './modal-creation-mode-state.js';

export default ArtifactModalController;

ArtifactModalController.$inject = [
    '$q',
    '$scope',
    '$timeout',
    '$window',
    'modal_instance',
    'modal_model',
    'displayItemCallback',
    'TuleapArtifactModalRestService',
    'TuleapArtifactModalValidateService',
    'TuleapArtifactModalLoading',
    'TuleapArtifactModalFieldDependenciesService',
    'TuleapArtifactModalFileUploadService'
];

function ArtifactModalController(
    $q,
    $scope,
    $timeout,
    $window,
    modal_instance,
    modal_model,
    displayItemCallback,
    TuleapArtifactModalRestService,
    TuleapArtifactModalValidateService,
    TuleapArtifactModalLoading,
    TuleapArtifactModalFieldDependenciesService,
    TuleapArtifactModalFileUploadService
) {
    var self    = this,
        user_id = modal_model.user_id;
    _.extend(self, {
        artifact_id        : modal_model.artifact_id,
        color              : formatColor(modal_model.color),
        creation_mode      : isInCreationMode(),
        is_disk_usage_empty: true,
        ordered_fields     : modal_model.ordered_fields,
        parent             : modal_model.parent,
        title              : (modal_model.title.content !== undefined) ? modal_model.title.content : modal_model.title,
        tracker            : modal_model.tracker,
        values             : modal_model.values,
        text_formats       : modal_model.text_formats,
        followups_comments : {
            content         : [],
            loading_comments: true,
            invert_order    : (modal_model.invert_followups_comments_order) ? 'asc' : 'desc'
        },
        formatColor                   : formatColor,
        getDropdownAttribute          : getDropdownAttribute,
        getError                      : function() { return TuleapArtifactModalRestService.error; },
        isDisabled                    : isDisabled,
        isFollowupCommentFormDisplayed: isFollowupCommentFormDisplayed,
        isLoading                     : function() { return TuleapArtifactModalRestService.is_loading; },
        isThereAtLeastOneFileField    : isThereAtLeastOneFileField,
        setupTooltips                 : setupTooltips,
        submit                        : submit,
        toggleFieldset                : toggleFieldset,
        initCkeditorConfig            : initCkeditorConfig,
        followup_comment              : {
            body  : '',
            format: modal_model.text_fields_format
        },
        ckeditor_options              : {
            default_ckeditor: {
                toolbar: [
                    ['Bold', 'Italic', 'Underline'],
                    [
                        'NumberedList',
                        'BulletedList',
                        '-',
                        'Blockquote',
                        'Format'
                    ],
                    ['Link', 'Unlink', 'Anchor', 'Image'],
                    ['Source']
                ],
                height: '100px'
            }
        }
    });

    init();

    function init() {
        setFieldDependenciesWatchers();

        TuleapArtifactModalLoading.loading = false;
        self.setupTooltips();

        if (! isInCreationMode()) {
            fetchFollowupsComments(
                self.artifact_id,
                50,
                0,
                self.followups_comments.invert_order
            );
        }
    }

    function initCkeditorConfig(field) {
        var id = 'default_ckeditor';
        if (field) {
            id = field.field_id;
            if (! self.ckeditor_options[id]) {
                self.ckeditor_options[id] = {
                    toolbar: [
                        ['Bold', 'Italic', 'Underline'],
                        [
                            'NumberedList',
                            'BulletedList',
                            '-',
                            'Blockquote',
                            'Format'
                        ],
                        ['Link', 'Unlink', 'Anchor', 'Image'],
                        ['Source']
                    ],
                    height: '100px',
                    readOnly: self.isDisabled(field)
                };
            }
        }

        return self.ckeditor_options[id];
    }

    function setupTooltips() {
        $timeout(function() {
            $window.codendi.Tooltip.load($window.document.body);
        }, 0);
    }

    function isFollowupCommentFormDisplayed() {
        return ! isInCreationMode() && user_id !== 0;
    }

    function fetchFollowupsComments(artifact_id, limit, offset, order) {
        return TuleapArtifactModalRestService.getFollowupsComments(artifact_id, limit, offset, order).then(function(data) {
            self.followups_comments.content = self.followups_comments.content.concat(data.results);

            if (offset + limit < data.total) {
                fetchFollowupsComments(artifact_id, limit, offset + limit, order);
            } else {
                self.followups_comments.loading_comments = false;
                self.setupTooltips();
            }
        });
    }

    function submit() {
        TuleapArtifactModalLoading.loading = true;

        uploadAllFileFields().then(function() {
            var validated_values = TuleapArtifactModalValidateService.validateArtifactFieldsValues(self.values, isInCreationMode());

            var promise;
            if (isInCreationMode()) {
                promise = TuleapArtifactModalRestService.createArtifact(modal_model.tracker_id, validated_values);
            } else {
                promise = TuleapArtifactModalRestService.editArtifact(modal_model.artifact_id, validated_values, self.followup_comment);
            }

            return promise;
        }).then(function(new_artifact) {
            modal_instance.tlp_modal.hide();

            return displayItemCallback(new_artifact.id);
        }).catch(function() {
            // Do nothing
            // This catch is required since angular 1.6, but the error is already handled and displayed by the REST service
        }).finally(function() {
            TuleapArtifactModalLoading.loading = false;
        });
    }

    function getAllFileFields() {
        return _.filter(self.values, function(field_value) {
            return field_value.type === "file";
        });
    }

    function isThereAtLeastOneFileField() {
        return (getAllFileFields().length > 0);
    }

    function uploadAllFileFields() {
        var promises = _.map(getAllFileFields(), function(file_field_value) {
            return uploadFileField(file_field_value);
        });

        return $q.all(promises);
    }

    function uploadFileField(file_field_value) {
        var promise = TuleapArtifactModalFileUploadService.uploadAllTemporaryFiles(file_field_value.temporary_files)
            .then(function(temporary_files_ids) {
                var uploaded_files_ids = _.compact(temporary_files_ids);

                file_field_value.value = file_field_value.value.concat(uploaded_files_ids);
            });

        return promise;
    }

    function isDisabled(field) {
        var necessary_permission = (isInCreationMode()) ? 'create' : 'update';
        return ! _(field.permissions).contains(necessary_permission);
    }

    function getDropdownAttribute(field) {
        return (self.isDisabled(field)) ? '' : 'dropdown';
    }

    function toggleFieldset(fieldset) {
        fieldset.collapsed = ! fieldset.collapsed;
    }

    function formatColor(color) {
        var color_formatted = '';
        var color_split     = color.split('_');
        color_split.forEach(function (color, index) {
            if (index === 0) {
                color_formatted = color_formatted.concat(color);
            } else {
                color_formatted = color_formatted.concat('-', color);
            }
        });
        return color_formatted;
    }

    function setFieldDependenciesWatchers() {
        TuleapArtifactModalFieldDependenciesService.setUpFieldDependenciesActions(self.tracker, setFieldDependenciesWatcher);
    }

    function setFieldDependenciesWatcher(source_field_id, target_field, field_dependencies_rules) {
        $scope.$watch(function() {
            return self.values[source_field_id].bind_value_ids;
        }, function(new_value, old_value) {
            if (new_value === old_value) {
                return;
            }

            var source_value_ids = [].concat(new_value);

            changeTargetFieldPossibleValuesAndResetSelectedValue(
                source_field_id,
                source_value_ids,
                target_field,
                field_dependencies_rules
            );
        }, true);
    }

    function changeTargetFieldPossibleValuesAndResetSelectedValue(
        source_field_id,
        source_value_ids,
        target_field,
        field_dependencies_rules
    ) {
        target_field.filtered_values = TuleapArtifactModalFieldDependenciesService.getTargetFieldPossibleValues(
            source_value_ids,
            target_field,
            field_dependencies_rules
        );

        var target_field_selected_value = modal_model.values[target_field.field_id].bind_value_ids;
        emptyArray(target_field_selected_value);

        if (target_field.filtered_values.length === 1) {
            target_field_selected_value.push(target_field.filtered_values[0].id);
        }
    }

    function emptyArray(array) {
        array.length = 0;
    }
}
