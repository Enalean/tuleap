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
    = "or"i _ operand:and_expression _ tail:or? {
        return new OrOperand($operand, $tail);
    }

and_expression
    = expression:term _ tail:and? {
    	return new AndExpression($expression, $tail);
    }

and
    = "and"i _ operand:term _ tail:and? {
        return new AndOperand($operand, $tail);
    }

term
	= EqualComparison / ParenthesisTerm

ParenthesisTerm = "(" _ e:or_expression _ ")" { return $e; }

EqualComparison
    = field:Field _ "=" _ value:SimpleExpr {
        return new EqualComparison($field, $value);
    }

Field
  = name:$[a-zA-Z0-9_]+ { return $name; }

SimpleExpr
    = l:Literal { return $l; }

Literal
  = String / Float / Integer

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

Float
  = digits:$([0-9]+ "." [0-9]+) { return floatval($digits); }

_ "whitespace"
  = [ \t\n\r]*
