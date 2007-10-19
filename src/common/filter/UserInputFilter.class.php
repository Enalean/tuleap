<?php
/**
 * @author Manuel VACELET <manuel.vacelet@st.com>
 * @copyright Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package CodeX
 */

/**
 * User input validation and sanitization.
 *
 *
 * Motto: filter input / escape output
 *
 * <b>About User input validation</b><br />
 * Goal: verify that user input match application expectations.
 * The theorical approach of this is to check that user input match the
 * expectations but not to modify those inputs.
 *
 * However due to some PHP limitations (on intergers for instance) and to take
 * legacy into account (usage of register_globals, or direct access to
 * $_REQUEST, ...) and to prevent validation code error, it's better to
 * sanitize submitted inputs.
 *
 * UserInputFilter framework propose to validate inputs with common rules
 * (integer, float, etc) and is extendable with custom rules. For instance for
 * docman, we can add a rule that ensure that a property label is 'field_XXX'
 * where XXX is an integer.
 *
 * We can also use this framework to expect more complex things (the submitted
 * group_id exist, is public, is accessible by current user, etc).
 *
 * What does that mean concretly:<br />
 * The UserInputFilter class will provide an method to validate the
 * expectations. This method will return a boolean value (match the expactation
 * or not). This method will also sanitize the value.
 *
 * <b>About the impact on HTTPRequest</b><br />
 * For this to be efficient, it could be a good idea to modify HTTPRequest
 * class to add the notion of 'tained' parameter.
 *
 * For instance, $request->get('key') could return a value only if 'key' was
 * validated. Obvioulsy, it will be always possible to get the original
 * parameter with $request->getTained('key') that would make the usage of
 * tained data much more obvious.
 *
 * However is a very first approach, $request->get() will still return the
 * tained value if nothing is found in clean array but will raise a notice.
 *
 * <b>Phpdoc about this class</b><br />
 * @uses FilterRule
 * @uses HTTPRequest
 * @example filter/onerule.php Filtering with one smart rule.
 * @example filter/multiplerules.php Filtering with a lot of stupid rules.
 * @example filter/filterrequest.php Basic usage of this class.
 * @package CodeX
 */
class UserInputFilter {
    /**
     * Constructor
     */
    function UserInputFilter() {

    }

    /**
     * Add a rule for given argument
     *
     * A rule can be applied on a http argument ($group_id, $user_password,
     * etc) but can also applies on the request itself (check if the method
     * used is 'post', etc).
     * @todo Define how to apply rules on request.
     *
     * @param string     $argName Name of parameter in HTTP request.
     * @param FilterRule $rule    The rule to apply on the given argument.
     */
    function addRule($argName, $rule) {
    }

    /**
     * Apply all rules on HTTPRequest instance.
     *
     * Use HTTPRequest singleton
     *
     * @see validateRequest
     */
    function validate() {
    }

    /**
     * Apply all validation rules on given request object.
     *
     * @param HTTPRequest $request The request object to validate.
     */
    function validateRequest(&$request) {
    }

    /**
     * Return all errors if errors were found during validation false otherwise.
     *
     * @return Iterator|boolean Each element is an array '(argName, rule)'.
     */
    function getErrors() {
    }

    /**
     * Return is errors were found during validation.
     *
     * @return boolean
     */
    function isError() {
    }

    /**
     * Return error for given parameter, false otherwise.
     *
     * @param string $argName Request parameter name.
     * @return boolean
     */
    function getError($argName) {
    }
}

?>
