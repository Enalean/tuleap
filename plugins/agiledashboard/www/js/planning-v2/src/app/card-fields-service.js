(function () {
    angular
        .module('planning')
        .service('CardFieldsService', CardFieldsService);

    CardFieldsService.$inject = ['$sce'];

    function CardFieldsService($sce) {
        return {
            cardFieldIsSimpleValue      : cardFieldIsSimpleValue,
            cardFieldIsList             : cardFieldIsList,
            cardFieldIsText             : cardFieldIsText,
            cardFieldIsDate             : cardFieldIsDate,
            cardFieldIsFile             : cardFieldIsFile,
            cardFieldIsCross            : cardFieldIsCross,
            cardFieldIsPermissions      : cardFieldIsPermissions,
            getCardFieldListValues      : getCardFieldListValues,
            getCardFieldTextValue       : getCardFieldTextValue,
            getCardFieldFileValue       : getCardFieldFileValue,
            getCardFieldCrossValue      : getCardFieldCrossValue,
            getCardFieldPermissionsValue: getCardFieldPermissionsValue
        };

        function cardFieldIsSimpleValue(type) {
            switch (type) {
                case 'string':
                case 'int':
                case 'float':
                case 'aid':
                case 'atid':
                case 'subby':
                case 'computed':
                case 'priority':
                    return true;
            }
        }

        function cardFieldIsList(type) {
            switch (type) {
                case 'sb':
                case 'msb':
                case 'rb':
                case 'cb':
                case 'tbl':
                case 'shared':
                    return true;
            }
        }

        function cardFieldIsDate(type) {
            switch (type) {
                case 'date':
                case 'lud':
                case 'subon':
                    return true;
            }
        }

        function cardFieldIsText(type) {
            return type == 'text';
        }

        function cardFieldIsFile(type) {
            return type == 'file';
        }

        function cardFieldIsCross(type) {
            return type == 'cross';
        }

        function cardFieldIsPermissions(type) {
            return type == 'perm';
        }

        function getCardFieldListValues(values) {
            function getValueRenderedWithColor(value) {
                var color = '';

                if (value.color) {
                    var rgb = 'rgb(' + value.color.r + ', ' + value.color.g + ', ' + value.color.b + ')';
                    color = '<span class="color" style="background: ' + rgb + '"></span>';
                }

                return color + value.label;
            }

            return $sce.trustAsHtml(_.map(values, getValueRenderedWithColor).join(', '));
        }

        function getCardFieldTextValue(value) {
            return $sce.trustAsHtml(value);
        }

        function getCardFieldFileValue(artifact_id, field_id, file_descriptions) {
            function getFileUrl(file) {
                return '/plugins/tracker/?aid=' + artifact_id + '&field=' + field_id + '&func=show-attachment&attachment=' + file.id;
            }

            function getFileLink(file) {
                return '<a data-nodrag href="' + getFileUrl(file) + '"><i class="icon-file-text-alt"></i> ' + file.name + '</a>';
            }

            return $sce.trustAsHtml(_.map(file_descriptions, getFileLink).join(', '));
        }

        function getCardFieldCrossValue(links) {
            function getCrossLink(link) {
                return $sce.trustAsHtml('<a data-nodrag href="' + link.url + '">' + link.ref + '</a>');
            }

            return $sce.trustAsHtml(_.map(links, getCrossLink).join(', '));
        }

        function getCardFieldPermissionsValue(values) {
            return _(values).join(', ');
        }
    }
})();
