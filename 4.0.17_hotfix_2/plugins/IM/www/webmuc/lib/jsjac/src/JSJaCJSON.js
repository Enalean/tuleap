/* Copyright (c) 2005-2007 Sam Stephenson
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use, copy,
 * modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/*
  json.js
  taken from prototype.js, made static
*/
function JSJaCJSON() {}
JSJaCJSON.toString = function (obj) {
  var m = {
    '\b': '\\b',
    '\t': '\\t',
    '\n': '\\n',
    '\f': '\\f',
    '\r': '\\r',
    '"' : '\\"',
    '\\': '\\\\'
  },
  s = {
    array: function (x) {
      var a = ['['], b, f, i, l = x.length, v;
      for (i = 0; i < l; i += 1) {
        v = x[i];
        f = s[typeof v];
        if (f) {
          v = f(v);
          if (typeof v == 'string') {
            if (b) {
              a[a.length] = ',';
            }
            a[a.length] = v;
            b = true;
          }
        }
      }
      a[a.length] = ']';
      return a.join('');
    },
    'boolean': function (x) {
      return String(x);
    },
    'null': function (x) {
      return "null";
    },
    number: function (x) {
      return isFinite(x) ? String(x) : 'null';
    },
    object: function (x) {
      if (x) {
        if (x instanceof Array) {
          return s.array(x);
        }
        var a = ['{'], b, f, i, v;
        for (i in x) {
          if (x.hasOwnProperty(i)) {
            v = x[i];
            f = s[typeof v];
            if (f) {
              v = f(v);
              if (typeof v == 'string') {
                if (b) {
                  a[a.length] = ',';
                }
                a.push(s.string(i), ':', v);
                b = true;
              }
            }
          }
        }
         
        a[a.length] = '}';
        return a.join('');
      }
      return 'null';
    },
    string: function (x) {
      if (/["\\\x00-\x1f]/.test(x)) {
                    x = x.replace(/([\x00-\x1f\\"])/g, function(a, b) {
          var c = m[b];
          if (c) {
            return c;
          }
          c = b.charCodeAt();
          return '\\u00' +
          Math.floor(c / 16).toString(16) +
          (c % 16).toString(16);
        });
  }
  return '"' + x + '"';
}
  };

switch (typeof(obj)) {
 case 'object':
   return s.object(obj);
 case 'array':
   return s.array(obj);
   
 }
};

JSJaCJSON.parse = function (str) {
  try {
    return !(/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(
                                                       str.replace(/"(\\.|[^"\\])*"/g, ''))) &&
            eval('(' + str + ')');
    } catch (e) {
        return false;
    }
};
