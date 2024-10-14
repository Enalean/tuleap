/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
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

query
    = default_query
        / expert_query

default_query
    = condition:or_expression {
        return new Query([], null, $condition, null);
    }

expert_query
    = select:select _ from:from _ "where"i _ condition:or_expression _ order:order_by? {
        return new Query($select, $from, $condition, $order);
    }

select
    = "select"i _ first:Selectable _ list:(SelectableList *) {
        array_unshift($list, $first);
        return $list;
    }

SelectableList
    = _ "," _ selectable:Selectable { return $selectable; }

Selectable = Field / Metadata

from
    = "from"i _ left:from_condition _ right:from_right? {
        return new From($left, $right);
    }

from_right
    = "and"i _ right:from_condition { return $right; }

from_condition
    = from_project / from_tracker

from_project
    = target:from_project_target _ condition:from_project_condition {
        return new FromProject($target, $condition);
    }

from_project_target
    = "@project.name" / "@project.category" / "@project"

from_project_condition
    = from_project_equal / from_project_in

from_project_equal
    = "=" _ value:String {
        return new FromProjectEqual($value->getValue());
    }

from_project_in
    = "in"i _ "(" _ first:String _ list:(StringList *) _ ")" {
        array_unshift($list, $first->getValue());
        return new FromProjectIn($list);
    }

from_tracker
    = "@tracker.name" _ condition:from_tracker_condition {
        return new FromTracker("@tracker.name", $condition);
    }

from_tracker_condition
    = from_tracker_equal / from_tracker_in

from_tracker_equal
    = "=" _ value:String {
        return new FromTrackerEqual($value->getValue());
    }

from_tracker_in
    = "in"i _ "(" _ first:String _ list:(StringList *) _ ")" {
        array_unshift($list, $first->getValue());
        return new FromTrackerIn($list);
    }

order_by
    = "order"i _ "by"i _ select:Selectable _ direction:order_direction {
        return new OrderBy($select, $direction);
    }

order_direction
    = ("ASCENDING"i / "ASC"i) { return OrderByDirection::ASCENDING; }
    / ("DESCENDING"i / "DESC"i) { return OrderByDirection::DESCENDING; }

StringList
    = _ "," _ value:String { return $value->getValue(); }

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
        / RelationshipCondition

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

RelationshipCondition
    = IsCoveredBy / IsNotCoveredBy
        / IsCovered / IsNotCovered
        / IsCovering / IsNotCovering
        / WithParent / WithoutParent
        / WithChildren / WithoutChildren
        / WithTypedReverseLink / WithoutTypedReverseLink
        / WithTypedForwardLink / WithoutTypedForwardLink
        / WithReverseLink / WithoutReverseLink
        / WithForwardLink / WithoutForwardLink

IsCovered = "is"i _ "covered"i {
        return new WithForwardLink(null, '_covered_by');
    }

IsCoveredBy = "is"i _ "covered"i _ "by"i _ condition:LinkArtifactCondition {
        return new WithForwardLink($condition, '_covered_by');
    }

IsCovering = "is"i _ "covering"i _ condition:LinkArtifactCondition? {
        return new WithReverseLink($condition, '_covered_by');
    }

IsNotCovered = "is"i _ "not"i _ "covered"i {
        return new WithoutForwardLink(null, '_covered_by');
    }

IsNotCoveredBy = "is"i _ "not"i _ "covered"i _ "by"i _ condition:LinkArtifactCondition {
        return new WithoutForwardLink($condition, '_covered_by');
    }

IsNotCovering = "is"i _ "not"i _ "covering"i _ condition:LinkArtifactCondition? {
        return new WithoutReverseLink($condition, '_covered_by');
    }

WithParent = "with"i _ "parent"i _ condition:LinkCondition? {
        return new WithReverseLink($condition, '_is_child');
    }

WithReverseLink = IsLinkedFrom _ condition:LinkCondition? {
        return new WithReverseLink($condition, null);
    }

WithTypedReverseLink = IsLinkedFrom _ condition:LinkCondition? _ WithType _ type:String {
        return new WithReverseLink($condition, (string) $type->getValue());
    }

WithoutParent = "without"i _ "parent"i _ condition:LinkCondition? {
        return new WithoutReverseLink($condition, '_is_child');
    }

WithoutReverseLink = IsNotLinkedFrom _ condition:LinkCondition? {
        return new WithoutReverseLink($condition, null);
    }

WithoutTypedReverseLink = IsNotLinkedFrom _ condition:LinkCondition? _ WithType _ type:String {
        return new WithoutReverseLink($condition, (string) $type->getValue());
    }

LinkCondition
    = LinkArtifactCondition
        / LinkTrackerCondition

LinkArtifactCondition = "artifact"i _ "=" _ id:$[0-9]+ {
        return new LinkArtifactCondition((int) $id);
    }

LinkTrackerCondition
    = LinkTrackerEqualCondition
        / LinkTrackerNotEqualCondition

LinkTrackerEqualCondition = "tracker"i _ "=" _ tracker:String {
        return new LinkTrackerEqualCondition((string) $tracker->getValue());
    }

LinkTrackerNotEqualCondition = "tracker"i _ "!=" _ tracker:String {
        return new LinkTrackerNotEqualCondition((string) $tracker->getValue());
    }

WithChildren = "with"i _ Children _ condition:LinkCondition? {
        return new WithForwardLink($condition, '_is_child');
    }

WithForwardLink = IsLinkedTo _ condition:LinkCondition? {
        return new WithForwardLink($condition, null);
    }

WithTypedForwardLink = IsLinkedTo _ condition:LinkCondition? _ WithType _ type:String {
        return new WithForwardLink($condition, (string) $type->getValue());
    }

WithoutChildren = "without"i _ Children _ condition:LinkCondition? {
        return new WithoutForwardLink($condition, '_is_child');
    }

WithoutForwardLink = IsNotLinkedTo _ condition:LinkCondition? {
        return new WithoutForwardLink($condition, null);
    }

WithoutTypedForwardLink = IsNotLinkedTo _ condition:LinkCondition? _ WithType _ type:String {
        return new WithoutForwardLink($condition, (string) $type->getValue());
    }

Children = "children"i / "child"i
IsLinkedFrom = "is"i _ "linked"i _ "from"i
IsNotLinkedFrom = "is"i _ "not"i _ "linked"i _ "from"i
IsLinkedTo = "is"i _ "linked"i _ "to"i
IsNotLinkedTo = "is"i _ "not"i _ "linked"i _ "to"i
WithType = "with"i _ "type"i

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
    = '@' name:$[a-zA-Z0-9_\.]+ {
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
    = [ \t\n\r\u00a0]*
