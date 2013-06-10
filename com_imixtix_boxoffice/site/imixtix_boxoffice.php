<?php
/**
 * @version     1.0.0
 * @package     com_imixtix_boxoffice
 * @copyright   Copyright (C) 2012. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Neuron Softlabs Pvt. Ltd. <info@neuronsoftsols.com> - http://www.neuronsoftsols.com/
 */

// No direct access
defined('_JEXEC') or die ;



// Include dependancies
jimport('joomla.application.component.controller');
define ('EVENTS_IMG_URL', JURI::root() . 'images/imixtix_events/');
define ('IMG_URL', JURI::root() . 'components/com_imixtix_boxoffice/assets/images/');
define ('ADMIN_IMG_URL', JURI::root() . 'administrator/components/com_imixtix_boxoffice/assets/images/');
$document = JFactory::getDocument();

### Including the Jquery and Date Picker JS and CSS ###
$document -> addScript(JURI::base() . 'components/com_imixtix_boxoffice/assets/js/jquery-1.8.3.js');
$document -> addScript(JURI::base() . 'components/com_imixtix_boxoffice/assets/js/jquery.ui.core.js');
$document -> addScript(JURI::base() . 'components/com_imixtix_boxoffice/assets/js/jquery.ui.datepicker.js');
$document -> addStyleSheet(JURI::base() . 'components/com_imixtix_boxoffice/assets/css/jquery.ui.theme.css');
$document -> addStyleSheet(JURI::base() . 'components/com_imixtix_boxoffice/assets/css/jquery.ui.core.css');
$document -> addStyleSheet(JURI::base() . 'components/com_imixtix_boxoffice/assets/css/jquery.ui.datepicker.css');
$document -> addStyleSheet(JURI::root() . 'components/com_imixtix_boxoffice/assets/css/imixtix.css');
$document -> addScript(JURI::root() . 'components/com_imixtix_boxoffice/assets/js/imixtix.js');
$document -> addScript(JURI::root() . 'administrator/components/com_imixtix_boxoffice/assets/js/country-state.js');
// Launch the controller.
$controller = JControllerLegacy::getInstance('ImixtixBO');
$controller->execute(JRequest::getCmd('task', ''));
$controller->redirect();






