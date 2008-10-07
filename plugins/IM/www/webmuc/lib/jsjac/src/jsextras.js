/**
 * @fileoverview Collection of functions to make live easier
 * @author Stefan Strigler
 * @version $Revision: 437 $
 */

/**
 * Convert special chars to HTML entities
 * @addon
 * @return The string with chars encoded for HTML
 * @type String
 */
String.prototype.htmlEnc = function() {
  var str = this.replace(/&/g,"&amp;");
  str = str.replace(/</g,"&lt;");
  str = str.replace(/>/g,"&gt;");
  str = str.replace(/\"/g,"&quot;");
  str = str.replace(/\n/g,"<br />");
  return str;
};

/**
 * Converts from jabber timestamps to JavaScript Date objects
 * @addon
 * @param {String} ts A string representing a jabber datetime timestamp as
 * defined by {@link http://www.xmpp.org/extensions/xep-0082.html XEP-0082}
 * @return A javascript Date object corresponding to the jabber DateTime given
 * @type Date
 */
Date.jab2date = function(ts) {
  var date = new Date(Date.UTC(ts.substr(0,4),ts.substr(5,2)-1,ts.substr(8,2),ts.substr(11,2),ts.substr(14,2),ts.substr(17,2)));
  if (ts.substr(ts.length-6,1) != 'Z') { // there's an offset
    var offset = new Date();
    offset.setTime(0);
    offset.setUTCHours(ts.substr(ts.length-5,2));
    offset.setUTCMinutes(ts.substr(ts.length-2,2));
    if (ts.substr(ts.length-6,1) == '+')
      date.setTime(date.getTime() - offset.getTime());
    else if (ts.substr(ts.length-6,1) == '-')
      date.setTime(date.getTime() + offset.getTime());
  }
  return date;
};

/**
 * Takes a timestamp in the form of 2004-08-13T12:07:04+02:00 as argument
 * and converts it to some sort of humane readable format
 * @addon
 */
Date.hrTime = function(ts) {
  return Date.jab2date(ts).toLocaleString();
};

/**
 * somewhat opposit to {@link #hrTime}
 * expects a javascript Date object as parameter and returns a jabber
 * date string conforming to
 * {@link http://www.xmpp.org/extensions/xep-0082.html XEP-0082}
 * @see #hrTime
 * @return The corresponding jabber DateTime string
 * @type String
 */
Date.prototype.jabberDate = function() {
  var padZero = function(i) {
    if (i < 10) return "0" + i;
    return i;
  };

  var jDate = this.getUTCFullYear() + "-";
  jDate += padZero(this.getUTCMonth()+1) + "-";
  jDate += padZero(this.getUTCDate()) + "T";
  jDate += padZero(this.getUTCHours()) + ":";
  jDate += padZero(this.getUTCMinutes()) + ":";
  jDate += padZero(this.getUTCSeconds()) + "Z";

  return jDate;
};

/**
 * Determines the maximum of two given numbers
 * @addon
 * @param {Number} A a number
 * @param {Number} B another number
 * @return the maximum of A and B
 * @type Number
 */
Number.max = function(A, B) {
  return (A > B)? A : B;
};
