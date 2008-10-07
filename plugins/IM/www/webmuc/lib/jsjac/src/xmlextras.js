
/* Copyright 2006 Erik Arvidsson
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you
 * may not use this file except in compliance with the License.  You
 * may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied.  See the License for the specific language governing
 * permissions and limitations under the License.
 */

/**
 * @fileoverview Wrapper to make working with XmlHttpRequest and the
 * DOM more convenient (cross browser compliance).
 * this code is taken from
 * http://webfx.eae.net/dhtml/xmlextras/xmlextras.html
 * @author Stefan Strigler steve@zeank.in-berlin.de
 * @version $Revision: 437 $
 */

/**
 * XmlHttp factory
 * @private
 */
function XmlHttp() {}

/**
 * creates a cross browser compliant XmlHttpRequest object
 */
XmlHttp.create = function () {
  try {
    if (window.XMLHttpRequest) {
      var req = new XMLHttpRequest();
     
      // some versions of Moz do not support the readyState property
      // and the onreadystate event so we patch it!
      if (req.readyState == null) {
	req.readyState = 1;
	req.addEventListener("load", function () {
			       req.readyState = 4;
			       if (typeof req.onreadystatechange == "function")
				 req.onreadystatechange();
			     }, false);
      }
     
      return req;
    }
    if (window.ActiveXObject) {
      return new ActiveXObject(XmlHttp.getPrefix() + ".XmlHttp");
    }
  }
  catch (ex) {}
  // fell through
  throw new Error("Your browser does not support XmlHttp objects");
};

/**
 * used to find the Automation server name
 * @private
 */
XmlHttp.getPrefix = function() {
  if (XmlHttp.prefix) // I know what you did last summer
    return XmlHttp.prefix;
 
  var prefixes = ["MSXML2", "Microsoft", "MSXML", "MSXML3"];
  var o;
  for (var i = 0; i < prefixes.length; i++) {
    try {
      // try to create the objects
      o = new ActiveXObject(prefixes[i] + ".XmlHttp");
      return XmlHttp.prefix = prefixes[i];
    }
    catch (ex) {};
  }
 
  throw new Error("Could not find an installed XML parser");
};


/**
 * XmlDocument factory
 * @private
 */
function XmlDocument() {}

XmlDocument.create = function (name,ns) {
  name = name || 'foo';
  ns = ns || '';
  try {
    var doc;
    // DOM2
    if (document.implementation && document.implementation.createDocument) {
      doc = document.implementation.createDocument(ns, name, null);
      // some versions of Moz do not support the readyState property
      // and the onreadystate event so we patch it!
      if (doc.readyState == null) {
	doc.readyState = 1;
	doc.addEventListener("load", function () {
			       doc.readyState = 4;
			       if (typeof doc.onreadystatechange == "function")
				 doc.onreadystatechange();
			     }, false);
      }
    } else if (window.ActiveXObject) {
      doc = new ActiveXObject(XmlDocument.getPrefix() + ".DomDocument");
    }
   
    if (!doc.documentElement || doc.documentElement.tagName != name ||
        (doc.documentElement.namespaceURI &&
         doc.documentElement.namespaceURI != ns)) {
          try {
            if (ns != '')
              doc.appendChild(doc.createElement(name)).
                setAttribute('xmlns',ns);
            else
              doc.appendChild(doc.createElement(name));
          } catch (dex) {
            doc = document.implementation.createDocument(ns,name,null);
           
            if (doc.documentElement == null)
              doc.appendChild(doc.createElement(name));

             // fix buggy opera 8.5x
            if (ns != '' &&
                doc.documentElement.getAttribute('xmlns') != ns) {
              doc.documentElement.setAttribute('xmlns',ns);
            }
          }
        }
   
    return doc;
  }
  catch (ex) { alert(ex.name+": "+ex.message); }
  throw new Error("Your browser does not support XmlDocument objects");
};

/**
 * used to find the Automation server name
 * @private
 */
XmlDocument.getPrefix = function() {
  if (XmlDocument.prefix)
    return XmlDocument.prefix;

  var prefixes = ["MSXML2", "Microsoft", "MSXML", "MSXML3"];
  var o;
  for (var i = 0; i < prefixes.length; i++) {
    try {
      // try to create the objects
      o = new ActiveXObject(prefixes[i] + ".DomDocument");
      return XmlDocument.prefix = prefixes[i];
    }
    catch (ex) {};
  }
 
  throw new Error("Could not find an installed XML parser");
};


// Create the loadXML method
if (typeof(Document) != 'undefined' && window.DOMParser) {

  /**
   * XMLDocument did not extend the Document interface in some
   * versions of Mozilla.
   * @private
   */
  Document.prototype.loadXML = function (s) {
	
    // parse the string to a new doc
    var doc2 = (new DOMParser()).parseFromString(s, "text/xml");
	
    // remove all initial children
    while (this.hasChildNodes())
      this.removeChild(this.lastChild);
		
    // insert and import nodes
    for (var i = 0; i < doc2.childNodes.length; i++) {
      this.appendChild(this.importNode(doc2.childNodes[i], true));
    }
  };
 }

// Create xml getter for Mozilla
if (window.XMLSerializer &&
    window.Node && Node.prototype && Node.prototype.__defineGetter__) {

  /**
   * xml getter
   *
   * This serializes the DOM tree to an XML String
   *
   * Usage: var sXml = oNode.xml
   * @deprecated
   * @private
   */
  // XMLDocument did not extend the Document interface in some versions
  // of Mozilla. Extend both!
  XMLDocument.prototype.__defineGetter__("xml", function () {
                                           return (new XMLSerializer()).serializeToString(this);
                                         });
  /**
   * xml getter
   *
   * This serializes the DOM tree to an XML String
   *
   * Usage: var sXml = oNode.xml
   * @deprecated
   * @private
   */
  Document.prototype.__defineGetter__("xml", function () {
                                        return (new XMLSerializer()).serializeToString(this);
                                      });

  /**
   * xml getter
   *
   * This serializes the DOM tree to an XML String
   *
   * Usage: var sXml = oNode.xml
   * @deprecated
   * @private
   */
  Node.prototype.__defineGetter__("xml", function () {
                                    return (new XMLSerializer()).serializeToString(this);
                                  });
 }
