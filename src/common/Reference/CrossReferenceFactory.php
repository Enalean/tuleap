<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

use Tuleap\Reference\CheckCrossReferenceValidityEvent;
use Tuleap\Reference\CrossReference;
use Tuleap\Reference\Presenters\CrossReferenceFieldPresenter;
use Tuleap\Reference\Presenters\CrossReferenceByNaturePresenterBuilder;
use Tuleap\Reference\Presenters\CrossReferenceLinkListPresenterBuilder;
use Tuleap\Reference\Presenters\CrossReferenceLinkPresenterCollectionBuilder;
use Tuleap\Reference\CrossReferenceByNatureCollection;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class CrossReferenceFactory
{
    public $entity_id;
    public $entity_gid;
    public $entity_type;

    /**
     * array of references {Object CrossReference}
     * to the current CrossReferenceFactory
     * In other words, Items in this array have made references to the current Item
     * @var array
     */
    public $source_refs_datas = [];

    /**
     * array of references {Object CrossReference} made by the current CrossReferenceFactory
     * In other words, Items in this array are referenced by the current Item
     * @var array
     */
    public $target_refs_datas = [];

    /**
     * Constructor
     * Note that we need a valid reference parameter
     */
    public function __construct($entity_id, $entity_type, $entity_group_id)
    {
        $this->entity_id   = $entity_id;
        $this->entity_type = $entity_type;
        $this->entity_gid  = $entity_group_id;
    }

    /**
     * Fill the arrays $this->source_refs_datas and $this->target_refs_datas
     * for the current CrossReferenceFactory
     */
    public function fetchDatas()
    {
        $sql = "SELECT *
                FROM cross_references
                WHERE  (target_gid=" . db_ei($this->entity_gid) . " AND target_id='" . db_es($this->entity_id) . "' AND target_type='" . db_es($this->entity_type) . "' )
                     OR (source_gid=" . db_ei($this->entity_gid) . " AND source_id='" . db_es($this->entity_id) . "' AND source_type='" . db_es($this->entity_type) . "' )";

        $res = db_query($sql);
        if ($res && db_numrows($res) > 0) {
            $this->source_refs_datas = [];
            $this->target_refs_datas = [];

            while ($field_array = db_fetch_array($res)) {
                $target_id   = $field_array['target_id'];
                $target_gid  = $field_array['target_gid'];
                $target_type = $field_array['target_type'];
                $target_key  = $field_array['target_keyword'];

                $source_id   = $field_array['source_id'];
                $source_gid  = $field_array['source_gid'];
                $source_type = $field_array['source_type'];
                $source_key  = $field_array['source_keyword'];

                $user_id    = $field_array['user_id'];
                $created_at = $field_array['created_at'];

                if (
                    ($target_id == $this->entity_id) &&
                     ($target_gid == $this->entity_gid) &&
                     ($target_type == $this->entity_type)
                ) {
                    $this->source_refs_datas[] = new CrossReference($source_id, $source_gid, $source_type, $source_key, $target_id, $target_gid, $target_type, $target_key, $user_id);
                }
                if (
                    ($source_id == $this->entity_id) &&
                     ($source_gid == $this->entity_gid) &&
                     ($source_type == $this->entity_type)
                ) {
                    $this->target_refs_datas[] = new CrossReference($source_id, $source_gid, $source_type, $source_key, $target_id, $target_gid, $target_type, $target_key, $user_id);
                }
            }
        }

        $this->getValidCrossReferences();
    }

    public function getNbReferences()
    {
        return (count($this->target_refs_datas) + count($this->source_refs_datas));
    }

    /** Accessors */
    public function getRefSource()
    {
        return $this->source_refs_datas;
    }

    public function getRefTarget()
    {
        return $this->target_refs_datas;
    }

    /**Display function */
    public function DisplayCrossRefs() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        echo $this->getHTMLDisplayCrossRefs();
    }

    /**
     * Returns the cross references grouped by 'source', 'target' and
     * 'both' types with their URLs and tags.
     * @return array The formatted cross references
     */
    public function getFormattedCrossReferences()
    {
        $crossRefArray = $this->getCrossReferences();
        $refs          = [];
        foreach ($crossRefArray as $nature => $refArraySourceTarget) {
            foreach (['both', 'target', 'source'] as $key) {
                if (array_key_exists($key, $refArraySourceTarget)) {
                    foreach ($refArraySourceTarget[$key] as $currRef) {
                        if ($key === 'source') {
                            $ref = $currRef->getRefSourceKey() . " #" . $currRef->getRefSourceId();
                            $url = $currRef->getRefSourceUrl();
                        } else {
                            $ref = $currRef->getRefTargetKey() . " #" . $currRef->getRefTargetId();
                            $url = $currRef->getRefTargetUrl();
                        }
                        $refs[$key][] = ['ref' => $ref, 'url' => $url];
                    }
                }
            }
        }
        return $refs;
    }

    public function getHTMLDisplayCrossRefs($with_links = true, $condensed = false, $isBrowser = true): string
    {
        $user           = $this->getCurrentUser();
        $can_delete     = $isBrowser && ($user->isSuperUser() || $user->isMember($this->entity_gid, 'A'));
        $renderer       = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../templates/common');
        $display_params = $with_links && $can_delete && ! $condensed;

        $cross_ref_by_nature_presenter_builder = new CrossReferenceByNaturePresenterBuilder(
            new CrossReferenceLinkListPresenterBuilder(),
            new CrossReferenceLinkPresenterCollectionBuilder()
        );

        $cross_ref_by_nature_collection = $this->getCrossReferencesByNatureCollection();

        $cross_refs_by_nature_presenter_collection = [];

        foreach ($cross_ref_by_nature_collection->getAll() as $cross_reference_collection) {
            $cross_ref_by_nature_presenter = $cross_ref_by_nature_presenter_builder->build(
                $cross_reference_collection,
                $display_params
            );
            if ($cross_ref_by_nature_presenter) {
                $cross_refs_by_nature_presenter_collection[] = $cross_ref_by_nature_presenter;
            }
        }

        return $renderer->renderToString('cross_reference', new CrossReferenceFieldPresenter(
            $condensed,
            $with_links,
            $display_params,
            $cross_refs_by_nature_presenter_collection
        ));
    }

    public function getHTMLCrossRefsForMail()
    {
        $html                        = '';
        $cross_refs                  = $this->getCrossReferences();
        $reference_manager           = ReferenceManager::instance();
        $available_nature_collection = $reference_manager->getAvailableNatures();

        foreach ($cross_refs as $nature => $references_by_destination) {
            $html .= '<div>';
            $refs  = [];
            foreach ($references_by_destination as $key => $references) {
                foreach ($references as $reference) {
                    if ($key === 'source') {
                        $ref = $reference->getRefSourceKey() . " #" . $reference->getRefSourceId();
                        $url = $reference->getRefSourceUrl();
                    } else {
                        $ref = $reference->getRefTargetKey() . " #" . $reference->getRefTargetId();
                        $url = $reference->getRefTargetUrl();
                    }
                    $available_nature = $available_nature_collection->getNatureFromIdentifier($nature);
                    if (! $available_nature) {
                        continue;
                    }
                    $title  = $available_nature->label;
                    $refs[] = '<a title="' . $title . '" href="' . $url . '">' . $ref . '</a>';
                }
            }
            $html .= implode(', ', $refs);
            $html .= '</div>';
        }

        return $html;
    }

    public function getHTMLCrossRefsForCSVExport()
    {
        $html       = '';
        $cross_refs = $this->getCrossReferences();

        foreach ($cross_refs as $nature => $references_by_destination) {
            $html .= '';
            $refs  = [];
            foreach ($references_by_destination as $key => $references) {
                foreach ($references as $reference) {
                    if ($key === 'source') {
                        $ref = $reference->getRefSourceKey() . " #" . $reference->getRefSourceId();
                    } else {
                        $ref = $reference->getRefTargetKey() . " #" . $reference->getRefTargetId();
                    }
                    $refs[] =  $ref;
                }
            }
            $html .= implode(', ', $refs);
        }

        return $html;
    }

    public function getCrossReferencesByNatureCollection(): CrossReferenceByNatureCollection
    {
        return new CrossReferenceByNatureCollection(
            $this->getCrossReferences(),
            ReferenceManager::instance()->getAvailableNatures()
        );
    }

    /**
     * This function retrieves all cross references for given entity id, a group id, and a type
     * @return array cross references data
     */
    protected function getCrossReferences()
    {
        $crossRefArray = [];

        // Walk the target ref array in order to fill the crossRefArray array
        for ($i = 0, $nb_target_refs = count($this->target_refs_datas); $i < $nb_target_refs; $i++) {
            $is_cross = false;
            // Check if the ref is cross referenced (means referenced by a source)
            $j               = 0;
            $source_position = 0;
            foreach ($this->source_refs_datas as $source_refs) {
                if ($this->target_refs_datas[$i]->isCrossReferenceWith($source_refs)) {
                    $is_cross        = true;
                    $source_position = $j;
                }
                $j++;
            }
            if ($is_cross) {
                if (
                    $this->entity_id == $this->target_refs_datas[$i]->getRefSourceId() &&
                    $this->entity_gid == $this->target_refs_datas[$i]->getRefSourceGid() &&
                    $this->entity_type == $this->target_refs_datas[$i]->getRefSourceType()
                ) {
                    // Add the cross reference into the "both" (target and source) array
                    $crossRefArray[$this->source_refs_datas[$source_position]->getInsertSourceType()]['both'][] = $this->target_refs_datas[$i];
                } else {
                    $crossRefArray[$this->target_refs_datas[$i]->getInsertSourceType()]['both'][] = $this->target_refs_datas[$i];
                }
            } else {
                // Add the cross reference into the "target" array
                $crossRefArray[$this->target_refs_datas[$i]->getInsertTargetType()]['target'][] = $this->target_refs_datas[$i];
            }
        }

        // Walk the source ref array in order to fill the crossRefArray array
        for ($i = 0, $nb_source_refs = count($this->source_refs_datas); $i < $nb_source_refs; $i++) {
            $is_cross = false;
            // Check if the ref is cross referenced (means referenced by a target)
            foreach ($this->target_refs_datas as $target_refs) {
                if ($this->source_refs_datas[$i]->isCrossReferenceWith($target_refs)) {
                    $is_cross = true;
                }
            }
            if ($is_cross) {
                // do nothing, has already been added during target walk
            } else {
                $crossRefArray[$this->source_refs_datas[$i]->getInsertSourceType()]['source'][] = $this->source_refs_datas[$i];
            }
        }

        // Sort array by Nature
        ksort($crossRefArray);
        return $crossRefArray;
    }

    private function getValidCrossReferences(): void
    {
        $event_manager = EventManager::instance();

        $event = new CheckCrossReferenceValidityEvent($this->target_refs_datas, $this->getCurrentUser());
        $event_manager->processEvent($event);
        $this->target_refs_datas = array_values($event->getCrossReferences());

        $event = new CheckCrossReferenceValidityEvent($this->source_refs_datas, $this->getCurrentUser());
        $event_manager->processEvent($event);
        $this->source_refs_datas = array_values($event->getCrossReferences());
    }

    private function getCurrentUser(): PFUser
    {
        return UserManager::instance()->getCurrentUser();
    }
}
