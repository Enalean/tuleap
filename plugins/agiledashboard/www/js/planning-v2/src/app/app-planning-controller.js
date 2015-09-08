(function () {
    angular
        .module('planning')
        .controller('PlanningCtrl', PlanningCtrl);

    PlanningCtrl.$inject = [
        '$scope',
        '$filter',
        '$q',
        'gettextCatalog',
        'SharedPropertiesService',
        'BacklogItemService',
        'MilestoneService',
        'ProjectService',
        'DroppedService',
        'CardFieldsService',
        'TuleapArtifactModalService',
        'NewTuleapArtifactModalService',
        'TuleapArtifactModalLoading',
        'UserPreferencesService'
    ];

    function PlanningCtrl(
        $scope,
        $filter,
        $q,
        gettextCatalog,
        SharedPropertiesService,
        BacklogItemService,
        MilestoneService,
        ProjectService,
        DroppedService,
        CardFieldsService,
        TuleapArtifactModalService,
        NewTuleapArtifactModalService,
        TuleapArtifactModalLoading,
        UserPreferencesService
    ) {
        var user_id                     = SharedPropertiesService.getUserId(),
            project_id                  = SharedPropertiesService.getProjectId(),
            milestone_id                = SharedPropertiesService.getMilestoneId(),
            use_angular_new_modal       = SharedPropertiesService.getUseAngularNewModal(),
            pagination_limit            = 50,
            pagination_offset           = 0,
            backlog_pagination_offset   = 0,
            show_closed_milestone_items = true;

        var self = this;

        self.canBeAddedToBacklogItemChildren = canBeAddedToBacklogItemChildren;

        _.extend($scope, {
            backlog: {
                user_can_move_cards: false
            },
            backlog_items: {
                content         : [],
                filtered_content: [],
                fully_loaded    : false,
                loading         : false
            },
            compact_view_key     : 'compact-view',
            current_milestone    : {},
            detailed_view_key    : 'detailed-view',
            filter_terms         : '',
            items                : {},
            loading_milestones   : true,
            loading_modal        : TuleapArtifactModalLoading.loading,
            milestones           : [],
            rest_error           : '',
            rest_error_occured   : false,
            submilestone_type    : null,
            use_angular_new_modal: use_angular_new_modal,
            appendBacklogItems                    : appendBacklogItems,
            canShowBacklogItem                    : canShowBacklogItem,
            cardFieldIsCross                      : CardFieldsService.cardFieldIsCross,
            cardFieldIsDate                       : CardFieldsService.cardFieldIsDate,
            cardFieldIsFile                       : CardFieldsService.cardFieldIsFile,
            cardFieldIsList                       : CardFieldsService.cardFieldIsList,
            cardFieldIsPermissions                : CardFieldsService.cardFieldIsPermissions,
            cardFieldIsSimpleValue                : CardFieldsService.cardFieldIsSimpleValue,
            cardFieldIsText                       : CardFieldsService.cardFieldIsText,
            cardFieldIsUser                       : CardFieldsService.cardFieldIsUser,
            displayBacklogItems                   : displayBacklogItems,
            displayUserCantPrioritizeForBacklog   : displayUserCantPrioritizeForBacklog,
            displayUserCantPrioritizeForMilestones: displayUserCantPrioritizeForMilestones,
            fetchAllBacklogItems                  : fetchAllBacklogItems,
            fetchBacklogItemChildren              : fetchBacklogItemChildren,
            fetchBacklogItems                     : fetchBacklogItems,
            filterBacklog                         : filterBacklog,
            generateMilestoneLinkUrl              : generateMilestoneLinkUrl,
            getCardFieldCrossValue                : CardFieldsService.getCardFieldCrossValue,
            getCardFieldFileValue                 : CardFieldsService.getCardFieldFileValue,
            getCardFieldListValues                : CardFieldsService.getCardFieldListValues,
            getCardFieldPermissionsValue          : CardFieldsService.getCardFieldPermissionsValue,
            getCardFieldTextValue                 : CardFieldsService.getCardFieldTextValue,
            getCardFieldUserValue                 : CardFieldsService.getCardFieldUserValue,
            getInitialEffortMessage               : getInitialEffortMessage,
            refreshBacklogItem                    : refreshBacklogItem,
            showAddChildModal                     : showAddChildModal,
            showAddItemToSubMilestoneModal        : showAddItemToSubMilestoneModal,
            showAddSubmilestoneModal              : showAddSubmilestoneModal,
            showChildren                          : showChildren,
            showCreateNewModal                    : showCreateNewModal,
            showEditModal                         : showEditModal,
            switchViewMode                        : switchViewMode,
            toggle                                : toggle,
            toggleClosedMilestoneItems            : toggleClosedMilestoneItems
        });

        $scope.treeOptions = {
            accept : isItemDroppable,
            dropped: dropped
        };

        initViewMode();
        loadBacklog();
        displayBacklogItems();
        displayMilestones();

        function initViewMode() {
            $scope.current_view_class = $scope.compact_view_key;

            if (SharedPropertiesService.getViewMode()) {
                $scope.current_view_class = SharedPropertiesService.getViewMode();
            }
        }

        function switchViewMode(view_mode) {
            $scope.current_view_class = view_mode;
            UserPreferencesService.setPreference(user_id, 'agiledashboard_planning_item_view_mode_' + project_id, view_mode);
        }

        function isMilestoneContext() {
            return angular.isDefined(SharedPropertiesService.getMilestoneId());
        }

        function loadBacklog() {
            if (! angular.isDefined(milestone_id)) {
                $scope.backlog = {
                    rest_base_route : 'projects',
                    rest_route_id   : project_id,
                    accepted_types  : {
                        toString: function() {
                            return '';
                        }
                    }
                };

                fetchProjectBacklogAcceptedTypes(project_id);
                fetchProjectSubmilestoneType(project_id);

            } else {
                MilestoneService.getMilestone(milestone_id, pagination_limit, pagination_offset, $scope.items).then(function(milestone) {
                    $scope.backlog = {
                        rest_base_route     : 'milestones',
                        rest_route_id       : milestone_id,
                        accepted_types      : milestone.results.backlog_accepted_types,
                        user_can_move_cards : milestone.results.has_user_priority_change_permission
                    };
                    $scope.current_milestone = milestone.results;
                    $scope.submilestone_type = milestone.results.sub_milestone_type;
                });
            }
        }

        function fetchProjectSubmilestoneType(project_id) {
            return ProjectService.getProject(project_id).then(function(response) {
                $scope.submilestone_type = response.data.additional_informations.agiledashboard.root_planning.milestone_tracker;
            });
        }

        function fetchProjectBacklogAcceptedTypes(project_id) {
            return ProjectService.getProjectBacklog(project_id).then(function(data) {
                $scope.backlog.accepted_types      = data.allowed_backlog_item_types;
                $scope.backlog.user_can_move_cards = data.has_user_priority_change_permission;
            });
        }

        function backlogItemsAreLoadingOrAllLoaded() {
            return ($scope.backlog_items.loading || $scope.backlog_items.fully_loaded);
        }

        function displayBacklogItems() {
            if (backlogItemsAreLoadingOrAllLoaded()) {
                return $q.when();
            }

            return $scope.fetchBacklogItems(pagination_limit, backlog_pagination_offset).then(function(total) {
                backlog_pagination_offset         += pagination_limit;
                $scope.backlog_items.fully_loaded = backlog_pagination_offset >= total;
            });
        }

        function fetchBacklogItems(limit, offset) {
            $scope.backlog_items.loading = true;
            var promise;

            if (isMilestoneContext()) {
                var milestone_id = SharedPropertiesService.getMilestoneId();
                promise = BacklogItemService.getMilestoneBacklogItems(milestone_id, limit, offset);
            } else {
                var project_id = SharedPropertiesService.getProjectId();
                promise = BacklogItemService.getProjectBacklogItems(project_id, limit, offset);
            }

            return promise.then(function(data) {
                var items = data.results;
                $scope.appendBacklogItems(items);

                return data.total;
            });
        }

        function appendBacklogItems(items) {
            _.extend($scope.items, _.indexBy(items, 'id'));
            var backlog_items     = $scope.backlog_items;
            backlog_items.content = backlog_items.content.concat(items);
            backlog_items.loading = false;
            applyFilter();
        }

        function fetchAllBacklogItems(limit, offset) {
            if (backlogItemsAreLoadingOrAllLoaded()) {
                return $q.reject();
            }

            return $scope.fetchBacklogItems(limit, offset).then(function(total) {
                if ((offset + limit) > total) {
                    $scope.backlog_items.fully_loaded = true;
                    return;
                } else {
                    return fetchAllBacklogItems(limit, offset + limit);
                }
            });
        }

        function filterBacklog() {
            $scope.fetchAllBacklogItems(pagination_limit, backlog_pagination_offset)
                ['finally'](function() {
                    applyFilter();
                });
        }

        function applyFilter() {
            $scope.backlog_items.filtered_content = $filter('InPropertiesFilter')($scope.backlog_items.content, $scope.filter_terms);
        }

        function displayMilestones() {
            if (! angular.isDefined(milestone_id)) {
                fetchMilestones(project_id, pagination_limit, pagination_offset);
            } else {
                fetchSubMilestones(milestone_id, pagination_limit, pagination_offset);
            }
        }

        function fetchMilestones(project_id, limit, offset) {
            return MilestoneService.getMilestones(project_id, limit, offset, $scope.items).then(function(data) {
                $scope.milestones = $scope.milestones.concat(data.results);

                if ((offset + limit) < data.total) {
                    fetchMilestones(project_id, limit, offset + limit);
                } else {
                    $scope.loading_milestones = false;
                }
            });
        }

        function fetchSubMilestones(milestone_id, limit, offset) {
            return MilestoneService.getSubMilestones(milestone_id, limit, offset, $scope.items).then(function(data) {
                $scope.milestones = $scope.milestones.concat(data.results);

                if ((offset + limit) < data.total) {
                    fetchSubMilestones(milestone_id, limit, offset + limit);
                } else {
                    $scope.loading_milestones = false;
                }
            });
        }

        function generateMilestoneLinkUrl(milestone, pane) {
            return '?group_id=' + project_id + '&planning_id=' + milestone.planning.id + '&action=show&aid=' + milestone.id + '&pane=' + pane;
        }

        function showCreateNewModal($event, item_type, backlog) {
            $event.preventDefault();

            var compared_to;
            if (! _.isEmpty($scope.backlog_items.content)) {
                compared_to = {
                    direction : "before",
                    item_id   : $scope.backlog_items.content[0].id
                };
            }

            var callback = function(item_id) {
                var promise;
                if (backlog.rest_base_route == 'projects') {
                    if (compared_to) {
                        promise = ProjectService.removeAddReorderToBacklog(undefined, backlog.rest_route_id, item_id, compared_to);
                    } else {
                        promise = ProjectService.removeAddToBacklog(undefined, backlog.rest_route_id, item_id);
                    }
                } else {
                    if (compared_to) {
                        promise = MilestoneService.removeAddReorderToBacklog(undefined, backlog.rest_route_id, item_id, compared_to);
                    } else {
                        promise = MilestoneService.removeAddToBacklog(undefined, backlog.rest_route_id, item_id);
                    }
                }

                promise.then(function() {
                    var subpromise;
                    if ($scope.filter_terms) {
                        subpromise = prependItemToFilteredBacklog(item_id);
                    } else {
                        subpromise = prependItemToBacklog(item_id);
                    }
                    return subpromise;
                });

                return promise;
            };

            var parent_item = (! _.isEmpty($scope.current_milestone)) ? $scope.current_milestone : undefined;
            if (SharedPropertiesService.getUseAngularNewModal()) {
                NewTuleapArtifactModalService.showCreation(item_type.id, parent_item, callback);
            } else {
                TuleapArtifactModalService.showCreateItemForm(item_type.id, backlog.rest_route_id, callback);
            }
        }

        function showAddChildModal($event, item_type, parent_item) {
            $event.preventDefault();

            var callback = function(item_id) {
                var promise = BacklogItemService.removeAddBacklogItemChildren(undefined, parent_item.id, item_id);

                promise.then(function() {
                    return appendItemToBacklogItem(item_id, parent_item);
                });

                return promise;
            };

            NewTuleapArtifactModalService.showCreation(item_type.id, parent_item, callback);
        }

        function showAddSubmilestoneModal($event, submilestone_type) {
            $event.preventDefault();

            var callback = function(submilestone_id) {
                if ($scope.backlog.rest_base_route == 'projects') {
                    return prependSubmilestoneToSubmilestoneList(submilestone_id);

                } else {
                    var submilestone_ids = [];
                    _.forEach($scope.milestones, function(milestone) {
                        submilestone_ids.push(milestone.id);
                    });

                    submilestone_ids.push(submilestone_id);

                    var promise = MilestoneService.putSubMilestones($scope.backlog.rest_route_id, submilestone_ids);
                    promise.then(function() {
                        return prependSubmilestoneToSubmilestoneList(submilestone_id);
                    });

                    return promise;
                }
            };

            var parent_item = (! _.isEmpty($scope.current_milestone)) ? $scope.current_milestone : undefined;
            NewTuleapArtifactModalService.showCreation(submilestone_type.id, parent_item, callback);
        }

        function prependSubmilestoneToSubmilestoneList(submilestone_id) {
            return MilestoneService.getMilestone(submilestone_id, pagination_limit, pagination_offset, $scope.items).then(function(data) {
                $scope.milestones.unshift(data.results);
            });
        }

        function showAddItemToSubMilestoneModal(item_type, parent_item) {
            var compared_to;
            if (!_.isEmpty(parent_item.content)) {
                compared_to = {
                    direction: "before",
                    item_id  : parent_item.content[0].id
                };
            }

            var callback = function(item_id) {
                var promise;
                if (compared_to) {
                    promise = MilestoneService.addReorderToContent(parent_item.id, item_id, compared_to);
                } else {
                    promise = MilestoneService.addToContent(parent_item.id, item_id);
                }

                promise.then(function() {
                    return prependItemToSubmilestone(item_id, parent_item);
                });

                return promise;
            };

            NewTuleapArtifactModalService.showCreation(item_type.id, parent_item, callback);
        }

        function showEditModal($event, backlog_item) {
            var when_left_mouse_click = 1;

            if($event.which === when_left_mouse_click) {
                $event.preventDefault();

                NewTuleapArtifactModalService.showEdition(
                    backlog_item.artifact.tracker.id,
                    backlog_item.artifact.id,
                    $scope.refreshBacklogItem
                );
            }
        }

        function appendItemToBacklogItem(child_item_id, parent_item) {
            return BacklogItemService.getBacklogItem(child_item_id).then(function(data) {
                $scope.items[child_item_id] = data.backlog_item;
                refreshBacklogItem(parent_item.id);

                if (canBeAddedToBacklogItemChildren(child_item_id, parent_item)) {
                    parent_item.children.data.push($scope.items[child_item_id]);
                }
            });
        }

        function canBeAddedToBacklogItemChildren(child_item_id, parent_item) {
            if (! parent_item.has_children) {
                return true;
            }

            if (parent_item.has_children && parent_item.children.loaded) {
                var child_already_in_children = _.find(parent_item.children.data, { id: child_item_id });

                if (child_already_in_children === undefined) {
                    return true;
                }
            }

            return false;
        }

        function prependItemToSubmilestone(child_item_id, parent_item) {
            return BacklogItemService.getBacklogItem(child_item_id).then(function(data) {
                $scope.items[child_item_id] = data.backlog_item;
                parent_item.content.unshift(data.backlog_item);
            });
        }

        function prependItemToBacklog(backlog_item_id) {
            return BacklogItemService.getBacklogItem(backlog_item_id).then(function(data) {
                var new_item = data.backlog_item;
                $scope.items[backlog_item_id] = new_item;
                $scope.backlog_items.content.unshift(new_item);
                $scope.backlog_items.filtered_content.unshift(new_item);
            });
        }

        function prependItemToFilteredBacklog(backlog_item_id) {
            return BacklogItemService.getBacklogItem(backlog_item_id).then(function(data) {
                var new_item = data.backlog_item;
                $scope.items[backlog_item_id] = new_item;
                $scope.backlog_items.content.unshift(new_item);
            });
        }

        function refreshBacklogItem(backlog_item_id) {
            $scope.items[backlog_item_id].updating = true;

            return BacklogItemService.getBacklogItem(backlog_item_id).then(function(data) {
                $scope.items[backlog_item_id].label          = data.backlog_item.label;
                $scope.items[backlog_item_id].initial_effort = data.backlog_item.initial_effort;
                $scope.items[backlog_item_id].card_fields    = data.backlog_item.card_fields;
                $scope.items[backlog_item_id].updating       = false;
            });
        }

        function toggle($event, milestone) {
            if (! milestone.alreadyLoaded && milestone.content.length === 0) {
                milestone.getContent();
            }

            var target                = $event.target;
            var is_a_create_item_link = false;

            if (target.classList) {
                is_a_create_item_link = target.classList.contains('create-item-link');
            } else {
                is_a_create_item_link = target.parentNode.getElementsByClassName("create-item-link")[0] !== undefined;
            }

            if (! is_a_create_item_link) {
                return milestone.collapsed = ! milestone.collapsed;
            }
        }

        function showChildren(scope, backlog_item) {
            scope.toggle();

            if (backlog_item.has_children && ! backlog_item.children.loaded) {
                backlog_item.loading = true;
                fetchBacklogItemChildren(backlog_item, pagination_limit, pagination_offset);
            }
        }

        function fetchBacklogItemChildren(backlog_item, limit, offset) {
            return BacklogItemService.getBacklogItemChildren(backlog_item.id, limit, offset).then(function(data) {
                angular.forEach(data.results, function(child) {
                    $scope.items[child.id] = child;
                    backlog_item.children.data.push(child);
                });

                if ((offset + limit) < data.total) {
                    fetchBacklogItemChildren(backlog_item, limit, offset + limit);
                } else {
                    backlog_item.loading         = false;
                    backlog_item.children.loaded = true;
                }
            });
        }

        function toggleClosedMilestoneItems() {
            show_closed_milestone_items = (show_closed_milestone_items === true) ? false : true;
        }

        function canShowBacklogItem(backlog_item) {
            if (typeof backlog_item.isOpen === 'function') {
                return backlog_item.isOpen() || show_closed_milestone_items;
            }

            return true;
        }

        function isItemDroppable(sourceNodeScope, destNodesScope, destIndex) {
            if (typeof destNodesScope.$element.attr === 'undefined' || destNodesScope.$element.attr('data-nodrag') === 'true') {
                return;
            }

            var accepted     = destNodesScope.$element.attr('data-accept').split('|');
            var type         = sourceNodeScope.$element.attr('data-type');
            var is_droppable = false;

            for (var i = 0; i < accepted.length; i++) {
                if (accepted[i] === type) {
                    is_droppable = true;
                    continue;
                }
            }

            return is_droppable;
        }

        function hideUserCantPrioritizeForBacklog() {
            return $scope.backlog.user_can_move_cards || $scope.backlog_items.content.length === 0;
        }

        function displayUserCantPrioritizeForBacklog() {
            return ! hideUserCantPrioritizeForBacklog();
        }

        function hideUserCantPrioritizeForMilestones() {
            if ($scope.milestones.length === 0) {
                return true;
            }

            return $scope.milestones[0].has_user_priority_change_permission;
        }

        function displayUserCantPrioritizeForMilestones() {
            return ! hideUserCantPrioritizeForMilestones();
        }

        function getInitialEffortMessage(initial_effort) {
            return gettextCatalog.getPlural(initial_effort, "pt", "pts");
        }

        function dropped(event) {
            var source_list_element = event.source.nodesScope.$element,
                dest_list_element   = event.dest.nodesScope.$element,
                dropped_item_id     = event.source.nodeScope.$modelValue.id,
                compared_to         = DroppedService.defineComparedTo(event.dest.nodesScope.$modelValue, event.dest.index);

            saveChange();
            updateSubmilestonesInitialEffort();
            collapseSourceParentIfNeeded();
            removeFromDestinationIfNeeded();

            function saveChange() {
                switch(true) {
                    case movedInTheSameList():
                        if (source_list_element.hasClass('backlog')) {
                            DroppedService
                                .reorderBacklog(dropped_item_id, compared_to, $scope.backlog)
                                .then(function() {}, catchError);

                        } else if (source_list_element.hasClass('submilestone')) {
                            DroppedService
                                .reorderSubmilestone(dropped_item_id, compared_to, parseInt(dest_list_element.attr('data-submilestone-id'), 10))
                                .then(function() {}, catchError);

                        } else if (source_list_element.hasClass('backlog-item-children')) {
                            DroppedService
                                .reorderBacklogItemChildren(dropped_item_id, compared_to, parseInt(dest_list_element.attr('data-backlog-item-id'), 10))
                                .then(function() {}, catchError);
                        }
                        break;

                    case movedFromBacklogToSubmilestone():
                        DroppedService
                            .moveFromBacklogToSubmilestone(dropped_item_id, compared_to, parseInt(dest_list_element.attr('data-submilestone-id'), 10))
                            .then(function() {
                                _.remove($scope.backlog_items.content, function(item) {
                                    return item.id === dropped_item_id;
                                });
                            }, catchError);
                        break;

                    case movedFromChildrenToChildren():
                        var backlog_item_id_source = parseInt(source_list_element.attr('data-backlog-item-id'), 10),
                            backlog_item_id_dest   = parseInt(dest_list_element.attr('data-backlog-item-id'), 10);

                        $scope.items[backlog_item_id_source].updating = true;
                        $scope.items[backlog_item_id_dest].updating   = true;

                        DroppedService
                            .moveFromChildrenToChildren(
                                dropped_item_id,
                                compared_to,
                                backlog_item_id_source,
                                backlog_item_id_dest
                            )
                            .then(function() {
                                refreshBacklogItem(backlog_item_id_source);
                                refreshBacklogItem(backlog_item_id_dest);

                            }, catchError);
                        break;

                    case movedFromSubmilestoneToBacklog():
                        DroppedService
                            .moveFromSubmilestoneToBacklog(
                                dropped_item_id,
                                compared_to,
                                parseInt(source_list_element.attr('data-submilestone-id'), 10),
                                $scope.backlog
                            )
                            .then(function() {}, catchError);
                        break;

                    case movedFromOneSubmilestoneToAnother():
                        DroppedService
                            .moveFromSubmilestoneToSubmilestone(
                                dropped_item_id,
                                compared_to,
                                parseInt(source_list_element.attr('data-submilestone-id'), 10),
                                parseInt(dest_list_element.attr('data-submilestone-id'), 10)
                            )
                            .then(function() {}, catchError);
                        break;
                }

                function catchError(data) {
                    $scope.rest_error_occured = true;
                    $scope.rest_error         = data.data.error.code + ' ' + data.data.error.message;
                }

                function movedInTheSameList() {
                    return event.source.nodesScope.$id === event.dest.nodesScope.$id;
                }

                function movedFromBacklogToSubmilestone() {
                    return source_list_element.hasClass('backlog') && dest_list_element.hasClass('submilestone');
                }

                function movedFromChildrenToChildren() {
                    return source_list_element.hasClass('backlog-item-children') && dest_list_element.hasClass('backlog-item-children');
                }

                function movedFromSubmilestoneToBacklog() {
                    return source_list_element.hasClass('submilestone') && dest_list_element.hasClass('backlog');
                }

                function movedFromOneSubmilestoneToAnother() {
                    return source_list_element.hasClass('submilestone') && dest_list_element.hasClass('submilestone');
                }
            }

            function updateSubmilestonesInitialEffort() {
                if (source_list_element.hasClass('submilestone')) {
                    MilestoneService.updateInitialEffort(_.find($scope.milestones, function(milestone) {
                        return milestone.id == source_list_element.attr('data-submilestone-id');
                    }));
                }

                if (dest_list_element.hasClass('submilestone')) {
                    MilestoneService.updateInitialEffort(_.find($scope.milestones, function(milestone) {
                        return milestone.id == dest_list_element.attr('data-submilestone-id');
                    }));
                }
            }

            function collapseSourceParentIfNeeded() {
                if (event.sourceParent && ! event.sourceParent.hasChild()) {
                    event.sourceParent.collapse();
                }
            }

            function removeFromDestinationIfNeeded() {
                if (event.dest.nodesScope.collapsed &&
                    event.dest.nodesScope.$nodeScope.$modelValue.has_children &&
                    ! event.dest.nodesScope.$nodeScope.$modelValue.children.loaded) {

                    event.dest.nodesScope.childNodes()[0].remove();
                }
            }
        }
    }
})();
