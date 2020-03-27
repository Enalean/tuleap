import moment from "moment";

export default CardFieldsService;

CardFieldsService.$inject = ["$sce", "$filter"];

function CardFieldsService($sce, $filter) {
    const highlight = $filter("tuleapHighlight");

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
        getCardFieldPermissionsValue,
        getCardFieldUserValue,
        isListBoundToAValueDifferentFromNone,
    };

    function cardFieldIsSimpleValue(type) {
        switch (type) {
            case "string":
            case "int":
            case "float":
            case "aid":
            case "atid":
            case "priority":
                return true;
            default:
                return false;
        }
    }

    function cardFieldIsList(type) {
        switch (type) {
            case "sb":
            case "msb":
            case "rb":
            case "cb":
            case "tbl":
            case "shared":
                return true;
            default:
                return false;
        }
    }

    function cardFieldIsDate(type) {
        switch (type) {
            case "date":
            case "lud":
            case "subon":
                return true;
            default:
                return false;
        }
    }

    function cardFieldIsText(type) {
        return type === "text";
    }

    function cardFieldIsFile(type) {
        return type === "file";
    }

    function cardFieldIsCross(type) {
        return type === "cross";
    }

    function cardFieldIsPermissions(type) {
        return type === "perm";
    }

    function cardFieldIsComputed(type) {
        return type === "computed";
    }

    function cardFieldIsUser(type) {
        return type === "subby" || type === "luby";
    }

    function getCardFieldListValues(values, filter_terms) {
        function getValueRendered(value) {
            if (value.color) {
                return getValueRenderedWithColor(value, filter_terms);
            } else if (value.tlp_color) {
                return getValueRenderedWithTlpColor(value, filter_terms);
            } else if (value.avatar_url) {
                return getCardFieldUserValue(value, filter_terms);
            }

            return highlight(value.label, filter_terms);
        }

        function getValueRenderedWithColor(value, filter_terms) {
            const r = parseInt(value.color.r, 10);
            const g = parseInt(value.color.g, 10);
            const b = parseInt(value.color.b, 10);
            const color = $sce.getTrustedHtml(`<span class="extra-card-field-color"
                style="background: rgb(${r}, ${g}, ${b})"></span>`);

            return color + highlight(value.label, filter_terms);
        }

        function getValueRenderedWithTlpColor({ label, tlp_color }, filter_terms) {
            const color = $sce.getTrustedHtml(
                `<span class="extra-card-field-color card-field-${tlp_color}"></span>`
            );

            return color + highlight(label, filter_terms);
        }

        return $sce.trustAsHtml(values.map(getValueRendered).join(", "));
    }

    function getCardFieldDateValue(value) {
        return moment(value).fromNow();
    }

    function getCardFieldFileValue(artifact_id, field_id, file_descriptions, filter_terms) {
        function getFileUrl(file) {
            return (
                "/plugins/tracker/attachments/" +
                encodeURIComponent(file.id) +
                "-" +
                encodeURIComponent(file.name)
            );
        }

        function getFileLink(file) {
            var file_name = highlight(file.name, filter_terms);

            return (
                '<a data-nodrag="true" href="' +
                getFileUrl(file) +
                '" title="' +
                file.description +
                '"><i class="fa fa-file-text"></i> ' +
                file_name +
                "</a>"
            );
        }

        return file_descriptions.map(getFileLink).join(", ");
    }

    function getCardFieldPermissionsValue(values) {
        return values.join(", ");
    }

    function getCardFieldUserValue(value, filter_terms) {
        let display_name;

        if (value.user_url === null) {
            display_name = highlight(value.display_name, filter_terms);
            return `<div class="tlp-avatar-mini"> </div><span>${display_name}</span>`;
        }

        display_name = highlight(value.display_name, filter_terms);
        return `<a data-nodrag="true" class="extra-card-field-user" href="${value.user_url}">
                            <div class="tlp-avatar-mini"><img src="${value.avatar_url}" /></div><span>${display_name}</span>
                        </a>`;
    }

    function isListBoundToAValueDifferentFromNone(values) {
        return values.find((value) => value.id !== null);
    }
}
