<?php // -*-php-*-
rcs_id('$Id: CustomPrefs.php,v 1.1 2004/06/18 14:42:17 rurban Exp $');

/**
 * Custom UserPreferences:
 * A list of name => _UserPreference class pairs.
 * Rationale: Certain themes should be able to extend the predefined list 
 * of preferences. Display/editing is done in the theme specific userprefs.tmpl
 * but storage/sanification/update/... must be extended to the Get/SetPreferences methods.
 *
 * This is just at alpha stage, a recommendation to the wikilens group.
 */

class _UserPreference_recengine // recommendation engine method
extends _UserPreference
{
    var $valid_values = array('php','mysuggest','mymovielens','mycluto');
    var $default_value = 'php';

    function sanify ($value) {
        if (!in_array($value, $this->valid_values)) return $this->default_value;
        else return $value;
    }
};

class _UserPreference_recalgo // recommendation engine algorithm
extends _UserPreference
{
    var $valid_values = array
        (
         'itemCos',  // Item-based Top-N recommendation algorithm with cosine-based similarity function
         'itemProb', // Item-based Top-N recommendation algorithm with probability-based similarity function. 
                     // This algorithms tends to outperform the rest.
         'userCos',  // User-based Top-N recommendation algorithm with cosine-based similarity function.
         'bayes');   // Na�ve Bayesian Classifier
    var $default_value = 'itemProb';

    function sanify ($value) {
        if (!in_array($value, $this->valid_values)) return $this->default_value;
        else return $value;
    }
};

class _UserPreference_recnnbr // recommendation engine key clustering, neighborhood size
extends _UserPreference_numeric{};

$WikiTheme->customUserPreferences
	(array
         (
          'recengine' => new _UserPreference_recengine('php'),
          'recalgo'   => new _UserPreference_recalgo('itemProb'),
          //recnnbr: typically 15-30 for item-based, 40-80 for user-based algos
          'recnnbr'   => new _UserPreference_recnnbr(10,14,80),
          ));


// $Log: CustomPrefs.php,v $
// Revision 1.1  2004/06/18 14:42:17  rurban
// added wikilens libs (not yet merged good enough, some work for DanFr)
// 

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>