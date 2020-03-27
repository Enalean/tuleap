/* global $$:readonly $H:readonly $break:readonly */
// http://jibbering.com/faq/names/
// http://thinkweb2.com/projects/prototype/domlint-resolving-name-conflicts/
document.observe("dom:loaded", function () {
    //reserved in opera
    var reserved_names = {
        firefox:
            "hild,previousSibling,nextSibling,attributes,ownerDocument,insertBefore," +
            "replaceChild,removeChild,appendChild,hasChildNodes,cloneNode,normalize," +
            "isSupported,namespaceURI,prefix,localName,hasAttributes,tagName," +
            "getAttribute,setAttribute,removeAttribute,getAttributeNode," +
            "setAttributeNode,removeAttributeNode,getElementsByTagName,getAttributeNS," +
            "setAttributeNS,removeAttributeNS,getAttributeNodeNS,setAttributeNodeNS," +
            "getElementsByTagNameNS,hasAttribute,hasAttributeNS,ELEMENT_NODE," +
            "ATTRIBUTE_NODE,TEXT_NODE,CDATA_SECTION_NODE,ENTITY_REFERENCE_NODE," +
            "ENTITY_NODE,PROCESSING_INSTRUCTION_NODE,COMMENT_NODE,DOCUMENT_NODE," +
            "DOCUMENT_TYPE_NODE,DOCUMENT_FRAGMENT_NODE,NOTATION_NODE,id,title,lang," +
            "dir,className,elements,length,name,acceptCharset,action,enctype,method," +
            "target,submit,reset,encoding,offsetTop,offsetLeft,offsetWidth,offsetHeight," +
            "offsetParent,innerHTML,scrollTop,scrollLeft,scrollHeight,scrollWidth," +
            "clientHeight,clientWidth,tabIndex,blur,focus,spellcheck,style," +
            "removeEventListener,dispatchEvent,baseURI,compareDocumentPosition,textContent," +
            "isSameNode,lookupPrefix,isDefaultNamespace,lookupNamespaceURI," +
            "isEqualNode,getFeature,setUserData,getUserData,DOCUMENT_POSITION_DISCONNECTED," +
            "DOCUMENT_POSITION_PRECEDING,DOCUMENT_POSITION_FOLLOWING," +
            "DOCUMENT_POSITION_CONTAINS,DOCUMENT_POSITION_CONTAINED_BY," +
            "DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC",
        ie:
            "language,scrollHeight,isTextEdit,currentStyle,document,onmouseup,oncontextmenu," +
            "isMultiLine,clientHeight,onrowexit,onbeforepaste,onactivate,scrollLeft," +
            "lang,onmousemove,onmove,onselectstart,parentTextEdit,oncontrolselect," +
            "canHaveHTML,onkeypress,oncut,onrowenter,onmousedown,onpaste,className,id," +
            "onreadystatechange,onbeforedeactivate,hideFocus,dir,isContentEditable," +
            "onkeydown,clientWidth,onlosecapture,parentElement,ondrag,ondragstart," +
            "oncellchange,recordNumber,onfilterchange,onrowsinserted,ondatasetcomplete," +
            "onmousewheel,ondragenter,onblur,onresizeend,onerrorupdate,onbeforecopy," +
            "ondblclick,scopeName,onkeyup,onresizestart,onmouseover,onmouseleave,outerText," +
            "innerText,onmoveend,tagName,title,offsetWidth,onresize,contentEditable," +
            "runtimeStyle,filters,ondrop,onpage,onrowsdelete,tagUrn,offsetLeft,clientTop," +
            "style,onfocusout,clientLeft,ondatasetchanged,canHaveChildren,ondeactivate," +
            "isDisabled,onpropertychange,ondragover,onhelp,ondragend,onbeforeeditfocus," +
            "disabled,onfocus,behaviorUrns,accessKey,onscroll,onbeforeactivate,onbeforecut," +
            "readyState,all,sourceIndex,onclick,scrollTop,oncopy,onfocusin,tabIndex," +
            "onbeforeupdate,outerHTML,innerHTML,ondataavailable,offsetHeight,onmovestart," +
            "onmouseout,scrollWidth,offsetTop,onmouseenter,onlayoutcomplete,offsetParent," +
            "onafterupdate,ondragleave,children,parentNode,nodeValue,name,length,onreset," +
            "onsubmit,lastChild,elements,attributes,acceptCharset,action,method,nodeType," +
            "target,previousSibling,ownerDocument,nodeName,childNodes,nextSibling,firstChild," +
            "encoding",
        opera:
            "addEventListener,addRepetitionBlock,addRepetitionBlockByIndex," +
            "appendChild,attachEvent,blur,checkValidity,cloneNode,contains," +
            "detachEvent,dispatchEvent,dispatchFormChange,dispatchFormInput," +
            "focus,getAttribute,getAttributeNS,getAttributeNode,getAttributeNodeNS," +
            "getElementsByTagName,getElementsByTagNameNS,getFeature,hasAttribute," +
            "hasAttributeNS,hasAttributes,hasChildNodes,insertAdjacentElement," +
            "insertAdjacentHTML,insertAdjacentText,insertBefore,isDefaultNamespace," +
            "isSupported,item,lookupNamespaceURI,lookupPrefix,moveRepetitionBlock," +
            "namedItem,normalize,removeAttribute,removeAttributeNS,removeAttributeNode," +
            "removeChild,removeEventListener,removeNode,removeRepetitionBlock," +
            "replaceChild,reset,resetFromData,scrollIntoView,selectNodes," +
            "selectSingleNode,setAttribute,setAttributeNS,setAttributeNode," +
            "setAttributeNodeNS,submit,toString,accept,acceptCharset,action," +
            "all,attributes,childNodes,children,className,clientHeight," +
            "clientLeft,clientTop,clientWidth,contentEditable,currentStyle," +
            "data,dir,document,elements,encoding,enctype,firstChild,id," +
            "innerHTML,innerText,isContentEditable,lang,lastChild,length," +
            "localName,method,name,namespaceURI,nextSibling,nodeName,nodeType," +
            "nodeValue,offsetHeight,offsetLeft,offsetParent,offsetTop,offsetWidth," +
            "onblur,onclick,ondblclick,onfocus,onkeydown,onkeypress,onkeyup,onload," +
            "onmousedown,onmousemove,onmouseout,onmouseover,onmouseup,onunload," +
            "outerHTML,outerText,ownerDocument,parentElement,parentNode,prefix," +
            "previousSibling,repeatMax,repeatMin,repeatStart,repetitionBlocks," +
            "repetitionIndex,repetitionTemplate,repetitionType,replace,scrollHeight," +
            "scrollLeft,scrollTop,scrollWidth,sourceIndex,style,tagName,target," +
            "templateElements,text,textContent,title,unselectable",
    };
    $$("input").each(function (element) {
        if (element.name) {
            var name = new RegExp("(^|W)" + element.name + "(W|$)");
            var result = $H(reserved_names)
                .keys()
                .inject([], function (result, browser) {
                    if (reserved_names[browser].match(name)) {
                        result.push(browser);
                    }
                    return result;
                });
            if (result.length) {
                Element.setStyle(element, {
                    border: "5px dotted red",
                });
                if (
                    //eslint-disable-next-line no-alert
                    !confirm(
                        'An <input> has the attribute name="' +
                            element.name +
                            '" which is reserved in ' +
                            result.join(" and ")
                    )
                ) {
                    throw $break;
                }
            }
        }
    });
});
