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

use Tuleap\CSRF\CSRFSessionKeyStorage;
use Tuleap\CSRF\CSRFSigningKeyStorage;

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
    public const DEFAULT_TOKEN_NAME = 'challenge';

    /**
     * @psalm-var SensitiveParameterValue<non-empty-string>|null
     */
    private ?SensitiveParameterValue $token = null;

    private readonly CSRFSigningKeyStorage $signing_key_storage;
    private readonly CSRFSessionKeyStorage $session_key_storage;

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
     */
    public function __construct(
        public readonly string $url,
        private readonly string $token_name = self::DEFAULT_TOKEN_NAME,
        ?\Tuleap\CSRF\CSRFSigningKeyStorage $signing_key_storage = null,
        ?\Tuleap\CSRF\CSRFSessionKeyStorage $session_key_storage = null,
    ) {
        if ($signing_key_storage === null) {
            $signing_key_storage = new \Tuleap\CSRF\CSRFSigningKeyDBStorage(new \Tuleap\Config\ConfigDao());
        }
        $this->signing_key_storage = $signing_key_storage;
        if ($session_key_storage === null) {
            $session_key_storage = new \Tuleap\CSRF\CSRFSessionKeyCookieStorage(new \Tuleap\CookieManager());
        }
        $this->session_key_storage = $session_key_storage;
    }

    /**
     * Check that a challenge token is valid.
     * @see Constructor
     *
     * @param string $token The token to check against what is stored in the user session
     *
     * @return bool true if token valid, false otherwise
     */
    #[\Override]
    public function isValid($token): bool
    {
        if (! is_string($token)) {
            return false;
        }

        return \hash_equals($this->getToken(), $token);
    }

    /**
     * Redirect to somewhere else if the token in request is not valid
     *
     * @param Codendi_Request $request     The request object, if null then use HTTPRequest
     * @param string          $redirect_to Url to be redirected to in case of error. if null then use $url instead. Default is null
     *
     */
    #[\Override]
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
            __DIR__ . '/../../templates/common/'
        );

        return $renderer->renderToString('csrf_token_input', $this);
    }

    #[\Override]
    public function getToken(): string
    {
        if ($this->token !== null) {
            return $this->token->getValue();
        }
        $this->token = new \SensitiveParameterValue(
            \hash_hmac(
                'sha256',
                $this->session_key_storage->getSessionKey() . ':' . $this->url,
                $this->signing_key_storage->getSigningKey()->getString()
            )
        );
        return $this->token->getValue();
    }

    #[\Override]
    public function getTokenName(): string
    {
        return $this->token_name;
    }
}
