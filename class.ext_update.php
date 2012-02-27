<?php
/***********************************************************************
* Copyright notice
*
* (c) 2005-2009 Christian Technology Ministries International Inc.
* All rights reserved
*
* This file is part of the Web-Empowered Church (WEC)
* (http://WebEmpoweredChurch.org) ministry of Christian Technology Ministries
* International (http://CTMIinc.org). The WEC is developing TYPO3-based
* (http://typo3.org) free software for churches around the world. Our desire
* is to use the Internet to help offer new life through Jesus Christ. Please
* see http://WebEmpoweredChurch.org/Jesus.
*
* You can redistribute this file and/or modify it under the terms of the
* GNU General Public License as published by the Free Software Foundation;
* either version 2 of the License, or (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This file is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the file!
*************************************************************************/

class ext_update {
	
	function main() {
		// don't update if the full_name table doesn't exist yet. The user has
		// to properly update the extension first and let the EM create the field
		$fields = $GLOBALS['TYPO3_DB']->admin_get_fields('tx_wecstaffdirectory_info');
		if(empty($fields['full_name'])) {
			return 'Please make sure any database changes are applied before running this update script.';
		}
		// Handle name => full_name updating
		if(t3lib_div::_GP('do_update') == 1) {
			// move all names to full_names
			$res = $GLOBALS['TYPO3_DB']->sql_query('UPDATE `tx_wecstaffdirectory_info` SET full_name=name');
			
			// drop the name field if the previous step succeeded
			if($res) $res = $GLOBALS['TYPO3_DB']->sql_query('ALTER TABLE `tx_wecstaffdirectory_info` DROP name');
			if($res == 1) {
				$out = 'Update successful!';	
			} else {
				$out = 'Update failed.';
			}
		} 
		// Handle converting to department records
		else if(t3lib_div::_GP('do_update') == 2) {
			$out = $this->convertToDeptRecords();
		}
		// Show a menu
		else {
			$onClickName = "document.location='".t3lib_div::linkThisScript(array('do_update' => 1))."'; return false;";
			$onClickDepartment = "document.location='".t3lib_div::linkThisScript(array('do_update' => 2))."'; return false;";
			$out .= '<form method="post">';
			if (!empty($fields['department_rec'])) {
				$out .= "<ul><li>Click Update Departments to move staff department names from string to department record (version 1.3.0)<br/>
							This is only needed if you previously used departments in strings and want to use the new department records(as of version 1.3.0)</li></ul>";
				$out .= '<input type="submit" name="do_update" value="Update Departments" onclick="'.htmlspecialchars($onClickDepartment).'"/>';
			}
			if (!empty($field['name'])) {
				$out .= "<ul><li>Click Update Name to rename field 'name' to 'full_name' (added in version 1.2.0)</li></ul>";
				$out .= '<input type="submit" name="do_update" value="Update" onclick="'.htmlspecialchars($onClickName).'"/>';
			}
			$out .= '</form>';	
		}
		
		return $out;
	}
	
	function access() {
		$fields = $GLOBALS['TYPO3_DB']->admin_get_fields('tx_wecstaffdirectory_info');
		$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wec_staffdirectory']);
		if(!empty($fields['name']) || $confArr['useStaffDeptRecords'] || empty($field['department_rec']) || t3lib_div::_GP('do_update')) {
			return true;
		} else {
			return false;
		}
	}
	
	function convertToDeptRecords() {
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT * FROM `tx_wecstaffdirectory_info` WHERE deleted=0 ORDER BY department, sorting');
		$staffCount = 0;
		$deptCount = 0;
		$uniquePids = array();
		$sortDeptList = array();
		$allDeptList = array();
		$deptNameList = array();
		
		// Grab all the staff, and read in staff info + department info
		//
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			// if we have already converted any of the staff, then stop now
			if ($row['department_rec']) {
				$staffCount = -1;
				break;
			}
			$thisPid = $row['pid'];
			$staffList[] = $row;
			// add unique pids where staff storage is
			if (!in_array($row['pid'],$uniquePids)) 
				array_push($uniquePids, $row['pid']);
			
			$deptStr = trim($row['department']);
			// keep track of unique department names
			
			// add all departments to departments list
			if (strlen($deptStr)) {
				$curDeptList = explode('|',$deptStr);
				// add each department name to list
				for ($j = 0; $j < count($curDeptList); $j++) {
					$deptName = $curDeptList[$j];
					if (!$deptName)
						continue;
					$found = false;	
					for ($k = 0; $k < count($deptNameList); $k++) {
						if (!strcasecmp($deptName, $deptNameList[$k])) {
							$found = true;
							break;
						}
					}
					if (!$found) {
						$deptNameList[] = $deptName;
						
						// remove any #- before departments (if used)
						if ((ctype_digit($firstCh = substr($deptName,0,1)))) {
							if ($dashCh = strpos($deptName, '-')) {
								$deptName = substr($deptName, $dashCh+1);
							}
						}
						$sortDeptList[] = trim($deptName) . '|' . $thisPid;
						$deptCount++;
					}
				}
			}
			$staffCount++;
		}
		if ($staffCount == -1) {
			return 'This site already has converted staff department records. Nothing done.';
		}
		// Create department_records
		//
		else if ($deptCount) {
			// if they already exist, then do nothing
			$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT * FROM `tx_wecstaffdirectory_department`');
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
				return 'This site already has department records setup. Nothing more done.';
			} 
			else {
				// sort departments by name (before add)
				usort($sortDeptList, array(&$this,"cmpDepts"));
				
				// then add each one to _department table
				for ($i = 0; $i < count($sortDeptList); $i++) {
					$thisDept = explode('|',$sortDeptList[$i]);
					$deptName = $thisDept[0];
					$deptPid = $thisDept[1];
					$newDept['tstamp'] = $newDept['crdate'] = mktime();
					$newDept['department_name'] = $deptName; 
					$newDept['pid'] = $deptPid; 
					$newDept['sorting'] = $i;
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_wecstaffdirectory_department', $newDept);
					$newUID = $GLOBALS['TYPO3_DB']->sql_insert_id();
					$allDeptList[] = array('department_name' => $deptName, 'pid' => $deptPid, 'newUID' => $newUID);
				}
			}
			// assign all staff department_records
			for ($i = 0; $i < count($staffList); $i++) {
				// extract all the departments from staff...
				$staffDepts = explode('|', $staffList[$i]['department']);
				$updRec = array();
				if (count($staffDepts)) {
					// build list of staff records to link to
					$newRec = '';
					for ($j = 0; $j < count($staffDepts); $j++) {
						$deptName = $staffDepts[$j];
						// remove any #- before departments (if used)
						if ((ctype_digit($firstCh = substr($deptName,0,1)))) {
							if ($dashCh = strpos($deptName, '-')) {
								$deptName = substr($deptName, $dashCh+1);
							}
						}					
						$newRecUID = 0;
						for ($k = 0; $k < count($allDeptList); $k++) {
							if (!strcasecmp($allDeptList[$k]['department_name'],$deptName)) {
								$newRecUID = $allDeptList[$k]['newUID'];
								break;
							}
						}
						$newRec['uid_local'] = $staffList[$i]['uid'];
						$newRec['uid_foreign'] = $newRecUID;
						$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_wecstaffdirectory_department_mm', $newRec);
					}
					// now update the count of department_rec
					$updRec['department_rec'] = count($staffDepts);
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_wecstaffdirectory_info', 'uid='.$staffList[$i]['uid'], $updRec);
				}
			}
		}
		
		return 'Created '.$deptCount." Department records. Updated ".$staffCount.' staff records.';		
	}
	
	// This is added so can sort/compare strings by lowercase
	function cmpDepts($a, $b) {
		$al = strtolower($a);
        $bl = strtolower($b);
        if ($al == $bl) {
            return 0;
        }
        return ($al > $bl) ? +1 : -1;		
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_staffdirectory/class.ext_update.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_staffdirectory/class.ext_update.php']);
}
?>