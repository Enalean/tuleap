<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Bugzilla\Administration;

use CSRFSynchronizerToken;
use Feedback;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Bugzilla\Reference\KeywordIsAlreadyUsedException;
use Tuleap\Bugzilla\Reference\KeywordIsInvalidException;
use Tuleap\Bugzilla\Reference\ReferenceDestructor;
use Tuleap\Bugzilla\Reference\ReferenceRetriever;
use Tuleap\Bugzilla\Reference\ReferenceSaver;
use Tuleap\Bugzilla\Reference\RequiredFieldEmptyException;
use Tuleap\Bugzilla\Reference\RESTURLIsInvalidException;
use Tuleap\Bugzilla\Reference\ServerIsInvalidException;
use Tuleap\Bugzilla\Reference\UnableToCreateSystemReferenceException;

class Controller
{
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    /**
     * @var AdminPageRenderer
     */
    private $renderer;
    /**
     * @var ReferenceSaver
     */
    private $reference_saver;
    /**
     * @var ReferenceRetriever
     */
    private $reference_retriever;
    /**
     * @var ReferenceDestructor
     */
    private $reference_destructor;

    public function __construct(
        AdminPageRenderer $renderer,
        ReferenceSaver $reference_saver,
        ReferenceRetriever $reference_retriever,
        ReferenceDestructor $reference_destructor
    ) {
        $this->renderer             = $renderer;
        $this->csrf_token           = new CSRFSynchronizerToken(BUGZILLA_REFERENCE_BASE_URL . '/admin/');
        $this->reference_saver      = $reference_saver;
        $this->reference_retriever  = $reference_retriever;
        $this->reference_destructor = $reference_destructor;
    }

    public function display()
    {
        $references           = $this->reference_retriever->getAllReferences();
        $references_presenter = $this->getPresenters($references);
        $presenter            = new Presenter($references_presenter, $this->csrf_token);
        $this->renderer->renderAPresenter(
            dgettext('tuleap-bugzilla_reference', 'Bugzilla configuration'),
            BUGZILLA_REFERENCE_TEMPLATE_DIR,
            'reference-list',
            $presenter
        );
    }

    private function getPresenters(array $references)
    {
        $presenters = array();

        foreach ($references as $reference) {
            $presenters[] = new ReferencePresenter(
                $reference->getId(),
                $reference->getKeyword(),
                $reference->getServer(),
                $reference->getUsername(),
                $reference->getAPIKey(),
                $reference->getAreFollowupPrivate(),
                $reference->getRestUrl(),
                $reference->hasApiKeyAlwaysBeenEncrypted()
            );
        }

        return $presenters;
    }

    public function addReference(\Codendi_Request $request)
    {
        $this->csrf_token->check();

        try {
            $this->reference_saver->save($request);
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-bugzilla_reference', 'Reference has been successfully added')
            );
        } catch (RequiredFieldEmptyException $ex) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-bugzilla_reference', 'Missing fields for creating reference')
            );
        } catch (KeywordIsAlreadyUsedException $ex) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(
                    dgettext('tuleap-bugzilla_reference', 'The reference "%s" is already used'),
                    $request->get('keyword')
                )
            );
        } catch (KeywordIsInvalidException $ex) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-bugzilla_reference', 'Keyword is invalid')
            );
        } catch (ServerIsInvalidException $ex) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-bugzilla_reference', 'Server is invalid')
            );
        } catch (RESTURLIsInvalidException $ex) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-bugzilla_reference', 'REST URL is invalid')
            );
        } catch (UnableToCreateSystemReferenceException $ex) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-bugzilla_reference', 'Unable to create corresponding system reference')
            );
        }

        $GLOBALS['Response']->redirect(BUGZILLA_REFERENCE_BASE_URL . '/admin/');
    }

    public function editReference(\Codendi_Request $request)
    {
        $this->csrf_token->check();

        try {
            $this->reference_saver->edit($request);
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-bugzilla_reference', 'Reference has been successfully updated')
            );
        } catch (RequiredFieldEmptyException $ex) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-bugzilla_reference', 'Missing fields for updating reference')
            );
        } catch (ServerIsInvalidException $ex) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-bugzilla_reference', 'Server is invalid')
            );
        } catch (RESTURLIsInvalidException $ex) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-bugzilla_reference', 'REST URL is invalid')
            );
        }

        $GLOBALS['Response']->redirect(BUGZILLA_REFERENCE_BASE_URL . '/admin/');
    }

    public function deleteReference(\Codendi_Request $request)
    {
        $this->csrf_token->check();

        if ($this->reference_destructor->delete($request)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-bugzilla_reference', 'Reference has been successfully removed')
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-bugzilla_reference', 'An error occured while removing reference')
            );
        }

        $GLOBALS['Response']->redirect(BUGZILLA_REFERENCE_BASE_URL . '/admin/');
    }
}
