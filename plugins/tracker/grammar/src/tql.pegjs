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
    AND WITHOUT PARENT
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
    = Comparison
        / ParenthesisTerm
        / LinkCondition

Comparison
    = EqualComparison
        / NotEqualComparison
        / LesserThanOrEqualComparison
        / GreaterThanOrEqualComparison
        / LesserThanComparison
        / GreaterThanComparison
        / BetweenComparison
        / InComparison
        / NotInComparison

LinkCondition
    = WithParent
        / WithoutParent

WithParent = "with parent"i _ condition:ParentCondition? {
        return new WithParent($condition);
    }

WithoutParent = "without parent"i _ condition:ParentCondition? {
        return new WithoutParent($condition);
    }

ParentCondition = "artifact"i _ "=" _ id:$[0-9]+ {
        return new ParentArtifactCondition($id);
    }

ParenthesisTerm = "(" _ e:or_expression _ ")" {
        return new Parenthesis($e);
    }

EqualComparison
    = searchable:Searchable _ "=" _ value_wrapper:SimpleExpr {
        return new EqualComparison($searchable, $value_wrapper);
    }

NotEqualComparison
    = searchable:Searchable _ "!=" _ value_wrapper:SimpleExpr {
        return new NotEqualComparison($searchable, $value_wrapper);
    }

LesserThanComparison
    = searchable:Searchable _ "<" _ value_wrapper:SimpleExpr {
        return new LesserThanComparison($searchable, $value_wrapper);
    }

GreaterThanComparison
    = searchable:Searchable _ ">" _ value_wrapper:SimpleExpr {
        return new GreaterThanComparison($searchable, $value_wrapper);
    }

LesserThanOrEqualComparison
    = searchable:Searchable _ "<=" _ value_wrapper:SimpleExpr {
        return new LesserThanOrEqualComparison($searchable, $value_wrapper);
    }

GreaterThanOrEqualComparison
    = searchable:Searchable _ ">=" _ value_wrapper:SimpleExpr {
        return new GreaterThanOrEqualComparison($searchable, $value_wrapper);
    }

BetweenComparison
    = searchable:Searchable _ "between"i _ "(" _ min_value_wrapper:SimpleExpr _ "," _ max_value_wrapper:SimpleExpr _ ")" {
        return new BetweenComparison($searchable, new BetweenValueWrapper($min_value_wrapper, $max_value_wrapper));
    }

NotInComparison
    = searchable:Searchable _ "not in"i _ "(" _ list:InComparisonValuesList _ "," ? _ ")" {
        return new NotInComparison($searchable, new InValueWrapper($list));
    }

InComparison
    = searchable:Searchable _ "in"i _ "(" _ list:InComparisonValuesList _ "," ? _ ")" {
        return new InComparison($searchable, new InValueWrapper($list));
    }

InComparisonValuesList
    = first_value_wrapper:ListValue value_wrappers:(InComparisonValue *) {
        array_unshift($value_wrappers, $first_value_wrapper);
        return $value_wrappers;
    }

InComparisonValue
    = _ "," _ value_wrapper:ListValue { return $value_wrapper; }

Field
    = name:$[a-zA-Z0-9_\-]+ {
        return new Field($name);
    }

Metadata
    = '@' name:$[a-zA-Z0-9_]+ {
        return new Metadata($name);
    }

Searchable
    = Field / Metadata

SimpleExpr
    = l:Literal { return $l; }

Literal
    = String / Float / Integer / CurrentDateTime / CurrentUser / StatusOpen

ListValue
    = l:Literal { return $l; }

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
        return new CurrentDateTimeValueWrapper($period["sign"] ?? null, $period["duration"] ?? null);
    }

CurrentUser
    = "myself"i _ "(" _ ")" {
        return new CurrentUserValueWrapper(\UserManager::instance());
    }

StatusOpen
    = "open"i _ "(" _ ")" {
        return new StatusOpenValueWrapper();
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
