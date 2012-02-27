<?php

/**
 * userFunc conditional to determine if we're in a form based
 * on the POST variables. Used to dynamically switch to a USER_INT for
 * form handling
 *
 * @return 		boolean		True if we should not be cached
 */

function user_isStaffDirectoryNotCached() {
	$postVars = t3lib_div::_GP('tx_wecstaffdirectory_pi1');

	if ($postVars['delete'] || $postVars['staff_uid'] || $postVars['new'] || $postVars['msg'] || $postVars['edit'] ||
		$postVars['searchFulltext'] || $postVars['sortby'] && t3lib_div::_GP('admin')) {
		return true;
	}
	else {
		return false;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_staffdirectory/pi1/class.tx_wecstaffdirectory_isNotCached.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_staffdirectory/pi1/class.tx_wecstaffdirectory_isNotCached.php']);
}

?>
