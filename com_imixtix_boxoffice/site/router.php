<?php
/**
 * @version     1.0.0
 * @package     com_imixtix_office
 * @copyright   Copyright (C) 2012. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Neuron Softlabs Pvt. Ltd. <info@neuronsoftsols.com> - http://www.neuronsoftsols.com/
 */

// No direct access
defined('_JEXEC') or die ;

/**
 * @param	array	A named array
 * @return	array
 */
function ImixtixBOBuildRoute(&$query) {
	
	$segments = array();
	if (isset($query['task'])) {
		$segments[] = $query['task'];
		unset($query['task']);
	}
	if (isset($query['id'])) {
		$segments[] = $query['id'];
		unset($query['id']);
	}
	
	return $segments;
}

/**
 * @param	array	A named array
 * @param	array
 *
 * Formats:
 *
 * index.php?/imixtix/task/id/Itemid
 *
 * index.php?/imixtix/id/Itemid
 */
function ImixtixBOParseRoute($segments) {
	
	$vars = array();
	// view is always the first element of the array
	$count = count($segments);
	if ($count) {
		$count--;
		$segment = array_shift($segments);
		if (is_numeric($segment)) {
			$vars['id'] = $segment;
		} else {
			$vars['task'] = $segment;
		}
	}
	if ($count) {
		$count--;
		$segment = array_shift($segments);
		if (is_numeric($segment)) {
			$vars['id'] = $segment;
		}
	}

	
	return $vars;
}
