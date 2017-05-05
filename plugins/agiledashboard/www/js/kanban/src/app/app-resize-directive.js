export default ResizeDirective;

ResizeDirective.$inject = ['$window', '$timeout'];

function ResizeDirective($window, $timeout) {
    return function (scope, board_element, attr) {
        scope.$watch(watchExpressions, listener, true);

        bindWindowResizeEvent();
        bindSidebarEvent();

        function watchExpressions() {
            return {
                height            : $window.document.body.clientHeight,
                width             : $window.document.body.clientWidth,
                nb_displayed      : getNbDisplayed(),
                nb_hidden         : getNbHidden(),
                nb_items_displayed: getNbItemsDisplayed()
            };
        }

        function bindWindowResizeEvent() {
            angular.element($window).bind('resize', function () {
                scope.$apply();
            });
        }

        function bindSidebarEvent() {
            angular.element($window.document).on('sidebarSizeUpdated', '.sidebar-nav', function(event, new_width) {
                listener(watchExpressions());
            });
        }

        function getSidebarWidth() {
            var sidebar_width = 0;

            angular.element($window.document).find('.sidebar-nav').each(function(index, element) {
                sidebar_width = angular.element(element).width();
            });

            return sidebar_width;
        }

        function getNbDisplayed() {
            var nb_displayed = scope.kanban.board.columns.reduce(
                function (sum, column) {
                    if (column.is_open) {
                        sum++;
                    }
                    return sum;
                },
                0
            );

            if (scope.kanban.backlog.is_open) {
                nb_displayed++;
            }

            if (scope.kanban.archive.is_open) {
                nb_displayed++;
            }

            return nb_displayed;
        }

        function getNbHidden() {
            var nb_hidden = scope.kanban.board.columns.reduce(
                function (sum, column) {
                    if (! column.is_open) {
                        sum++;
                    }
                    return sum;
                },
                0
            );

            if (! scope.kanban.backlog.is_open) {
                nb_hidden++;
            }

            if (! scope.kanban.archive.is_open) {
                nb_hidden++;
            }

            return nb_hidden;
        }

        function getNbItemsDisplayed() {
            var nb_items_displayed = scope.kanban.board.columns.reduce(
                function (sum, column) {
                    if (column.is_open) {
                        sum += column.content.length;
                    }
                    return sum;
                },
                0
            );

            if (scope.kanban.backlog.is_open) {
                nb_items_displayed += scope.kanban.backlog.content.length;
            }

            if (scope.kanban.archive.is_open) {
                nb_items_displayed += scope.kanban.archive.content.length;
            }

            return nb_items_displayed;
        }

        function listener(new_value) {
            var nb_hidden     = new_value.nb_hidden,
                nb_displayed  = new_value.nb_displayed,
                sidebar_width = getSidebarWidth();

            $timeout(function() {
                var board_width = angular.element('body').width() - sidebar_width;

                if (nb_displayed === 0) {
                    setUniformSizeForHiddenColumns(board_element, board_width, nb_hidden);
                } else {
                    setUniformSizeForDisplayedColumns(board_element, board_width, nb_hidden, nb_displayed);
                }

                setUniformHeightForColumns(board_element);
                alignColumnTitles();

                $timeout(function() {
                    scope.$broadcast('rebuild:kustom-scroll');
                });
            });
        }

        function setUniformHeightForColumns(board_element) {
            board_element.css('height', angular.element('#kanban-app').outerHeight() - angular.element('#kanban-header').outerHeight());

            var columns = board_element.children().toArray();

            columns.forEach(function (column) {
                angular.element(column).css('height', '');
            });

            var max_height = columns.reduce(
                function (max, column) {
                    return Math.max(max, angular.element(column).outerHeight());
                },
                0
            );

            columns.forEach(function (child) {
                angular.element(child).css('height', max_height + 'px');
            });
        }

        function alignColumnTitles() {
            var column_titles = angular.element('.column-header > .column-label').toArray();

            column_titles.forEach(function (title) {
                title = angular.element(title);

                title.css('top', '0');
                if(title.outerHeight() >= 35) {
                    title.css('top', '-8px');
                }
            });
        }

        function setUniformSizeForDisplayedColumns(board_element, board_width, nb_hidden, nb_displayed) {
            var default_width_hidden = 75,
                width_displayed      = Math.floor(computeWidthDisplayed(board_width, nb_hidden, default_width_hidden, nb_displayed)),
                is_small_width       = width_displayed < 300;

            resizeColumn(scope.kanban.backlog, width_displayed, is_small_width, default_width_hidden);
            resizeColumn(scope.kanban.archive, width_displayed, is_small_width, default_width_hidden);
            scope.kanban.board.columns.map(function (column) {
                resizeColumn(column, width_displayed, is_small_width, default_width_hidden);
            });

            scope.kanban.backlog.resize_left = '';
            scope.kanban.archive.resize_left = '';
            scope.kanban.board.columns.map(function (column) {
                column.resize_left = '';
            });

            function computeWidthDisplayed(board_width, nb_hidden, default_width_hidden, nb_displayed) {
                var width_displayed = (board_width - nb_hidden * default_width_hidden) / nb_displayed;

                if (width_displayed < 250) {
                    width_displayed = 250;
                }

                return width_displayed;
            }

            function resizeColumn(column, width_displayed, is_small_width, default_width_hidden) {
                if (column.is_open) {
                    column.resize_width   = width_displayed + 'px';
                    column.is_small_width = is_small_width;

                } else {
                    column.resize_width = default_width_hidden + 'px';
                }
            }
        }

        function setUniformSizeForHiddenColumns(board_element, board_width, nb_hidden) {
            var width_hidden  = Math.floor(board_width / nb_hidden),
                magic_delta_x = 10,
                magic_top     = 32,
                label_width   = angular.element('.column-hidden > .column-label').width(),
                left          = (width_hidden - label_width) / 2 - magic_delta_x;

            scope.kanban.backlog.resize_width = width_hidden + 'px';
            scope.kanban.archive.resize_width = width_hidden + 'px';
            scope.kanban.board.columns.map(function (column) {
                column.resize_width = width_hidden + 'px';
            });

            scope.kanban.backlog.resize_left = left + 'px';
            scope.kanban.archive.resize_left = left + 'px';
            scope.kanban.board.columns.map(function (column) {
                column.resize_left = left + 'px';
            });
        }
    };
}
