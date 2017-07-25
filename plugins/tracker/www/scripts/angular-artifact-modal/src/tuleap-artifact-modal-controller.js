import _ from 'lodash';

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
    'TuleapArtifactModalParentService',
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
    TuleapArtifactModalParentService,
    TuleapArtifactModalFieldDependenciesService,
    TuleapArtifactModalFileUploadService
) {
    var self    = this,
        user_id = modal_model.user_id;
    _.extend(self, {
        artifact_id        : modal_model.artifact_id,
        color              : formatColor(modal_model.color),
        creation_mode      : modal_model.creation_mode,
        is_disk_usage_empty: true,
        ordered_fields     : modal_model.ordered_fields,
        parent             : modal_model.parent,
        parent_artifacts   : modal_model.parent_artifacts,
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
        formatParentArtifactTitle     : formatParentArtifactTitle,
        getDropdownAttribute          : getDropdownAttribute,
        getError                      : function() { return TuleapArtifactModalRestService.error; },
        isDisabled                    : isDisabled,
        isFollowupCommentFormDisplayed: isFollowupCommentFormDisplayed,
        isLoading                     : function() { return TuleapArtifactModalRestService.is_loading; },
        isThereAtLeastOneFileField    : isThereAtLeastOneFileField,
        newOpenListStaticValue        : newOpenListStaticValue,
        newOpenListUserBindValue      : newOpenListUserBindValue,
        searchUsers                   : searchUsers,
        setupTooltips                 : setupTooltips,
        showParentArtifactChoice      : showParentArtifactChoice,
        submit                        : submit,
        toggleFieldset                : toggleFieldset,
        followup_comment              : {
            body  : '',
            format: modal_model.text_fields_format
        },
        ckeditor_options: {
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
    });

    init();

    function init() {
        setFieldDependenciesWatchers();

        TuleapArtifactModalLoading.loading = false;
        self.setupTooltips();

        if (! self.creation_mode) {
            fetchFollowupsComments(
                self.artifact_id,
                50,
                0,
                self.followups_comments.invert_order
            );
        }
    }

    function setupTooltips() {
        $timeout(function() {
            $window.codendi.Tooltip.load($window.document.body);
        }, 0);
    }

    function isFollowupCommentFormDisplayed() {
        return ! self.creation_mode && user_id !== 0;
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
            var validated_values = TuleapArtifactModalValidateService.validateArtifactFieldsValues(self.values, modal_model.creation_mode);

            var promise;
            if (modal_model.creation_mode) {
                promise = TuleapArtifactModalRestService.createArtifact(modal_model.tracker_id, validated_values);
            } else {
                promise = TuleapArtifactModalRestService.editArtifact(modal_model.artifact_id, validated_values, self.followup_comment);
            }

            return promise;
        }).then(function(new_artifact) {
            modal_instance.tlp_modal.hide();

            return displayItemCallback(new_artifact.id);
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
        var necessary_permission = (self.creation_mode) ? 'create' : 'update';
        return ! _(field.permissions).contains(necessary_permission);
    }

    function getDropdownAttribute(field) {
        return (self.isDisabled(field)) ? '' : 'dropdown';
    }

    function toggleFieldset(fieldset) {
        fieldset.collapsed = ! fieldset.collapsed;
    }

    function newOpenListStaticValue(newOpenValue) {
        var item = {
            label: newOpenValue
        };

        return item;
    }

    function newOpenListUserBindValue(email) {
        var item = {
            display_name: email,
            email       : email
        };

        return item;
    }

    function searchUsers(field, query) {
        var minimal_query_length = 3;

        if (! query || minimal_query_length > query.length) {
            field.values = [];
            return;
        }

        field.loading = true;

        TuleapArtifactModalRestService.searchUsers(query).then(function(data) {
            field.loading = false;
            field.values  = [].concat(data);
        });
    }

    function showParentArtifactChoice() {
        var canChoose = TuleapArtifactModalParentService.canChooseArtifactsParent(
            self.tracker.parent,
            self.parent
        );

        return (
            canChoose &&
            Boolean(self.parent_artifacts) &&
            self.parent_artifacts.length > 0
        );
    }

    function formatParentArtifactTitle(artifact) {
        var tracker_label   = getTrackerLabel(artifact);
        var formatted_title = tracker_label + ' #' + artifact.id + ' - ' + artifact.title;

        return formatted_title;
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

    function getTrackerLabel(artifact) {
        if (_.has(artifact, 'tracker') && _.has(artifact.tracker, 'label')) {
            return artifact.tracker.label;
        }

        return '';
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
