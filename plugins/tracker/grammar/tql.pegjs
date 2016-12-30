/*

 _____     _
|_   _|_ _| |___ ___ ___
  | | | | | | -_| .'| . |
  |_| |___|_|___|__,|  _|
                     |_|

 _____
|     |_ _ ___ ___ _ _
|  |  | | | -_|  _| | |
|__  _|___|___|_| |_  |
   |__|           |___|

 __
|  |   ___ ___ ___ _ _ ___ ___ ___
|  |__| .'|   | . | | | .'| . | -_|
|_____|__,|_|_|_  |___|__,|_  |___|
              |___|       |___|


(summary = 'ありがとう' or status = "open" and subby = 'Toto' or subby = 'Titi')


summary = 'ありがとう' or status = "open" and (subby = 'Toto' or subby = 'Titi')


summary = 'ありがとう' or
    status = "open" and
    (
            subby = 'Toto'
         or subby = 'Titi'
         or subby = myself()
    )
*/

or_expression
    = _ expression:and_expression _ tail:or? {
    	return new OrExpression($expression, $tail);
    }

or
    = O R _ operand:and_expression _ tail:or? {
        return new OrOperand($operand, $tail);
    }

and_expression
    = expression:term _ tail:and? {
    	return new AndExpression($expression, $tail);
    }

and
    = A N D _ operand:term _ tail:and? {
        return new AndOperand($operand, $tail);
    }

term
	= EqualComparison / ParenthesisTerm

ParenthesisTerm = "(" _ e:or_expression _ ")" { return $e; }

EqualComparison
    = field:Field _ "=" _ value:SimpleExpr {
  	return new Comparison($field, '=', $value);
    }

Field
  = name:$[a-zA-Z0-9_]+ { return $name; }

SimpleExpr
  = l:Literal { return array( "literal" => $l ); }
  / f:FunctionCall { return array( "function" => $f ); }

FunctionCall
  = M Y S E L F "()" { return "MYSELF"; }

Literal
  = String / Integer

String
  = String1
  / String2

String1
  = '"' chars:([^\n\r\f\\"] / "\\" nl:nl { return ""; } / escape)* '"' {
      return join("", $chars);
    }

String2
  = "'" chars:([^\n\r\f\\'] / "\\" nl:nl { return ""; } / escape)* "'" {
      return join("", $chars);
    }

hex
  = [0-9a-f]i

nonascii
  = [\x80-\uFFFF]

unicode
  = "\\u" digits:$(hex hex? hex? hex? hex? hex?) (nl / _)? {
      return chr_unicode(intval($digits, 16));
    }

escape
  = unicode
  / "\\" ch:[^\r\n\f0-9a-f]i { return $ch; }

nl
  = "\n"
  / "\r\n"
  / "\r"
  / "\f"

Integer "integer"
  = digits:$[0-9]+ { return intval($digits, 10); }

_ "whitespace"
  = [ \t\n\r]*


A  = "a"i / "\\" "0"? "0"? "0"? "0"? [\x41\x61] ("\r\n" / [ \t\r\n\f])? { return "a"; }
C  = "c"i / "\\" "0"? "0"? "0"? "0"? [\x43\x63] ("\r\n" / [ \t\r\n\f])? { return "c"; }
D  = "d"i / "\\" "0"? "0"? "0"? "0"? [\x44\x64] ("\r\n" / [ \t\r\n\f])? { return "d"; }
E  = "e"i / "\\" "0"? "0"? "0"? "0"? [\x45\x65] ("\r\n" / [ \t\r\n\f])? { return "e"; }
F  = "f"i / "\\" "0"? "0"? "0"? "0"? [\x46\x66] ("\r\n" / [ \t\r\n\f])? / "\\f"i { return "f"; }
G  = "g"i / "\\" "0"? "0"? "0"? "0"? [\x47\x67] ("\r\n" / [ \t\r\n\f])? / "\\g"i { return "g"; }
H  = "h"i / "\\" "0"? "0"? "0"? "0"? [\x48\x68] ("\r\n" / [ \t\r\n\f])? / "\\h"i { return "h"; }
I  = "i"i / "\\" "0"? "0"? "0"? "0"? [\x49\x69] ("\r\n" / [ \t\r\n\f])? / "\\i"i { return "i"; }
K  = "k"i / "\\" "0"? "0"? "0"? "0"? [\x4b\x6b] ("\r\n" / [ \t\r\n\f])? / "\\k"i { return "k"; }
L  = "l"i / "\\" "0"? "0"? "0"? "0"? [\x4c\x6c] ("\r\n" / [ \t\r\n\f])? / "\\l"i { return "l"; }
M  = "m"i / "\\" "0"? "0"? "0"? "0"? [\x4d\x6d] ("\r\n" / [ \t\r\n\f])? / "\\m"i { return "m"; }
N  = "n"i / "\\" "0"? "0"? "0"? "0"? [\x4e\x6e] ("\r\n" / [ \t\r\n\f])? / "\\n"i { return "n"; }
O  = "o"i / "\\" "0"? "0"? "0"? "0"? [\x4f\x6f] ("\r\n" / [ \t\r\n\f])? / "\\o"i { return "o"; }
P  = "p"i / "\\" "0"? "0"? "0"? "0"? [\x50\x70] ("\r\n" / [ \t\r\n\f])? / "\\p"i { return "p"; }
R  = "r"i / "\\" "0"? "0"? "0"? "0"? [\x52\x72] ("\r\n" / [ \t\r\n\f])? / "\\r"i { return "r"; }
S  = "s"i / "\\" "0"? "0"? "0"? "0"? [\x53\x73] ("\r\n" / [ \t\r\n\f])? / "\\s"i { return "s"; }
T  = "t"i / "\\" "0"? "0"? "0"? "0"? [\x54\x74] ("\r\n" / [ \t\r\n\f])? / "\\t"i { return "t"; }
U  = "u"i / "\\" "0"? "0"? "0"? "0"? [\x55\x75] ("\r\n" / [ \t\r\n\f])? / "\\u"i { return "u"; }
X  = "x"i / "\\" "0"? "0"? "0"? "0"? [\x58\x78] ("\r\n" / [ \t\r\n\f])? / "\\x"i { return "x"; }
Y  = "y"i / "\\" "0"? "0"? "0"? "0"? [\x59\x79] ("\r\n" / [ \t\r\n\f])? / "\\y"i { return "y"; }
Z  = "z"i / "\\" "0"? "0"? "0"? "0"? [\x5a\x7a] ("\r\n" / [ \t\r\n\f])? / "\\z"i { return "z"; }
