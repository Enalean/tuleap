<?php
/*
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

/**
 *
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
class CSRFSynchronizerToken implements \Tuleap\Request\CSRFSynchronizerTokenInterface // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const DEFAULT_TOKEN_NAME    = 'challenge';
    public const STORAGE_PREFIX        = 'synchronizer_token';
    public const MAX_TOKEN_PER_STORAGE = 4096;

    /**
     * @var string a pseudorandom generated token
     */
    private $token;

    /**
     * @var string the url the token is referring to
     */
    private $url;

    /**
     * @var string the name of the token (to retrieve in the request)
     */
    private $token_name;

    /**
     * @var array Storage used to keep CSRF tokens
     */
    private $storage;

    /**
     * Generate a token for the $url
     *
     * Generate a challenge token to prevent CSRF attacks.
     *
     * The pseudorandom generated token must be put in an hidden field in a form.
     * If the form as method=POST it is better. Use this for operations changing server state.
     *
     * @see https://www.owasp.org/index.php/Cross-Site_Request_Forgery_%28CSRF%29
     *
     * @param string $url         The url of the page. The token is uniq for (url, user session)
     * @param string $token_name  The name of the token in the request. default is 'challenge'
     * @param array|null $storage Storage used to keep CSRF tokens, $_SESSION is used by default
     */
    public function __construct($url, $token_name = self::DEFAULT_TOKEN_NAME, &$storage = null)
    {
        $this->url        = $url;
        $this->token_name = $token_name;

        if ($storage === null) {
            $this->storage =& $_SESSION;
        } else {
            $this->storage =& $storage;
        }

        if (isset($this->storage[self::STORAGE_PREFIX][$this->url])) {
            $this->token = $this->storage[self::STORAGE_PREFIX][$this->url]['token'];
        }
        if (! $this->token) {
            $this->generateToken();
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
    public function isValid($token): bool
    {
        if (! is_string($token)) {
            return false;
        }

        return hash_equals($this->token, $token);
    }

    /**
     * Redirect to somewhere else if the token in request is not valid
     *
     * @param Codendi_Request $request     The request object, if null then use HTTPRequest
     * @param string          $redirect_to Url to be redirected to in case of error. if null then use $url instead. Default is null
     *
     */
    public function check(?string $redirect_to = null, ?Codendi_Request $request = null): void
    {
        if (! $request) {
            $request = HTTPRequest::instance();
        }
        if (! $this->isValid($request->get($this->token_name))) {
            $GLOBALS['Response']->addFeedback(
                'error',
                $GLOBALS['Language']->getText('global', 'error_synchronizertoken')
            );
            $GLOBALS['Response']->redirect($redirect_to === null ? $this->url : $redirect_to);
        }
    }

    /**
     * Fetch HTML input (hidden) to be included in a form to protect the user against CSRF
     *
     * @return string html
     */
    public function fetchHTMLInput()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(
            ForgeConfig::get('codendi_dir') . '/src/templates/common/'
        );

        return $renderer->renderToString('csrf_token_input', $this);
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getTokenName(): string
    {
        return $this->token_name;
    }

    private function generateToken()
    {
        $random_number_generator                         = new RandomNumberGenerator();
        $this->token                                     = $random_number_generator->getNumber();
        $this->storage[self::STORAGE_PREFIX][$this->url] = [
            'token'   => $this->token,
            'created' => time(),
        ];
        $this->recycleTokens();
    }

    /**
     * We enforce a limit on the number of CSRF tokens stored in the storage
     * by removing the oldest ones first.
     * We do this to avoid an unnecessary bloating of the storage capacity.
     */
    private function recycleTokens()
    {
        if (self::MAX_TOKEN_PER_STORAGE > count($this->storage[self::STORAGE_PREFIX])) {
            return;
        }

        uasort(
            $this->storage,
            function ($token_1, $token_2) {
                return $token_1['created'] - $token_2['created'];
            }
        );

        while (count($this->storage[self::STORAGE_PREFIX]) > self::MAX_TOKEN_PER_STORAGE) {
            array_shift($this->storage[self::STORAGE_PREFIX]);
        }
    }
}
