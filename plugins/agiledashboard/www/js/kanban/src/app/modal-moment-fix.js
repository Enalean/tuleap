// This file is needed while the tuleap-artifact-modal does not import moment
// It instead expects it to be present on window
import moment from 'moment';

window.moment = moment;
