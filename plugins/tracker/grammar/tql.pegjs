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
    = EqualComparison
        / NotEqualComparison
        / LesserThanOrEqualComparison
        / GreaterThanOrEqualComparison
        / LesserThanComparison
        / GreaterThanComparison
        / BetweenComparison
        / ParenthesisTerm

ParenthesisTerm = "(" _ e:or_expression _ ")" { return $e; }

EqualComparison
    = field:Field _ "=" _ value_wrapper:SimpleExpr {
        return new EqualComparison($field, $value_wrapper);
    }

NotEqualComparison
    = field:Field _ "!=" _ value_wrapper:SimpleExpr {
        return new NotEqualComparison($field, $value_wrapper);
    }

LesserThanComparison
    = field:Field _ "<" _ value_wrapper:SimpleExpr {
        return new LesserThanComparison($field, $value_wrapper);
    }

GreaterThanComparison
    = field:Field _ ">" _ value_wrapper:SimpleExpr {
        return new GreaterThanComparison($field, $value_wrapper);
    }

LesserThanOrEqualComparison
    = field:Field _ "<=" _ value_wrapper:SimpleExpr {
        return new LesserThanOrEqualComparison($field, $value_wrapper);
    }

GreaterThanOrEqualComparison
    = field:Field _ ">=" _ value_wrapper:SimpleExpr {
        return new GreaterThanOrEqualComparison($field, $value_wrapper);
    }

BetweenComparison
    = field:Field _ "between"i _ "(" _ min_value_wrapper:SimpleExpr _ "," _ max_value_wrapper:SimpleExpr _ ")" {
        return new BetweenComparison($field, new BetweenValueWrapper($min_value_wrapper, $max_value_wrapper));
    }

Field
    = name:$[a-zA-Z0-9_]+ { return $name; }

SimpleExpr
    = l:Literal { return $l; }

Literal
    = String / Float / Integer / CurrentDateTime

String
     = String1
        / String2

String1
    = '"' chars:([^\n\r\f\\"] / "\\" nl:nl { return ""; } / escape)* '"' {
        return new SimpleValueWrapper(join("", $chars));
    }

String2
    = "'" chars:([^\n\r\f\\'] / "\\" nl:nl { return ""; } / escape)* "'" {
        return new SimpleValueWrapper(join("", $chars));
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
    = digits:$[0-9]+ {
        return new SimpleValueWrapper(intval($digits, 10));
    }

Float
    = digits:$([0-9]+ "." [0-9]+) {
        return new SimpleValueWrapper(floatval($digits));
    }

CurrentDateTime
    = "now"i _ "(" _ ")" _ period:PeriodCurrentDateTime? {
        return new CurrentDateTimeValueWrapper($period["sign"], $period["duration"]);
    }

PeriodCurrentDateTime
    = sign:$[+|-] _ number:$[0-9]+ designator:Designator {
        return array("sign" => $sign, "duration" => "P".$number.$designator);
    }

Designator
    = designator:$[d|w|m|y]i {
        return strtoupper($designator);
    }

_ "whitespace"
    = [ \t\n\r]*
