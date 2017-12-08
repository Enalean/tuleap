import Gettext             from 'node-gettext';
import french_translations from '../../po/fr.po';

const gettext_provider = new Gettext();
gettext_provider.addTranslations('fr_FR', 'cross-tracker', french_translations);
gettext_provider.setTextDomain('cross-tracker');

export { gettext_provider };
