
/**
 * an error packet for internal use
 * @private
 * @constructor
 */
function JSJaCError(code,type,condition) {
  var xmldoc = XmlDocument.create("error","jsjac");

  xmldoc.documentElement.setAttribute('code',code);
  xmldoc.documentElement.setAttribute('type',type);
  xmldoc.documentElement.appendChild(xmldoc.createElement(condition)).
    setAttribute('xmlns','urn:ietf:params:xml:ns:xmpp-stanzas');
  return xmldoc.documentElement;
}
