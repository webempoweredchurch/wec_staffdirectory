<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Web-Empowered Church Team <staffdirectory@webempoweredchurch.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_t3lib.'class.t3lib_befunc.php');

/**
 * Custom backend label class for the 'wec_staffdirectory' extension.
 *
 * @author	Web-Empowered Church Team <staffdirectory@webempoweredchurch.org>
 */
class tx_wecstaffdirectory_labels {

	function getStaffLabel(&$params, &$pObj) {
		$uid = $params['row']['uid'];
		$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wec_staffdirectory']);
		if ($confArr['useFEUsers']) {
			$feuser_id = $params['row']['feuser_id'];
			if($feuser_id) {
				$feuser = t3lib_BEfunc::getRecord('fe_users', $feuser_id);
				$label = $feuser['name'];
			}
		}
		else {
			$staffinfo = t3lib_BEfunc::getRecord('tx_wecstaffdirectory_info', $uid);
			if ($staffinfo['full_name'])
				$label = $staffinfo['full_name'];
			else if ($staffinfo['first_name'] && $staffinfo['last_name'])
				$label = $staffinfo['last_name'] . ', ' . $staffinfo['first_name'];
			else if ($staffinfo['last_name']) 
				$label = $staffinfo['last_name'];
			else
				$label = $staffinfo['first_name'];
		}
		
		$params['title'] = $label;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_staffdirectory/class.tx_wecstaffdirectory_labels.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_staffdirectory/class.tx_wecstaffdirectory_labels.php']);
}