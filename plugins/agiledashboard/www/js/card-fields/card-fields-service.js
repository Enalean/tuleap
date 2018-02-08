import _      from 'lodash';
import moment from 'moment';

export default CardFieldsService;

CardFieldsService.$inject = [
    '$sce',
    '$filter'
];

function CardFieldsService(
    $sce,
    $filter
) {
    const highlight = $filter('tuleapHighlight');

    return {
        cardFieldIsSimpleValue,
        cardFieldIsList,
        cardFieldIsText,
        cardFieldIsDate,
        cardFieldIsFile,
        cardFieldIsCross,
        cardFieldIsPermissions,
        cardFieldIsUser,
        cardFieldIsComputed,
        getCardFieldDateValue,
        getCardFieldListValues,
        getCardFieldFileValue,
        getCardFieldCrossValue,
        getCardFieldPermissionsValue,
        getCardFieldUserValue,
        isListBoundToAValueDifferentFromNone
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
                color = '<span class="extra-card-field-color" style="background: ' + rgb + '"></span>';

            return color + highlight(_.escape(value.label), filter_terms);
        }

        return $sce.trustAsHtml(values.map(getValueRendered).join(', '));
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

            return '<a data-nodrag="true" href="' + getFileUrl(file) + '" title="' + _.escape(file.description) + '"><i class="fa fa-file-text"></i> ' + file_name + '</a>';
        }

        return $sce.trustAsHtml(file_descriptions.map(getFileLink).join(', '));
    }

    function getCardFieldCrossValue(links, filter_terms) {
        function getCrossLink(link) {
            return $sce.trustAsHtml('<a data-nodrag="true" href="' + link.url + '">' + highlight(_.escape(link.ref), filter_terms) + '</a>');
        }

        return $sce.trustAsHtml(links.map(getCrossLink).join(', '));
    }

    function getCardFieldPermissionsValue(values) {
        return values.join(', ');
    }

    function getCardFieldUserValue(value, filter_terms) {
        let display_name,
            link;

        if (value.user_url === null) {
            display_name = highlight(_.escape(value.display_name), filter_terms);
            link         = `<div class="tlp-avatar-mini"> </div><span>${ display_name }</span>`;
        } else {
            display_name = highlight(_.escape(value.display_name), filter_terms);
            link         = `<a data-nodrag="true" class="extra-card-field-user" href="${ value.user_url }">
                                <div class="tlp-avatar-mini"><img src="${ value.avatar_url }" /></div><span>${ display_name }</span>
                            </a>`;
        }

        return $sce.trustAsHtml(link);
    }

    function isListBoundToAValueDifferentFromNone(values) {
        return values.find(value => (value.id !== null));
    }
}
