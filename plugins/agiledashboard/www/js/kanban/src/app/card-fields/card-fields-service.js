import _ from 'lodash';

export default CardFieldsService;

CardFieldsService.$inject = [
    '$sce',
    '$filter',
    'moment'
];

function CardFieldsService(
    $sce,
    $filter,
    moment
) {
    var highlight = $filter('tuleapHighlight');

    return {
        cardFieldIsSimpleValue      : cardFieldIsSimpleValue,
        cardFieldIsList             : cardFieldIsList,
        cardFieldIsText             : cardFieldIsText,
        cardFieldIsDate             : cardFieldIsDate,
        cardFieldIsFile             : cardFieldIsFile,
        cardFieldIsCross            : cardFieldIsCross,
        cardFieldIsPermissions      : cardFieldIsPermissions,
        cardFieldIsUser             : cardFieldIsUser,
        cardFieldIsComputed         : cardFieldIsComputed,
        getCardFieldDateValue       : getCardFieldDateValue,
        getCardFieldListValues      : getCardFieldListValues,
        getCardFieldFileValue       : getCardFieldFileValue,
        getCardFieldCrossValue      : getCardFieldCrossValue,
        getCardFieldPermissionsValue: getCardFieldPermissionsValue,
        getCardFieldUserValue       : getCardFieldUserValue
    };

    function cardFieldIsSimpleValue(type) {
        switch (type) {
            case 'string':
            case 'int':
            case 'float':
            case 'aid':
            case 'atid':
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
        return type === 'text';
    }

    function cardFieldIsFile(type) {
        return type === 'file';
    }

    function cardFieldIsCross(type) {
        return type === 'cross';
    }

    function cardFieldIsPermissions(type) {
        return type === 'perm';
    }

    function cardFieldIsComputed(type) {
        return type === 'computed';
    }

    function cardFieldIsUser(type) {
        return type === 'subby' || type === 'luby';
    }

    function getCardFieldListValues(values, filter_terms) {
        function getValueRendered(value) {
            if (value.color) {
                return getValueRenderedWithColor(value, filter_terms);
            } else if (value.avatar_url) {
                return getCardFieldUserValue(value, filter_terms);
            }

            return highlight(_.escape(value.label), filter_terms);
        }

        function getValueRenderedWithColor(value, filter_terms) {
            var rgb   = 'rgb(' + _.escape(value.color.r) + ', ' + _.escape(value.color.g) + ', ' + _.escape(value.color.b) + ')',
                color = '<span class="color" style="background: ' + rgb + '"></span>';

            return color + highlight(_.escape(value.label), filter_terms);
        }

        return $sce.trustAsHtml(_.map(values, getValueRendered).join(', '));
    }

    function getCardFieldDateValue(value) {
        return $sce.trustAsHtml(moment(_.escape(value)).fromNow());
    }

    function getCardFieldFileValue(artifact_id, field_id, file_descriptions, filter_terms) {
        function getFileUrl(file) {
            return '/plugins/tracker/?aid=' + artifact_id + '&field=' + field_id + '&func=show-attachment&attachment=' + file.id;
        }

        function getFileLink(file) {
            var file_name = highlight(_.escape(file.name), filter_terms);

            return '<a data-nodrag="true" href="' + getFileUrl(file) + '" title="' + _.escape(file.description) + '"><i class="icon-file-text-alt"></i> ' + file_name + '</a>';
        }

        return $sce.trustAsHtml(_.map(file_descriptions, getFileLink).join(', '));
    }

    function getCardFieldCrossValue(links, filter_terms) {
        function getCrossLink(link) {
            return $sce.trustAsHtml('<a data-nodrag="true" href="' + link.url + '">' + highlight(_.escape(link.ref), filter_terms) + '</a>');
        }

        return $sce.trustAsHtml(_.map(links, getCrossLink).join(', '));
    }

    function getCardFieldPermissionsValue(values) {
        return _(values).join(', ');
    }

    function getCardFieldUserValue(value, filter_terms) {
        var avatar,
            display_name,
            link;

        if (value.user_url === null) {
            avatar       = '<div class="avatar"> </div> ';
            display_name = highlight(_.escape(value.display_name), filter_terms);
            link         = avatar + display_name;
        } else {
            avatar       = '<div class="avatar"><img src="' + value.avatar_url + '" /></div> ';
            display_name = highlight(_.escape(value.display_name), filter_terms);
            link         = '<a data-nodrag="true" href="' + value.user_url + '">' + avatar + display_name + '</a>';
        }

        return $sce.trustAsHtml('<div class="user">' + link + '</div>');
    }
}
