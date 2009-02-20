<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
 * 
 * 
 *
 * Cross Reference Factory class
 */

require_once('common/reference/CrossReference.class.php');

class CrossReferenceFactory {
    
    var $entity_id;
    var $entity_gid;
    var $entity_type;
    
    /**
     * array of references {Object CrossReference}
     * to the current CrossReferenceFactory 
     * In other words, Items in this array have made references to the current Item
     * @var array
     */
    var $source_refs_datas;
    
    /**
     * array of references {Object CrossReference} made by the current CrossReferenceFaxtory
     * In other words, Items in this array are referenced by the current Item
     * @var array
     */
    var $target_refs_datas;
    
    /** 
     * Constructor 
     * Note that we need a valid reference parameter 
     */
    function CrossReferenceFactory($entity_id,$entity_type,$entity_group_id) {
       $this->entity_id=$entity_id;
       $this->entity_type=$entity_type;
       $this->entity_gid=$entity_group_id;
    }
	
    /**
     * Fill the arrays $this->source_refs_datas and $this->target_refs_datas
     * for the current CrossReferenceFactory  
     */
	function fetchDatas() {
		$sql = 'SELECT * from cross_references where ' .
				'( target_gid='.$this->entity_gid.' AND target_id='.$this->entity_id.' AND target_type="'.$this->entity_type.'" ) ' .
				'OR (source_gid='.$this->entity_gid.' AND source_id='.$this->entity_id.' AND source_type="'.$this->entity_type.'" )';
		$res = db_query($sql);
		if ($res && db_numrows($res) > 0) {
		
			$this->source_refs_datas=array();
			$this->target_refs_datas=array();
			
	    	while ($field_array = db_fetch_array($res)) {	    		
	    		
	    		$target_id=$field_array['target_id'];
	    	    $target_gid=$field_array['target_gid'];
	    	    $target_type=$field_array['target_type'];
	    	    
				$source_id=$field_array['source_id'];
	    	    $source_gid=$field_array['source_gid'];
	    	    $source_type=$field_array['source_type'];
	    	    
	    	    $user_id=$field_array['user_id'];
	    	    $created_at=$field_array['created_at'];
	    	    
	    	    if ( ($target_id==$this->entity_id) &&
	    	    	 ($target_gid==$this->entity_gid) &&
	    	    	 ($target_type==$this->entity_type)
	    	    	) {
	    	    	$this->source_refs_datas[] = new CrossReference($source_id,$source_gid,$source_type,$target_id,$target_gid,$target_type,$user_id);
	    	    }
	    	    if ( ($source_id==$this->entity_id) &&
	    	    	 ($source_gid==$this->entity_gid) &&
	    	    	 ($source_type==$this->entity_type)
	    	        ) {
	    	    	$this->target_refs_datas[] = new CrossReference($source_id,$source_gid,$source_type,$target_id,$target_gid,$target_type,$user_id);
	    	    }
	    	}

		}
	}

	function getNbReferences(){
		return (sizeof($this->target_refs_datas) +sizeof($this->source_refs_datas));
	}

    /** Accessors */
    function getRefSource() { return $this->source_refs_datas;}
    function getRefTarget() { return $this->target_refs_datas;}
    
    /**Display function */
    function DisplayCrossRefs() {
    	echo $this->getHTMLDisplayCrossRefs();
    }
    
    function getHTMLDisplayCrossRefs() {
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
    	$crossRefArray = array();
    	
        // Walk the target ref array in order to fill the crossRefArray array
    	for ($i=0;$i<sizeof($this->target_refs_datas);$i++) {   		
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
    	for ($i=0; $i < sizeof($this->source_refs_datas); $i++) {
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
        
        $reference_manager = ReferenceManager::instance();
    	$available_natures = $reference_manager->getAvailableNatures();
    	
    	// HTML part (stored in $display)
    	$display = "<p>".$Language->getText('cross_ref_fact_include','legend')."</p>";
    	foreach ($crossRefArray as $nature => $refArraySourceTarget) {
            $display .= "<p><b>" . $available_natures[$nature] . "</b>";
    	    if (array_key_exists('both', $refArraySourceTarget)) {
    	        $display.="<br>".$GLOBALS['HTML']->getImage('ic/both_arrows.png', 
                    array( 'alt'=> $Language->getText('cross_ref_fact_include','cross_referenced'),
                            'align' => 'top-left',
                            'hspace' => '5',
                            'title' => $Language->getText('cross_ref_fact_include','cross_referenced') ));
                $i = 0;
                foreach ($refArraySourceTarget['both'] as $currRef) {
                    if ($i != 0) {
                        $display .= ", ";
                    }
                    $display .= "<a title='" . $available_natures[$nature] . "' href='".$currRef->getRefTargetUrl()."'>";
                    $display.= "#".$currRef->getRefTargetId()."</a>";
                    $i++;
                }
            }
            if (array_key_exists('target', $refArraySourceTarget)) {
                $display.="<br>".$GLOBALS['HTML']->getImage('ic/right_arrow.png', 
                    array( 'alt'=> $Language->getText('cross_ref_fact_include','referenced_in'),
                            'align' => 'top-left',
                            'hspace' => '5',
                            'title' => $Language->getText('cross_ref_fact_include','referenced_in')));
                $i = 0;
                foreach ($refArraySourceTarget['target'] as $currRef) {
                    if ($i != 0) {
                        $display .= ", ";
                    }
                    $display .= "<a title='" . $available_natures[$nature] . "' href='".$currRef->getRefTargetUrl()."'>";
                    $display.= "#".$currRef->getRefTargetId()."</a>";
                    $i++;
                }
            }
            if (array_key_exists('source', $refArraySourceTarget)) {
                $display.="<br>".$GLOBALS['HTML']->getImage('ic/left_arrow.png', 
                    array( 'alt'=> $Language->getText('cross_ref_fact_include','referenced_in'),
                            'align' => 'top-left',
                            'hspace' => '5',
                            'title' => $Language->getText('cross_ref_fact_include','referenced_in')));
                $i = 0;
        	    foreach ($refArraySourceTarget['source'] as $currRef) {
        	       if ($i != 0) {
                        $display .= ", ";
                    }
                    $display .= "<a title='" . $available_natures[$nature] . "' href='".$currRef->getRefSourceUrl()."'>";
                    $display.= "#".$currRef->getRefSourceId()."</a>";
                    $i++;
                }
            }
    	}
    	
    	return $display;
    }
    
}

?>