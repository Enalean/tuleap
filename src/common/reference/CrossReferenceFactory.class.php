<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
 *
 *
 *
 * Cross Reference Factory class
 */

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
        $this->entity_id = $entity_id;
        $this->entity_type = $entity_type;
        $this->entity_gid = $entity_group_id;
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
            $this->source_refs_datas = array();
            $this->target_refs_datas = array();

            while ($field_array = db_fetch_array($res)) {
                $target_id = $field_array['target_id'];
                $target_gid = $field_array['target_gid'];
                $target_type = $field_array['target_type'];
                $target_key = $field_array['target_keyword'];

                $source_id = $field_array['source_id'];
                $source_gid = $field_array['source_gid'];
                $source_type = $field_array['source_type'];
                $source_key = $field_array['source_keyword'];

                $user_id = $field_array['user_id'];
                $created_at = $field_array['created_at'];

                if (($target_id == $this->entity_id) &&
                     ($target_gid == $this->entity_gid) &&
                     ($target_type == $this->entity_type)
                    ) {
                    $this->source_refs_datas[] = new CrossReference($source_id, $source_gid, $source_type, $source_key, $target_id, $target_gid, $target_type, $target_key, $user_id);
                }
                if (($source_id == $this->entity_id) &&
                     ($source_gid == $this->entity_gid) &&
                     ($source_type == $this->entity_type)
                    ) {
                    $this->target_refs_datas[] = new CrossReference($source_id, $source_gid, $source_type, $source_key, $target_id, $target_gid, $target_type, $target_key, $user_id);
                }
            }
        }
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
    public function DisplayCrossRefs()
    {
        echo $this->getHTMLDisplayCrossRefs();
    }

    public function getParams($currRef)
    {
        $params = "?target_id=" . $currRef->getRefTargetId();
        $params .= "&target_gid=" . $currRef->getRefTargetGid();
        $params .= "&target_type=" . $currRef->getRefTargetType();
        $params .= "&target_key=" . $currRef->getRefTargetKey();
        $params .= "&source_id=" . $currRef->getRefSourceId();
        $params .= "&source_gid=" . $currRef->getRefSourceGid();
        $params .= "&source_type=" . $currRef->getRefSourceType();
        $params .= "&source_key=" . $currRef->getRefSourceKey();
        return $params;
    }

    /**
     * Returns the cross references grouped by 'source', 'target' and
     * 'both' types with their URLs and tags.
     * @return array The formatted cross references
     */
    public function getFormattedCrossReferences()
    {
        $crossRefArray = $this->getCrossReferences();
        $refs = array();
        foreach ($crossRefArray as $nature => $refArraySourceTarget) {
            foreach (array('both', 'target', 'source') as $key) {
                if (array_key_exists($key, $refArraySourceTarget)) {
                    foreach ($refArraySourceTarget[$key] as $currRef) {
                        if ($key === 'source') {
                            $ref = $currRef->getRefSourceKey() . " #" . $currRef->getRefSourceId();
                            $url = $currRef->getRefSourceUrl();
                        } else {
                            $ref = $currRef->getRefTargetKey() . " #" . $currRef->getRefTargetId();
                            $url = $currRef->getRefTargetUrl();
                        }
                        $refs[$key][] = array( 'ref' => $ref, 'url' => $url);
                    }
                }
            }
        }
        return $refs;
    }

    public function getHTMLDisplayCrossRefs($with_links = true, $condensed = false, $isBrowser = true)
    {
        global $Language;

        /**
         * Array of cross references grouped by nature (to easy cross reference display)
         * Array has the form:
         * ['nature1'] => array (
         *                  ['both'] => array (
         *                                  CrossReference1,
         *                                  CrossReference2,
         *                                  ...)
         *                  ['source'] => array (
         *                                  CrossReference3,
         *                                  CrossReference4,
         *                                  ...)
         *                  ['target'] => array (
         *                                  CrossReference3,
         *                                  CrossReference4,
         *                                  ...)
         *  ['nature2'] => array (
         *                  ['both'] => array (
         *                                  CrossReference5,
         *                                  CrossReference6,
         *                                  ...)
         *                  ['source'] => array (
         *                                  CrossReference7,
         *                                  CrossReference8,
         *                                  ...)
         *                  ['target'] => array (
         *                                  CrossReference9,
         *                                  CrossReference10,
         *                                  ...)
         *  ...
         */

        $crossRefArray = $this->getCrossReferences();

        $reference_manager = ReferenceManager::instance();
        $available_natures = $reference_manager->getAvailableNatures();
        $user = UserManager::instance()->getCurrentUser();

        $itemIsReferenced = false;
        if ($isBrowser && ($user->isSuperUser() || $user->isMember($this->entity_gid, 'A'))) {
                $can_delete = true;
        } else {
                $can_delete = false;
        }

        $classes = array(
            'both'   => 'cross_reference',
            'source' => 'referenced_by',
            'target' => 'reference_to',
        );
        $message = addslashes($GLOBALS['Language']->getText('cross_ref_fact_include', 'confirm_delete'));

         // HTML part (stored in $display)
        $display = '';
        if (!$condensed) {
            $display .= '<p id="cross_references_legend">' . $Language->getText('cross_ref_fact_include', 'legend') . '</p>';
        }
        // loop through natures
        foreach ($crossRefArray as $nature => $refArraySourceTarget) {
            $div_classes = "nature";

            if (!$condensed) {
                $div_classes .= " not-condensed";
            }

            $display .= '<div class="' . $div_classes . '">';
            if (!$condensed) {
                $display .= "<p><b>" . $available_natures[$nature]['label'] . "</b>";
            }

            // loop through each type of target
            $display .= '<ul class="cross_reference_list">';
            foreach (array('both', 'target', 'source') as $key) {
                if (array_key_exists($key, $refArraySourceTarget)) {
                    // one li for one type of ref (both, target, source)
                    $display .= '<li class="' . $classes[$key] . '">';
                    switch ($key) {
                        case 'both':
                            $display .= $GLOBALS['HTML']->getImage(
                                'ic/both_arrows.png',
                                array(
                                    'alt'    => $Language->getText('cross_ref_fact_include', 'cross_referenced'),
                                    'align'  => 'top-left',
                                    'hspace' => '5',
                                    'title'  => $Language->getText('cross_ref_fact_include', 'cross_referenced')
                                )
                            );
                            break;
                        case 'target':
                            $display .= $GLOBALS['HTML']->getImage(
                                'ic/right_arrow.png',
                                array(
                                    'alt'    => $Language->getText('cross_ref_fact_include', 'reference_to'),
                                    'align'  => 'top-left',
                                    'hspace' => '5',
                                    'title'  => $Language->getText('cross_ref_fact_include', 'reference_to')
                                )
                            );
                            break;
                        default:
                            $display .= $GLOBALS['HTML']->getImage(
                                'ic/left_arrow.png',
                                array(
                                    'alt'    => $Language->getText('cross_ref_fact_include', 'referenced_in'),
                                    'align'  => 'top-left',
                                    'hspace' => '5',
                                    'title'  => $Language->getText('cross_ref_fact_include', 'referenced_in')
                                )
                            );
                            break;
                    }

                    // the refs
                    $spans = array();
                    foreach ($refArraySourceTarget[$key] as $currRef) {
                        $span = '';
                        if ($key === 'source') {
                            $id  = $currRef->getRefSourceKey() . "_" .  $currRef->getRefSourceId();
                            $ref = $currRef->getRefSourceKey() . " #" . $currRef->getRefSourceId();
                            $url = $currRef->getRefSourceUrl();
                        } else {
                            $id  = $currRef->getRefTargetKey() . "_" .  $currRef->getRefTargetId();
                            $ref = $currRef->getRefTargetKey() . " #" . $currRef->getRefTargetId();
                            $url = $currRef->getRefTargetUrl();
                        }
                        $span .= '<span id="' . $id . '" class="link_to_ref">';
                        if ($with_links) {
                            $span .= '<a class="cross-reference"
                                            title="' . $available_natures[$nature]['label'] . '"
                                            href="' . $url . '">';
                            $span .= $ref . '</a>';
                        } else {
                            $span .= $ref;
                        }
                        if ($with_links && $can_delete && !$condensed) {
                            $params = $this->getParams($currRef);
                            $span .= '<a class="delete_ref"
                                           href="/reference/rmreference.php' . $params . '"
                                           onClick="return delete_ref(\'' . $id . '\', \'' . $message . '\');">';
                            $span .= $GLOBALS['HTML']->getImage(
                                'ic/cross.png',
                                array(
                                   'alt'   => $Language->getText('cross_ref_fact_include', 'delete'),
                                   'title' => $Language->getText('cross_ref_fact_include', 'delete')
                                )
                            );
                            $span .= '</a>';
                        }
                        $spans[] = $span;
                    }
                    $display .= implode(', </span>', $spans) . '</span>';
                    $display .= '</li>';
                }
            }
            $display .= "</ul>";
            $display .= "</p>";
            $display .= "</div>";
        }

        return $display;
    }

    public function getHTMLCrossRefsForMail()
    {
        $html              = '';
        $cross_refs        = $this->getCrossReferences();
        $reference_manager = ReferenceManager::instance();
        $available_natures = $reference_manager->getAvailableNatures();

        foreach ($cross_refs as $nature => $references_by_destination) {
            $html .= '<div>';
            $refs = array();
            foreach ($references_by_destination as $key => $references) {
                foreach ($references as $reference) {
                    if ($key === 'source') {
                        $ref = $reference->getRefSourceKey() . " #" . $reference->getRefSourceId();
                        $url = $reference->getRefSourceUrl();
                    } else {
                        $ref = $reference->getRefTargetKey() . " #" . $reference->getRefTargetId();
                        $url = $reference->getRefTargetUrl();
                    }
                    $title = $available_natures[$nature]['label'];
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
        $html              = '';
        $cross_refs        = $this->getCrossReferences();

        foreach ($cross_refs as $nature => $references_by_destination) {
            $html .= '';
            $refs = array();
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

    /**
     * This function retrieves all cross references for given entity id, a group id, and a type
     * @return array cross references data
     */
    protected function getCrossReferences()
    {
        $crossRefArray = array();

        // Walk the target ref array in order to fill the crossRefArray array
        for ($i = 0, $nb_target_refs = count($this->target_refs_datas); $i < $nb_target_refs; $i++) {
            $is_cross = false;
            // Check if the ref is cross referenced (means referenced by a source)
            $j = 0;
            $source_position = 0;
            foreach ($this->source_refs_datas as $source_refs) {
                if ($this->target_refs_datas[$i]->isCrossReferenceWith($source_refs)) {
                    $is_cross = true;
                    $source_position = $j;
                }
                $j++;
            }
            if ($is_cross) {
                if ($this->entity_id == $this->target_refs_datas[$i]->getRefSourceId() &&
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
}
