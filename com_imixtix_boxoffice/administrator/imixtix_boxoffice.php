<?php
/**
 * @version     1.0.0
 * @package     com_imixtix_boxoffice
 * @copyright   Copyright (C) 2012. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Nanda Kishore M <nandaatwork@yahoo.com> - http://www.jomexperts.com
 */

// no direct access
defined('_JEXEC') or die ;

// Access check.
if (!JFactory::getUser() -> authorise('core.manage', 'com_imixtix_boxoffice')) {
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

define ('IMG_URL', JURI::base() . 'components/com_imixtix_boxoffice/assets/images/');
$document = JFactory::getDocument();

   
### Including the Jquery and Date Picker JS and CSS ###
$document -> addScript(JURI::base() . 'components/com_imixtix_boxoffice/assets/js/jquery-1.8.3.js');
$document -> addScript(JURI::base() . 'components/com_imixtix_boxoffice/assets/js/jquery.ui.core.js');
$document -> addScript(JURI::base() . 'components/com_imixtix_boxoffice/assets/js/jquery.ui.datepicker.js');
$document -> addStyleSheet(JURI::base() . 'components/com_imixtix_boxoffice/assets/css/jquery.ui.theme.css');
$document -> addStyleSheet(JURI::base() . 'components/com_imixtix_boxoffice/assets/css/jquery.ui.core.css');
$document -> addStyleSheet(JURI::base() . 'components/com_imixtix_boxoffice/assets/css/jquery.ui.datepicker.css');
$document -> addScript(JURI::base() . 'components/com_imixtix_boxoffice/assets/js/imixtix.js');
$document -> addScript(JURI::base() . 'components/com_imixtix_boxoffice/assets/js/country-state.js');
$document -> addStyleSheet(JURI::base() . 'components/com_imixtix_boxoffice/assets/css/imixtix.css');

        
               
// Include dependancies
jimport('joomla.application.component.controller');
$controller = JController::getInstance('ImixtixBO');
$controller -> execute(JRequest::getCmd('task'));
JRequest::setVar( 'hidemainmenu', 0 );
$controller -> redirect();
