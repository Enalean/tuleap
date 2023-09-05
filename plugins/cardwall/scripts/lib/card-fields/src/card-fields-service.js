import { html } from "hybrids";
import { highlightFilterElements } from "./highlight-filter-template";

export default CardFieldsService;

CardFieldsService.$inject = ["$sce"];

function CardFieldsService($sce) {
    return {
        cardFieldIsSimpleValue,
        cardFieldIsList,
        cardFieldIsOpenList,
        cardFieldIsText,
        cardFieldIsDate,
        cardFieldIsFile,
        cardFieldIsCross,
        cardFieldIsPermissions,
        cardFieldIsUser,
        cardFieldIsComputed,
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
            case "shared":
                return true;
            default:
                return false;
        }
    }

    function cardFieldIsOpenList(type) {
        return type === "tbl";
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
                return getCardFieldUserValueTemplate(value, filter_terms);
            }

            return highlightFilterElements(value.label, filter_terms);
        }

        function getValueRenderedWithColor(value, filter_terms) {
            const r = parseInt(value.color.r, 10);
            const g = parseInt(value.color.g, 10);
            const b = parseInt(value.color.b, 10);

            const styles = {
                background: `rgb(${r}, ${g}, ${b})`,
            };

            return html`<span class="extra-card-field-color" style="${styles}"></span
                >${highlightFilterElements(value.label, filter_terms)}`;
        }

        function getValueRenderedWithTlpColor({ label, tlp_color }, filter_terms) {
            const classlist = ["extra-card-field-color", `card-field-${tlp_color}`];

            return html`<span class="${classlist}"></span>${highlightFilterElements(
                    label,
                    filter_terms,
                )}`;
        }

        return getHTMLStringFromTemplate(renderListItems(values, getValueRendered));
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
            const file_display = html`<i class="fas fa-paperclip extra-card-field-file-icon"></i
                >${highlightFilterElements(file.name, filter_terms)}`;
            return html`<a data-nodrag="true" href="${getFileUrl(file)}" title="${file.description}"
                >${file_display}</a
            >`;
        }

        return getHTMLStringFromTemplate(renderListItems(file_descriptions, getFileLink));
    }

    function getCardFieldPermissionsValue(values, filter_terms) {
        return getHTMLStringFromTemplate(
            renderListItems(
                values,
                (value) => html`${highlightFilterElements(value, filter_terms)}`,
            ),
        );
    }

    function getCardFieldUserValueTemplate(value, filter_terms) {
        const display_name = highlightFilterElements(value.display_name, filter_terms);
        if (value.user_url === null) {
            return html`<div class="tlp-avatar-mini"></div>
                <span>${display_name}</span>`;
        }

        return html`<a data-nodrag="true" class="extra-card-field-user" href="${value.user_url}">
            <div class="tlp-avatar-mini"><img loading="lazy" src="${value.avatar_url}" /></div>
            <span>${display_name}</span>
        </a>`;
    }

    function getCardFieldUserValue(value, filter_terms) {
        return getHTMLStringFromTemplate(getCardFieldUserValueTemplate(value, filter_terms));
    }

    function isListBoundToAValueDifferentFromNone(values) {
        return values.find((value) => value.id !== null);
    }

    function renderListItems(items, render_item) {
        let templated_content = html``;

        for (const [i, file] of items.entries()) {
            if (i === 0) {
                templated_content = render_item(file);
            } else {
                templated_content = html`${templated_content}, ${render_item(file)}`;
            }
        }

        return templated_content;
    }

    function getHTMLStringFromTemplate(template) {
        const element = document.createElement("div");
        template({}, element);

        return $sce.trustAsHtml(element.innerHTML);
    }
}
