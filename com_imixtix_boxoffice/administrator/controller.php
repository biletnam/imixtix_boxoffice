<?php
/**
 * @version     1.0.0
 * @package     com_imixtix_boxoffice
 * @copyright   Copyright (C) 2012. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Nanda Kishore M <nandaatwork@yahoo.com> - http://www.jomexperts.com
 */

// No direct access
defined('_JEXEC') or die ;

echo'<link href="'.JURI::base('administrator').'/components/com_imixtix_boxoffice/assets/css/tpl_custom.css" rel="stylesheet" type="text/css" />';



class ImixtixBOController extends JController {
	
	/**
	 * Method to display a view.
	 *
	 * @param	boolean			$cachable	If true, the view output will be cached
	 * @param	array			$urlparams	An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return	JController		This object to support chaining.
	 * @since	1.5
	 */
	public function display($cachable = false, $urlparams = false) {


		require_once JPATH_COMPONENT . '/helpers/imixtix_boxoffice.php';
		// Load the submenu.
		ImixtixHelper::addSubmenu(JRequest::getCmd('view', ''));
		$view = JRequest::getCmd('view', 'dashboard');
		JRequest::setVar('view', $view);
      
		parent::display();
		return $this;
	}

}
