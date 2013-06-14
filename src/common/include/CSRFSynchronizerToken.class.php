<?php
/*
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once('common/include/Codendi_HTMLPurifier.class.php');

/**
 * Class CSRFSynchronizerToken
 * 
 * This class deals with a CSRF countermeasure. Usage:
 *
 * In a html form, add a token:
 * <pre>
 *   echo '<form ...>';
 *   $token = new CSRFSynchronizerToken('/path/of/my/page');
 *   echo $token->fetchHTMLInput();
 * </pre>
 *
 * Then in the target page, which deals with sensitive data, check that the token is valid
 * <pre>
 *   $request = HTTPRequest::instance();
 *   $token = new CSRFSynchronizerToken('/path/of/my/page');
 *   $token->check();
 *   // ... continue in a safe way
 * </pre>
 *
 * That's it!
 */
class CSRFSynchronizerToken {
    
    const PREF_NAME_PREFIX = 'synchronizer_token_';
    
    const DEFAULT_TOKEN_NAME = 'challenge';
    
    /**
     * @var string a pseudorandom generated token
     */
    protected $token;
    
    /**
     * @var string the url the token is refering to
     */
    protected $url;
    
    /**
     * @var string the name of the token (to retrieve in the request)
     */
    protected $token_name;
    
    /**
     * Generate a token for the $url
     *
     * Generate a challenge token to prevent CSRF attacks.
     *
     * The pseudorandom generated token must be put in an hidden field in a form.
     * If the form as method=POST it is better. Use this for sensitive server-side operations (admin, preferences, ...)
     *
     * /!\ using this method for anonymous user is just silly (although it doesn't raise any error)
     *
     * @see https://www.owasp.org/index.php/Cross-Site_Request_Forgery_%28CSRF%29
     *
     * @param string $url         The url of the page (take it as a 'salt'). The token is uniq for (url, user session)
     * @param string $token_name  The name of the token in the request. default is 'challenge'
     *
     * @return void
     */
    public function __construct($url, $token_name = self::DEFAULT_TOKEN_NAME) {
        $this->url        = $url;
        $this->token_name = $token_name;
        
        //generation of the token
        $salt = $this->url . $this->getUser()->getSessionHash();
        $this->token = $this->getUser()->getPreference(self::PREF_NAME_PREFIX . $salt);
        if (!$this->token) {
            $this->token = md5(uniqid(rand(), true) . $salt);
            $this->getUser()->setPreference(self::PREF_NAME_PREFIX . $salt, $this->token);
        }
    }
    
    /**
     * Check that a challenge token is valid.
     * @see Constructor
     *
     * @param string $token The token to check against what is stored in the user session
     *
     * @return bool true if token valid, false otherwise
     */
    public function isValid($token) {
        return $this->token === $token;
    }
    
    /**
     * Redirect to somewhere else if the token in request is not valid
     *
     * @param Codendi_Request $request     The request object, if null then use HTTPRequest
     * @param string          $redirect_to Url to be redirected to in case of error. if null then use $url instead. Default is null
     *
     * @return void
     */
    public function check($redirect_to = null, $request = null) {
        if (!$request) {
            $request = HTTPRequest::instance();
        }
        if (!$request->existAndNonEmpty($this->token_name) || !$this->isValid($request->get($this->token_name))) {
           $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'error_synchronizertoken'));
           $GLOBALS['Response']->redirect($redirect_to === null ? $this->url : $redirect_to);
       }
    }
    
    /**
     * Fetch HTML input (hidden) to be included in a form to protect the user against CSRF
     *
     * @return string html
     */
    public function fetchHTMLInput() {
        $hp = Codendi_HTMLPurifier::instance();
        return '<input type="hidden" name="'. $hp->purify($this->token_name, CODENDI_PURIFIER_CONVERT_HTML) .'" value="'. $hp->purify($this->token, CODENDI_PURIFIER_CONVERT_HTML) .'" />';
    }
    
    /**
     * @return string The token
     */
    public function getToken() {
        return $this->token;
    }
    
    /**
     * @return string The token name
     */
    public function getTokenName() {
        return $this->token_name;
    }
    
    /**
     * @return PFUser
     */
    protected function getUser() {
        return UserManager::instance()->getCurrentUser();
    }
}
?>
