<?php
 /**
  * 'itemsProcFunc' for the 'tx_wecstaffdirectory' extension.
  *
  * @author	WEC Staff Directory <staffdirectory@webempoweredchurch.org>
  * @package TYPO3
  * @subpackage tx_wecstaffdirectory
  */
class tx_wecstaffdirectory_itemsProcFunc {
	
/**
 * insert departments based on storagePid in flexform, "storage pid", and conf['pid_list']
 *
 * @param	array		$config: extension configuration array
 * @return	array		$config array with extra codes merged in
 */
	function show_department($config) {
		if (isset($config['items'][0][0])) 
			unset($config['items'][0]);
		if (!isset($config)) {
			$config = array();
		}
		// grab departments from db
		$enableFields = 'AND deleted=0 AND hidden=0';
		// need to grab pids...
		if ($config['row']['pid_list'])
			$pid_list = $config['row']['pid_list'];
		else 
			$pid_list = $config['row']['pid'];
		// grab root storage pid
		$TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig('tt_content',$config['row']);
		$rootPid = $TSconfig['_STORAGE_PID']?$TSconfig['_STORAGE_PID']:0;
		if ($rootPid) {
			if (strlen($pid_list)) 
				$pid_list .= ',';
			$pid_list .= $rootPid;
		}
		
		// if we have existing flexform, then load startingPoint, if set
		if ($config['row'] && !empty($config['row']['pi_flexform'])) {
			$flexArray = t3lib_div::xml2array($config['row']['pi_flexform']);
			if (!empty($flexArray)) {
				$storagePidList = $flexArray['data']['sDEF']['lDEF']['storagePID']['vDEF'];
				if (strlen($storagePidList)) {
					$storagePids = t3lib_div::trimExplode(',', $storagePidList);
					foreach ($storagePids as $sp) {
						if (strpos($sp,'|') !== FALSE) {
							$spArray = t3lib_div::trimExplode('|',$sp);
							list($table,$sp) = t3lib_div::trimExplode('_',$spArray[0]);
						}
						if (strlen($sp)) {
							if (strlen($pid_list))
						 		$pid_list .= ',';
							$pid_list .= $sp;
						}
					}
				}
			}
		}
		// now grab all the department names and add them to item list
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('department_name,uid','tx_wecstaffdirectory_department','pid IN ('.$pid_list.') '.$enableFields,'','department_name');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$config['items'][] = Array($row['department_name'],$row['uid']);
		}
		return $config;
	}

}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_staffdirectory/pi1/class.tx_wecstaffdirectory_itemsProcFunc.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_staffdirectory/pi1/class.tx_wecstaffdirectory_itemsProcFunc.php']);
}
?>