<?php
/***************************************************************
* Copyright notice
*
* (c) 2005-2011 Christian Technology Ministries International Inc.
* All rights reserved
*
* This file is part of the Web-Empowered Church (WEC)
* (http://WebEmpoweredChurch.org) ministry of Christian Technology Ministries
* International (http://CTMIinc.org). The WEC is developing TYPO3-based
* (http://typo3.org) free software for churches around the world. Our desire
* is to use the Internet to help offer new life through Jesus Christ. Please
* see http://WebEmpoweredChurch.org/Jesus.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This file is distributed in the hope that it will be useful for ministry,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
require_once(PATH_tslib.'class.tslib_pibase.php');
/**
 * Plugin 'WEC Staff Directory ' for the 'wec_staffdirectory' extension.
 *
 * @author	Web-Empowered Church Team <devteam(at)webempoweredchurch.org>
 */
class tx_wecstaffdirectory_pi1 extends tslib_pibase {
    var $prefixId = 'tx_wecstaffdirectory_pi1';        // Same as class name
    var $scriptRelPath = 'pi1/class.tx_wecstaffdirectory_pi1.php';    // Path to this script relative to the extension dir.
    var $extKey = 'wec_staffdirectory';    // The extension key.
    var $pi_checkCHash = TRUE;	// use cHash to keep unique cached pages
	var $pid_list;		// Page ID for data
	var $cObj; 			// The backReference to the mother cObj object set at call time
	var $userID;		// current UID of user logged in (0 = no user logged in)
	var $userName;		// user name for any logged in user
	var $userGroups;	// group(s) of the logged in user (array)
	var $templateCode; 	// template code
	var $isAdministrator;// if is an administrator
	var $userStaffUID;	// uid of user's staff record
	var $conf;			// the TS configuration passed in
	var $theCode;		// which function/action to do
	var $responseText;	// any response message put at top
	var $responseReturn = false; // add return button (because error)
	var $formErrorText; // any form error
	var $dbShowFields;	// database fields to show
	var $curStaffList;  // current staff listing
	var $sortOrder;		// sort order
	var $sortOptionsToShow;	// array of sort options shown in dropdown menu
	var $staffTable = 'tx_wecstaffdirectory_info';	
	
	var $searchFulltext; // search user entry
	var $altImagePath;	// path to find images
	var $useFEUsers;		// if using fe_user to store data or wec_staffdirectory_info
	var $useStaffDeptRecords;	// if using staff department records vs. string (old way) to store departments
	var $curDeptList;		// list of department records
	
	var $versioningEnabled = false; // is the extension 'version' loaded
	/**
	 * Initialize the Plugin
	 *
	 * @param	array		$conf: The PlugIn configuration
	 * @return	void
	 */
	function init($conf) {
		//-----------------------------------------------------------------
		// Initialize all class and global variables
		//-----------------------------------------------------------------
		$this->conf = $conf;				// TypoScript configuration
		$this->pi_setPiVarDefaults();		// GetPost-parameter configuration
		$this->pi_initPIflexForm();			// Initialize the FlexForms array
		$this->pi_loadLL();					// localized language variables
		$this->conf['cache']=1;
		$GLOBALS['TSFE']->page_cache_reg1 = 414;
		if (t3lib_extMgm::isLoaded('version')) {
			$this->versioningEnabled = true;
		}
		
		// initialize class variables
		$this->isAdministrator = 0;
		$this->userID = 0;
		$this->userName = '';
		$this->theCode = 'LIST';
		$this->sortOrder = 1;
		$this->searchFulltext = "";
		// set up the storage pid...if defined
		$this->config['storagePID'] = $this->getConfigVal($this, 'storagePID', 'sDEF');
		if ($this->config['storagePID']) 	// can specify in flexform
			$this->pid_list = $this->config['storagePID'];
		else if ($this->conf['pid_list'])	// or specify in TypoScript
			$this->pid_list = $this->conf['pid_list'];
		else								// the default is the current page
			$this->pid_list = $GLOBALS['TSFE']->id;
		//	Set default user info if logged in
		if ($GLOBALS['TSFE']->loginUser) {
			$this->userID = $GLOBALS['TSFE']->fe_user->user['uid'];
			$this->userName = $GLOBALS['TSFE']->fe_user->user['username'];
			$this->userGroups = $GLOBALS['TSFE']->fe_user->groupData['title'];
		}
		// Save in var whether use fe_users or not as well as use staff dept records
		$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wec_staffdirectory']);
		$this->useFEUsers = $confArr['useFEUsers'];
		$this->useStaffDeptRecords = $confArr['useStaffDeptRecords'];
		// Setup template file
		if ($tf = trim($this->getConfigVal($this, 'template_reference', 'sDEF'))) {
			$templateflex_file = $tf;
		}
		else if ($tf = trim($this->getConfigVal($this, 'template_file', 'sDEF'))) {
			$templateflex_file = "uploads/tx_wecstaffdirectory/" . $tf;
		}
		else {
			$templateflex_file = $this->conf['templateFile'];
		}
		if ($templateflex_file) {
			$this->templateCode = $this->cObj->fileResource($templateflex_file);
		}
		// Database fields
		$this->dbShowFields = array('name','position_title','position_description',($this->useStaffDeptRecords) ? 'department_rec' : 'department','team','start_date','biography','news','photo_main','photos_etc','misc','email','social_contact1','social_contact2','social_contact3','telephone','cellphone','fax','name_title','address','address2','city','zone','zip','country','map','gender');
		foreach (explode(',',$this->conf['dbShowFields']) as $extraShowField) {
			if (!isset($this->dbShowFields[trim($extraShowField)])) {
				$this->dbShowFields[] = trim($extraShowField);
			}
		}
				
		// Adds hook for adding any extra db fields
		// Note that  you do not need to delete any here because if not activated, will not be shown. These are all POSSIBLE ones available.
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecstaffdirectory_pi1']['addExtraFields'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecstaffdirectory_pi1']['addExtraFields'] as $_classRef) {
				$_procObj = & t3lib_div::getUserObj($_classRef);
				$extraDBFields = $_procObj->addExtraFields($this->dbShowFields, $this);
			}
			if (!empty($extraDBFields) && is_array($extraDBFields)) {
				$this->dbShowFields = array_merge($this->dbShowFields, $extraDBFields);
			}
		}
		//-----------------------------------------------------------------
		// Load the Flexform variables
		//-----------------------------------------------------------------
		// MAIN
		$this->config['templateFile']		= $this->getConfigVal($this, 'templateFile', 		'sDEF');
		$this->config['title']				= $this->getConfigVal($this, 'title',				'sDEF');
		$this->config['show_department']	= trim($this->getConfigVal($this, 'show_department','sDEF'));
		$this->config['display_random']		= trim($this->getConfigVal($this, 'display_random','sDEF'));
		$this->config['single_pid']			= trim($this->getConfigVal($this, 'single_pid','sDEF'));
		$this->config['back_pid']			= trim($this->getConfigVal($this, 'back_pid','sDEF'));
		// "CODE" decides what is rendered: codes can be set by TypoScript or FlexForm with priority on FlexForm
		$code = $this->getConfigVal($this,'what_to_display', 'sDEF');
		$this->config['code'] = $code ? $code : $this->cObj->stdWrap($this->conf['code'], $this->conf['code.']);
		$this->theCode = $this->config['code'];
		// OPTIONS
		$this->config['sort_order']			= $this->getConfigVal($this, 'sort_order', 			's_display');
		$this->config['sort_options']		= $this->getConfigVal($this, 'sort_options', 		's_display');
		$this->config['sort_direction']		= $this->getConfigVal($this, 'sort_order_ascdesc', 	's_display');
		$this->config['show_search']		= $this->getConfigVal($this, 'show_search', 		's_display');
		$this->config['show_listheader']	= $this->getConfigVal($this, 'show_listheader', 	's_display');
		$this->config['show_deptSelector']	= $this->getConfigVal($this, 'show_deptSelector', 	's_display');
		$this->config['num_per_page']		= $this->getConfigVal($this, 'num_per_page', 		's_display');
		$this->sortOptionsToShow = $this->config['sort_options'] ? t3lib_div::trimExplode(',', $this->config['sort_options']) : array();
		if ($this->config['sort_order'])	$this->sortOrder = $this->config['sort_order'];
		// FIELDS
		$this->config['directory_fields']	= $this->getConfigVal($this, 'directory_fields', 	's_fields');
		$this->config['personalpage_fields']= $this->getConfigVal($this, 'personalpage_fields', 's_fields');
		$this->config['editpersonalpage_fields']= $this->getConfigVal($this, 'editpersonalpage_fields', 's_fields');
		// set default values if nothing set
		if (!$this->config['directory_fields'])
			$this->config['directory_fields'] = array('name','department','position_title','email','telephone','photo_main');
		else
			$this->config['directory_fields'] = t3lib_div::trimExplode(',',$this->config['directory_fields']);
		if (!$this->config['personalpage_fields'])
			$this->config['personalpage_fields'] = array('name','department','position_title','position_description','email','telephone','biography','news','photo_main','photos_etc','misc');
		else
			$this->config['personalpage_fields'] = t3lib_div::trimExplode(',',$this->config['personalpage_fields']);
		if (!$this->config['editpersonalpage_fields'])
			$this->config['editpersonalpage_fields'] = array('name','email','telephone','biography','news','photo_main','photos_etc','misc');
		else
			$this->config['editpersonalpage_fields'] = t3lib_div::trimExplode(',',$this->config['editpersonalpage_fields']);
		// ADMINISTRATOR
		# a) BE username(s)
 		$this->config['administrator_users']= $this->getConfigVal($this, 'administrator_users', 's_administrator');
		# b) BE group(s)
		$this->config['administrator_groups']= $this->getConfigVal($this, 'administrator_groups', 's_administrator');
		// SET if administrator - case a) by user
		if ($this->userID && ($admins = $this->config['administrator_users'])) {
			$adminList = t3lib_div::trimExplode(',',$admins);
			foreach ($adminList as $thisAdmin) {
				if (($thisAdmin == $this->userID) || ($thisAdmin == $this->userName)) {
					$this->isAdministrator = 1;
					break;
				}
			}
		}
		// SET if administrator - case b) by group
		if ($this->userID && ($admins = $this->config['administrator_groups'])) {
			$adminList = t3lib_div::trimExplode(',',$admins);
			foreach ($adminList as $thisAdmin) {
				if (is_numeric($thisAdmin)) { # seach for group id
					if (array_key_exists($thisAdmin, $this->userGroups)) {
						$this->isAdministrator = 1;
						break;
					}
				} else { # search for group name
					if (in_array($thisAdmin, $this->userGroups)) {
						$this->isAdministrator = 1;
						break;
					}
				}
			}
		}
		$this->config['staff_can_edit']= $this->getConfigVal($this, 'staff_can_edit', 's_administrator');
		$this->config['staff_can_delete']= $this->getConfigVal($this, 'staff_can_delete', 's_administrator');
		$this->config['staff_can_add']= $this->getConfigVal($this, 'staff_can_add', 's_administrator');
		$this->altImagePath = strlen($this->conf['altImagePath']) ? $this->conf['altImagePath'] : 'uploads/tx_wecstaffdirectory/';
		//-----------------------------------------------------------------
		// Load & Process Incoming Vars -- GET/POST and Form
		//-----------------------------------------------------------------
		// SECURITY FOR ALL INCOMING VARS -- var = (int) var
		if ($this->piVars) {
			foreach ($this->piVars as $key => $value)
				$this->piVars[$key] = htmlspecialchars(stripslashes($value));
		}
		if ($this->piVars['curstaff']) 	 $this->piVars['curstaff'] 	= (int) $this->piVars['curstaff'];
		if ($this->piVars['showSingle']) $this->piVars['curstaff'] 	= (int) $this->piVars['showSingle'];
		if ($this->piVars['staff_uid'])  $this->piVars['staff_uid'] 	= (int) $this->piVars['staff_uid'];
		if ($this->piVars['curstaff'] && !$this->conf['multiplePluginsPerPage']) {
			$this->theCode = 'SINGLE';
		}
		
		// set sort order if passed in
		if ($this->piVars['sortby'])
			$this->sortOrder = (int) $this->piVars['sortby'];
		// handle fulltext search if passed in
		if ($this->piVars['searchFulltext'])
			$this->searchFulltext = $this->piVars['searchFulltext'];
		// ------------------------------------------------------------
		// Additional Init Actions
		//-------------------------------------------------------------
		// LOAD IN DEPARTMENT DATABASE (if using department records)
		//===========================================================
		if ($this->useStaffDeptRecords) {
			$lang = ($l = $GLOBALS['TSFE']->sys_language_uid) ? $l : '0,-1';
			$queryStr .= 'sys_language_uid IN ('.$lang.')';
			$queryStr .= $this->cObj->enableFields('tx_wecstaffdirectory_department');
			$queryStr .= ' AND tx_wecstaffdirectory_department.pid IN (' . $this->pid_list . ')';
			$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,pid,sorting,department_name,description,parent_department','tx_wecstaffdirectory_department',$queryStr,'','parent_department,sorting');
			while ($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2)) {
/*				
				if ($this->versioningEnabled) {
					// get workspaces Overlay
					$GLOBALS['TSFE']->sys_page->versionOL('tx_wecstaffdirectory_department',$row2);
				}
				if (is_array($row2)) {
*/					
					$this->curDeptList[$row2['uid']] = $row2;
/*				} 
*/
			}
			if (!count($this->curDeptList)) {
				$this->useStaffDeptRecords = false;
			}
		}
		
		// LOAD IN THE STAFF DATABASE
		//============================================
		$this->curStaffList = array();
		$selectStr = '*, A.uid as staff_uid, A.l18n_parent as staff_lang_parent, A.title as name_title';
		if ($this->useFEUsers) { // grab from fe_users  and staff_info
			$fromStr = $this->staffTable.' AS A, fe_users AS B';
			$queryStr = 'A.feuser_id = B.uid AND A.pid in ('.$this->pid_list.')';
			$uinfo_tbl = 'B';
			$uinfo_name_field = 'name';
		}
		else { // just get everything from staff info
			$fromStr = $this->staffTable.' AS A';
			$queryStr = 'A.pid in ('.$this->pid_list.')';
			$uinfo_tbl = 'A';
			$uinfo_name_field = 'full_name';
		}
		// load departments if using staff department records
		if ($this->useStaffDeptRecords) { 
//			$fromStr .= ', (tx_wecstaffdirectory_department AS C LEFT JOIN tx_wecstaffdirectory_department_mm AS D ON C.uid = D.uid_foreign)';
//			$queryStr .= ' AND D.uid_local = A.uid';
			// old way -- did not handle if department was blank			
			$fromStr .= ', tx_wecstaffdirectory_department AS C, tx_wecstaffdirectory_department_mm AS D';
			$queryStr .= ' AND C.uid = D.uid_foreign AND D.uid_local = A.uid';
		}
		$enableFields = $this->cObj->enableFields($this->staffTable);
		$queryStr .= str_replace($this->staffTable,'A',$enableFields);
		if (($showDepts = $this->config['show_department']) || ($showDepts = $this->piVars['show_department'])) {
			$showDepts = t3lib_div::trimExplode(',',$showDepts);
			if (count ($showDepts)) {
				$deptQueryStr = '';
				$addedDept = 0;
				for ($k = 0; $k < count($showDepts); $k++) {
					if ($showDepts[$k]) {
						if ($addedDept > 0) 
							$deptQueryStr .= ' OR ';
						if ($this->useStaffDeptRecords) {
							$showDeptRec = $this->curDeptList[$showDepts[$k]]['department_name'];
							$deptQueryStr .= 'C.department_name LIKE \'' . addslashes($showDeptRec) . '\'';
						}
						else {
							$deptQueryStr .= 'A.department LIKE "' . addslashes($showDepts[$k]) . '"';
						}
						$addedDept++;
					}
				}
				if ($addedDept == 1)
					$queryStr .= ' AND ' . $deptQueryStr;
				else if ($addedDept > 1)
					$queryStr .= ' AND (' . $deptQueryStr . ')';
			}
		}
		if ($this->searchFulltext) { // fulltext search
			$queryStr .= ' AND (';
			$queryStr .= ' '.$uinfo_tbl.'.'.$uinfo_name_field.' LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $this->searchFulltext . '%', $this->staffTable);
			$queryStr .= ' OR '.$uinfo_tbl.'.first_name LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $this->searchFulltext . '%', $this->staffTable);
			$queryStr .= ' OR '.$uinfo_tbl.'.last_name LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $this->searchFulltext . '%', $this->staffTable);
			$queryStr .= ' OR A.position_title LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $this->searchFulltext . '%', $this->staffTable);
			$queryStr .= ' OR A.position_description LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $this->searchFulltext . '%', $this->staffTable);
			$queryStr .= ' ) ';
		}
		// handle languages
		$lang = ($l = $GLOBALS['TSFE']->sys_language_uid) ? $l : '0,-1';
		$queryStr .= ' AND A.sys_language_uid IN ('.$lang.')';
		if ($this->useStaffDeptRecords) 
			$queryStr .= ' AND C.sys_language_uid IN ('.$lang.')';
		switch ($this->sortOrder) {
			case 1: // LAST NAME
				$orderBy = $uinfo_tbl.'.last_name '.$this->config['sort_direction'].','.$uinfo_tbl.'.'.$uinfo_name_field; break;
			case 2: // DEPARTMENT
				// set department backend sorting
				$sortDeptField = ($this->conf['sortFieldForDept'] ? $this->conf['sortFieldForDept'] : 'sorting');
				if (!strcmp($sortDeptField,'sorting_backend')) // rename to right field
					$sortDeptField = 'sorting ' . $this->config['sort_direction'];
				if (!strcmp($sortDeptField,'name')) {
					$sortDeptField = $uinfo_name_field;
				}	
				if (!strcmp($sortDeptField,'first_name') || !strcmp($sortDeptField,'last_name') || !strcmp($sortDeptField,'name') || !strcmp($sortDeptField,'full_name'))
					$sortDeptField = $uinfo_tbl . '.' . $sortDeptField;
				else
					$sortDeptField = 'A.' . $sortDeptField;
				$orderBy = (($this->useStaffDeptRecords) ? 'C.sorting, C.department_name ' : 'A.department ') . $this->config['sort_direction'] . ', ' . $sortDeptField; break;
			case 3: // EMPLOYMENT DATE
				$orderBy = 'A.start_date '.$this->config['sort_direction']; break;
			case 4: // FIRST NAME
				$orderBy = $uinfo_tbl . '.first_name, ' . $uinfo_tbl . '.last_name ' . $this->config['sort_direction']; break;
			case 5: // BACKEND SORTING
				$orderBy = 'A.sorting ' . $this->config['sort_direction']; break;
			case 6: // DISPLAY ORDER FIELD
				$orderBy = 'A.display_order ' . $this->config['sort_direction']; break;
			case 7: // TEAM
				$orderBy = 'A.team ' . $this->config['sort_direction']; break;
			case 8: // MISC
				$orderBy = 'A.misc ' . $this->config['sort_direction']; break;
			case 9: // CITY
				$orderBy = 'A.city ' . $this->config['sort_direction']; break;
			case 10: // ZONE
				$orderBy = 'A.zone ' . $this->config['sort_direction']; break;
			case 11: // ZIP
				$orderBy = 'A.zip ' . $this->config['sort_direction']; break;
			case 12: // COUNTRY
				$orderBy = 'A.country ' . $this->config['sort_direction']; break;
			case 13: // NAME
				$orderBy = $uinfo_tbl . "." . $uinfo_name_field . " " . $this->config['sort_direction'].','.$uinfo_tbl.'.'.$uinfo_name_field; break;
		}
		// make sure have unique uids
//		if ($this->sortOrder != 2 && $this->useStaffDeptRecords) {
//			$queryStr .= ' GROUP by A.uid';
//		} 
		// finally, do the sql query
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				$selectStr,
				$fromStr,
				$queryStr,
				'',
				$orderBy);
		if (mysql_error())
			t3lib_div::debug(array(mysql_error(),'SELECT '.$selectStr.' FROM '.$fromStr.' WHERE '.$queryStr.' ORDER BY '.$orderBy));
		$extraDeptList = array();
		//	process all of the staff results
		$staffByUID = array();
 		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			// if multi-language site, then get original row
			if ($GLOBALS['TSFE']->sys_language_content) {
				$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_wecstaffdirectory_info', $row, $GLOBALS['TSFE']->sys_language_content, $GLOBALS['TSFE']->sys_language_contentOL, '');
			}
/*
			if ($this->versioningEnabled) {
				// get workspaces Overlay
				$GLOBALS['TSFE']->sys_page->versionOL('tx_wecstaffdirectory_info',$row);
			}
			if (!is_array($row)) {
				continue;
			}
*/
			// fix name if using frontend users
			if ($this->useFEUsers) {
				if (!$row['name'])
					$row['name'] = $row['full_name'];
			}
			else {
				if (!$row['first_name']) {
					$row['name'] = $row['full_name'];
				}
			}
 			// cleanup data
			$row['position_title'] = stripslashes($row['position_title']);
			$row['position_description'] = stripslashes($row['position_description']);
			$row['biography'] =  $this->formatStr($row['biography']);
			$row['news'] =  $this->formatStr($row['news']);
			
			// if first name/last name is, then build new name
			if ($row['first_name'] || $row['last_name']) {
				// show the name in given order that specified; default is first name/last name
				if ($this->sortOrder == 1) {
					$showName = $this->pi_getLL('last_first_name','###LAST_NAME###, ###FIRST_NAME###');
				}
				else {
					$showName = $this->pi_getLL('first_last_name','###FIRST_NAME### ###LAST_NAME###');
				}
				$showName = str_replace('###FIRST_NAME###',$row['first_name'], $showName);
				$showName = str_replace('###LAST_NAME###',$row['last_name'], $showName);
				$showName = str_replace('###NAME###',$row['name'], $showName);
				$showName = str_replace('###TITLE###',$row['title'], $showName);
				$showName = str_replace('###NAME_TITLE###',$row['title'], $showName);
				// set new name based on given name
				$row['show_name'] = trim($showName);
				if (!$row['name']) {
					if ($this->sortOrder == 1) {
						$row['name'] = $row['last_name'] . ',' . $row['first_name'];
					} 
					else {
						$row['name'] = $row['first_name'] . ' ' . $row['last_name'];
					}
				}
			}
			else {
				$row['show_name'] = $row['name'];
			}
			// fix department
			if ($this->useStaffDeptRecords) {
				$row['department'] = $row['department_name'];
			}
			
			// if multiple depts and not using department records, put copy of person with new dept name
			if (($this->sortOrder == 2) && strstr($row['department'],'|') && !$this->useStaffDeptRecords) {
				$depts = t3lib_div::trimExplode('|',$row['department']);
				foreach ($depts as $myDept) {
					$row['department'] = $myDept;
					array_push($extraDeptList, $row);
				}
			}
			// add staff to list
			else {
				
				if (!$staffByUID[$row['staff_uid']]) {
					$row['department_list'] = $row['department'];
					array_push($this->curStaffList,$row);
					$staffByUID[$row['staff_uid']] = $row;
				}
				else { // add to department list, if in more than one department
					foreach($this->curStaffList as $key => $staffEl) {
						if ($staffEl['staff_uid'] == $row['staff_uid']) {
							$this->curStaffList[$key]['department_list'] .= $this->pi_getLL('multiDept_separator',', ') . $row['department'];
							break;
						}
					}
				}
			}
			if ($this->userID && ($this->userID == $row['feuser_id'])) {
				$this->userStaffUID = $row['staff_uid'];
			}
	 	}
		// show message for empty search results
		if (!count($this->curStaffList) && $this->searchFulltext) {
			$this->responseText = $this->pi_getLL('no_staff_found','No matching staff found for "' . $this->searchFulltext . '"');
			$this->responseReturn = true;
		}
		
		//
 		// For string-based departments, this code handles if a person is in multiple depts.
		// If so, we must sort and put copy of person's records in right order (ugly code...but need to for strings)
 		//
	 	if (count($extraDeptList)) {
	 		// assume dept sorted and just need to insert first name or last name
			$sortDeptField = ($this->conf['sortFieldForDept'] ? $this->conf['sortFieldForDept'] : 'sorting');
			if (!$this->curStaffList) $this->curStaffList = array();
			// go through and find dept. Then insert based on first_name/last_name/uid
			$deptField = ($this->useStaffDeptRecords) ? 'department_rec' : 'department';
			for ($i = 0; $i < count($extraDeptList); $i++ ) {
				$addStaff = $extraDeptList[$i];
				$newStaffList = $this->curStaffList;
				$prevDept = count($this->curStaffList) ? $this->curStaffList[0][$deptField] : 0;
				$doInsert = false;
				for ($j = 0; $j < count($this->curStaffList); $j++) {
					$thisStaff = $this->curStaffList[$j];
					$changedDept = strcmp($prevDept,$thisStaff[$deptField]) ? true  : false;
					// if this is not the right dept, then skip...
					// unless we changed dept, then add to the end of last
					if ((!$changedDept && strcmp($thisStaff[$deptField],$addStaff[$deptField])) ||
						($changedDept && strcmp($addStaff[$deptField],$prevDept))) {
						$prevDept = $thisStaff[$deptField];
						continue;
					}
					if (($changedDept && (!strcmp($prevDept,$addStaff[$deptField]))) ||
					    (!strcmp($sortDeptField,'last_name')  && ($thisStaff['last_name'] >= $addStaff['last_name'])) ||
					    (!strcmp($sortDeptField,'first_name') && ($thisStaff['first_name'] >= $addStaff['first_name'])) ||
					    (!strcmp($sortDeptField,'display_order') && ($thisStaff['display_order'] >= $addStaff['display_order'])) ||
						(!strcmp($sortDeptField,'sorting_backend') && ($thisStaff['sorting'] >= $addStaff['sorting'])) ||
						($j == (count($this->curStaffList) - 1))
						) {
						$array1 = $newStaffList;
					    $array2 = array_splice($array1,$j);
					    $array1[] = $addStaff;
					    $array1 = array_merge($array1,$array2);
					    $newStaffList = $array1;
						$doInsert = true;
					    break;
					}
					$prevDept = $thisStaff[$deptField];
				}
				$this->curStaffList = $newStaffList;
				// if staff record not inserted (could not find dept), then insert now
				if (!$doInsert) {
					array_push($this->curStaffList,$addStaff);
					continue;
				}
			}
	 	}
		// ------------------------------------------------------------
		// Handle Admin Actions
		//-------------------------------------------------------------
		if ($this->isAdministrator && (t3lib_div::_GP('admin') == 1)) { // if admin=1 then show menu
			$this->theCode = 'ADMIN';
		}
		// handle editForm being passed in
		// Test and make sure USER id = $v or isAdmin
		if (($v = (int)$this->piVars['edit']) && ($this->isAdministrator || (($this->userStaffUID == $v) && $this->config['staff_can_edit']))) {
			$this->theCode  = 'EDIT';
		}
		// handle new staff being passed in
		if (($v = (int)$this->piVars['new']) && ($this->isAdministrator || ($this->userStaffUID && $this->config['staff_can_add']))) {
			$this->theCode  = 'NEW';
		}
		// clicked on delete link for staff
		if ($this->piVars['delete']) {
			$this->process_delete_form($this->piVars);
		}
		// clicked on submit button in form for edit staff page
	  	if ($this->piVars['staff_uid']) {
	  		if (!($v = $this->process_edit_form($this->piVars)) && $this->formErrorText) {
				$this->piVars['edit'] = $this->piVars['staff_uid']; // set up this field for re-editing
  				$this->theCode = $this->piVars['new'] ? 'NEW' : 'EDIT'; // show form again if errors
	  		}
	  	}
		// --------------------------------------------------
		//  LOAD CSS FILES
		// --------------------------------------------------
		// Set CSS file(s), if exists
		if (t3lib_extMgm::isLoaded('wec_styles') && ($this->conf['isOldTemplate'] == 0)) {
			require_once(t3lib_extMgm::extPath('wec_styles') . 'class.tx_wecstyles_lib.php');
			$wecStylesLib = t3lib_div::makeInstance('tx_wecstyles_lib');
			$wecStylesLib->includePluginCSS();
		}
		else if ($baseCSSFile = $this->conf['baseCSSFile']) {
			$fileList = array(t3lib_div::getFileAbsFileName($baseCSSFile));
			$fileList = t3lib_div::removePrefixPathFromList($fileList,PATH_site);
			$GLOBALS['TSFE']->additionalHeaderData['wecdiscussion_basecss'] = '<link type="text/css" rel="stylesheet" href="'.$fileList[0].'" />';
		}
		if ($cssFile = $this->conf['cssFile']) {
			$fileList = array(t3lib_div::getFileAbsFileName($cssFile));
			$fileList = t3lib_div::removePrefixPathFromList($fileList,PATH_site);
			$GLOBALS['TSFE']->additionalHeaderData['wecdiscussion_css'] = '<link type="text/css" rel="stylesheet" href="'.$fileList[0].'" />';
		}
					
		// if multiple plugins, and this is the single view, and none selected, then select first one in list
		if ($this->conf['multiplePluginsPerPage'] && ($this->theCode=='SINGLE') && !$this->piVars['curstaff']) {
			$this->piVars['curstaff'] = $this->useStaffDeptRecords ? $this->curStaffList[0]['uid_local'] : $this->curStaffList[0]['uid'];
		}
		// if a message needs to be displayed, set it here
		if ($v = $this->piVars['msg']) {
			
			switch ($v) {
				case 1:
						$this->responseText = $this->pi_getLL('updated_staff_page','The staff page has been updated');
						break;
				case 2:
						$this->responseText = $this->pi_getLL('created_staff_page','The staff record has been created');
						break;
				case 3:
						$this->responseText = $this->pi_getLL('deleted_staff_page','The staff entry has been deleted');
						break;
				case 4:
						break;
			}
		}
	}
	/**
	 * Handles main actions
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	string		The content that is displayed on the web page
	 */
	function main($content,$conf)	{
		$this->local_cObj = t3lib_div::makeInstance('tslib_cObj'); // Local cObj.
		$this->init($conf);
	    if ($this->conf['isLoaded']!='yes') {
		  t3lib_div::sysLog('Static template not set for ' . $this->extKey . ' on page ID: ' . $GLOBALS['TSFE']->id . ' url: ' . t3lib_div::_GET(), $this->extKey, 3); 
	      return $this->pi_getLL('errorIncludeStatic');
		}
		$content = '';
		switch ($this->theCode) {
			case 'EDIT':
				$content .= $this->edit_staff_page($this->piVars['edit']);
				break;
			case 'NEW':
				$content .= $this->edit_staff_page(-1);
				break;
			case 'SINGLE':
				$content .= $this->display_staff_page($this->piVars['curstaff']);
				break;
			case 'RANDOM':
				$content .= $this->display_random_staff_page();
				break;
			case 'LIST BRIEF':
				$content .= $this->display_staff_directory('###STAFF_LIST_BRIEF###');
				break;
			case 'LIST COLUMN':
				$content .= $this->display_staff_directory('###STAFF_LIST_COLUMN###');
				break;
			case 'LIST VERBOSE':
				$content .= $this->display_staff_directory('###STAFF_LIST_VERBOSE###');
				break;
			case 'LIST CUSTOM':
				$content .= $this->display_staff_directory('###STAFF_LIST_CUSTOM###');
				break;
			case 'LIST':
			default:
				$content .= $this->display_staff_directory('###STAFF_LIST_LINE###');
		}
		return $this->pi_wrapInBaseClass($content);
	}
	/**
	 * display staff directory
	 *
	 * @param	string	$subTemplateName	can specify the template to use for this
	 * @return	string	the content that is displayed on the website
	*/
	function display_staff_directory($subTemplateName = '###STAFF_LIST_SINGLE###') {
		if (!$this->templateCode)
			return $this->pi_getLL('no_template','No template file found.');
		// Read in the part of the template file for staff listing
		$template = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_STAFF_LIST###');
		// Set markers for template
		$markerArray['###TITLE###'] = $this->config['title'];
		if (!$this->config['title'] && !$this->responseText && !$this->formErrorText) {
			$subpartArray['###SHOW_HEADER###'] = '';
		}
		$listFields = $this->config['directory_fields'];
		if (in_array('address',$listFields))
			array_push($listFields,'address2','city','zone','zip');
		array_push($listFields,'name_title','gender');
		
		// which fields to exclude
		$notFields = array_diff($this->dbShowFields, $listFields);
		// add paging -- determine where to start/end if setup
		$endAt = count($this->curStaffList);
		$numPerPage = $this->config['num_per_page'];
		if (!$numPerPage) $numPerPage = $endAt ? $endAt : 10;
		$curPage = (int) $this->piVars['pagenum'];
		if ($curPage < 0) $curPage = 0;
		$beginAt = $numPerPage * $curPage;
		if ($beginAt > $endAt) // error check: if begin is greater than where end, then set it to show last one
			$beginAt = $endAt - 1;
		// setup staff list template once...
		$mainStaffListTemplate = $GLOBALS['TSFE']->cObj->getSubpart($this->templateCode, $subTemplateName);
		// hide any fields not used...
		foreach ($notFields as $hideField) {
			$mainStaffListTemplate = $this->cObj->substituteSubpart($mainStaffListTemplate, '###SHOW_'.strtoupper($hideField).'###', '');
		}
		$deptHeader = $this->cObj->getSubpart($template,'###DEPARTMENT_HEADER###');
		$subpartArray['###DEPARTMENT_HEADER###'] = '';
		// go through each staff and display
		$alternate = true;
		$staffListContent = '';
		$curDept = 0;
		for ($i = $beginAt; $i < $endAt && $i < ($beginAt + $numPerPage); $i++) {
			$thisStaff = $this->curStaffList[$i];
			$itemMarkerArray = array();
			$iSubpartArray = array();
			$staffListTemplate = $mainStaffListTemplate;
			// USE DEPARTMENT STRINGS (old way)
			//-------------------------------------------
			if (!$this->useStaffDeptRecords) {
				$dept = $thisStaff['department'];
				$deptList = 0;
				// if multiple departments, separate by ,
				if (strstr($dept,'|')) {
					$deptList = t3lib_div::trimExplode('|',$dept);
					$newDept = '';
					for ($k = 0; $k < count($deptList); $k++) {
						$thisDept = $deptList[$k];
						if ($this->ctype_digit_new(substr($thisDept,0,1)))
							$newDept .= substr($thisDept,strpos($thisDept,'-')+1);
						else
							$newDept .= $thisDept;
						if ($k != (count($deptList) -1))
							$newDept .= ', ';
					}
					$dept = $newDept;
					$thisStaff['department'] = $newDept;
				}
				// translate dept (if put sort#- in front)
				if ($dept && ($this->ctype_digit_new($firstCh = substr($dept,0,1)))) {
					if ($dashCh = strpos($dept, '-')) {
						$dept = substr($dept,$dashCh+1);
						$thisStaff['department'] = $dept;
					}
				}
				// if sortBy department, put in department markers
				if (($this->sortOrder == 2) && (strcmp($curDept, $dept) && !$deptList)) {
					$curDept = $dept;
					$staffListContent .= '<div class="deptHeader">'.$curDept.'</div>';
				}
			}
			// USE STAFF DEPARTMENTS
			//------------------------------------------------
			else if ($thisStaff['department_rec']) {
				
				$dept = $thisStaff['department_name'];
//@todo -- support multiple departments listed here
				
				if (($this->sortOrder == 2) && (strcmp($curDept, $dept))) {
					$curDept = $dept;
////					$indent = '';
////					if ($this->curDeptList[$dept]['parent_department']) {
////						$indent = '&nbsp;&nbsp;&nbsp;';
////					}
					if ($deptHeader) {
						// lookup department
						$deptIndex = 0;
						
						foreach ($this->curDeptList as $thisDept) {
							if (!strcmp($thisDept['department_name'],$curDept)) {
								$deptIndex = $thisDept['uid'];
								break;
							}
						}
						// then fill in fields
						$itemMarkerArray['###DEPARTMENT_TITLE###'] = $this->curDeptList[$deptIndex]['department_name'];
						$itemMarkerArray['###DEPARTMENT_DESCRIPTION###'] = $this->curDeptList[$deptIndex]['description'];
						$itemMarkerArray['###DEPARTMENT_IMAGE###'] = $this->curDeptList[$deptIndex]['image'];
						$staffListTemplate = $deptHeader . $staffListTemplate;
					}
					else {
						$staffListContent .= '<div class="deptHeader">' . $curDept. '</div>';
					}
				}
				else {
					// if we have multiple departments, show them
					if ($thisStaff['department_list']) {
						$thisStaff['department'] = $thisStaff['department_list'];
					}
				}
			}
			
			// Set all Markers
			//----------------------------------------------------------------
			// Display all database field markers
			foreach ($listFields as $theField) {
				// add the value, but if blank, add space (default) for blank field
				$itemMarkerArray['###'.strtoupper($theField).'###'] = $thisStaff[$theField] ? $thisStaff[$theField] : $this->pi_getLL('blank_field','&nbsp;');
			}
			// use display name, if available
			if ($thisStaff['show_name']) {
				$itemMarkerArray['###NAME###'] = $thisStaff['show_name'];
			}
			// do not show email if does not exist
			if (!$thisStaff['email']) {
				$itemMarkerArray['###EMAIL_ICON###'] = $this->pi_getLL('blank_field','&nbsp;');
				$itemMarkerArray['###EMAIL_LINK###'] = $this->pi_getLL('blank_field','&nbsp;');
			}
			// process photo, email, and other markers
			$itemMarkerArray = $this->process_markers($thisStaff,$itemMarkerArray,$staffListTemplate);
			// set listing style if listing background colors are set...
			if ($this->conf['staffListingBackColor']) {
				$itemMarkerArray['###ALT_LISTCOLOR###'] = $alternate ? "listAlternatingColor1" : "listAlternatingColor2";
				if ($this->conf['staffListingBackColor2'])
					$alternate = !$alternate;
			}
			// display certain labels
			if ($thisStaff['cellphone'])
				$itemMarkerArray['###CELLPHONE_NOTIFY_LABEL###'] = $this->pi_getLL('form_cellphone_notify_label','(cell)');
			if ($thisStaff['fax'])
				$itemMarkerArray['###FAX_NOTIFY_LABEL###'] = $this->pi_getLL('form_fax_notify_label','(fax)');
			// set the pagelink markers, if available. These allow to link to the staff page
			$staffPID = $this->config['single_pid'] ? $this->config['single_pid'] : $GLOBALS['TSFE']->id;
			// parameters for links
			$params[$this->prefixId.'[curstaff]'] = $thisStaff['staff_uid'];
			
			// this is the older way to do links for items. Wrap manually with PAGELINK_START...PAGELINK_END.
			// It uses pi_linkTP though to grab cHash/no_cache href and extract the URL
			$pageLink = $this->pi_linkTP('Content',$params, true, $staffPID);
			$pageLink = preg_match('/<a.*?href="(.+?)"/', $pageLink, $matches);
			$pageLinkStart = '<a href="' . $matches[1] . '">';
			$pageLinkEnd = '</a>';
			$itemMarkerArray['###PAGELINK_START###'] = $pageLinkStart;
			$itemMarkerArray['###PAGELINK_END###'] 	 = $pageLinkEnd;
			// this is new way for links for items. It wraps the <a> tag around the text or image
			$itemMarkerArray['###NAME_LINK###'] = $this->pi_linkTP($thisStaff['show_name'],$params, true, $staffPID);
			$itemMarkerArray['###PHOTO_LINK###'] = $this->pi_linkTP($itemMarkerArray['###PHOTO###'],$params, true, $staffPID);
			$itemMarkerArray['###PHOTO_SMALL_LINK###'] = $this->pi_linkTP($itemMarkerArray['###PHOTO_SMALL###'],$params, true, $staffPID);
			// handle BIO_SHORT and NEWS_SHORT more links
			if ($thisStaff['biography']) {
				$itemMarkerArray['###BIO_SHORT_MORE###'] = $pageLinkStart.$this->pi_getLL('more_link','[More]').$pageLinkEnd;
			}
			if ($thisStaff['news']) {
				$itemMarkerArray['###NEWS_SHORT_MORE###'] = $pageLinkStart.$this->pi_getLL('more_link','[More]').$pageLinkEnd;
			}
			
			// item template
			$staffListContent .= $this->cObj->substituteMarkerArrayCached($staffListTemplate,$itemMarkerArray,$iSubpartArray,array());
		}
		$markerArray['###STAFF_LISTING_CONTENT###'] = $staffListContent;
		$markerArray = $this->process_page_markers($markerArray);
		if (!$this->responseText)
			$subpartArray['###SHOW_RESPONSE###'] = '';
		else if ($this->responseReturn) {
			$subpartArray['###SHOW_NAVIGATION###'] = '';
			$subpartArray['###SHOW_LISTING_HEADER###'] = '';
		}
		if (!$this->formErrorText)
			$subpartArray['###SHOW_ERROR###'] = '';
			
		// fill in header array if list view and have set option
		if ($this->config['show_listheader'] && ($this->theCode == 'LIST')) {
			$showFields = $this->dbShowFields;
			$showFields[] = 'department';
			foreach ($showFields as $field) {
				if (!in_array($field, $listFields)) {
					$subpartArray['###SHOW_'.strtoupper($field).'###'] = '';
				}
				else {
					$thisLabel = $this->pi_getLL('header_'.strtolower($field).'_label') ? $this->pi_getLL('header_'.strtolower($field).'_label') : $this->pi_getLL('form_'.strtolower($field).'_label');
					$markerArray['###'.strtoupper($field).'_TEXT###'] = $thisLabel ? $thisLabel : '&nbsp;';
				}
			}
		}
		else {
			$subpartArray['###SHOW_LISTING_HEADER###'] = '';
		}
		
		// remove ###staff_list subtemplates if in main template
		$subpartArray['###STAFF_LIST_LINE###'] = '';
		$subpartArray['###STAFF_LIST_COLUMN###'] = '';
		$subpartArray['###STAFF_LIST_BRIEF###'] = '';
		$subpartArray['###STAFF_LIST_VERBOSE###'] = '';
		$subpartArray['###STAFF_LIST_CUSTOM###'] = '';
		// Add sort dropdown...
		//----------------------------------------------------
		if (count($this->sortOptionsToShow)) {
			$sortMenuList = array();
			for ($n = 0; $n < count($this->sortOptionsToShow); $n++) {
				if (!strcasecmp($this->sortOptionsToShow[$n],'name'))
					array_push($sortMenuList, $this->pi_getLL('sortmenu1','Last Name'), 1);
				else if (!strcasecmp($this->sortOptionsToShow[$n],'department'))
					array_push($sortMenuList, $this->pi_getLL('sortmenu2','Department'), 2);
				else if (!strcasecmp($this->sortOptionsToShow[$n],'start_date'))
					array_push($sortMenuList, $this->pi_getLL('sortmenu3','Employment Date'), 3);
				else if (!strcasecmp($this->sortOptionsToShow[$n],'first_name'))
					array_push($sortMenuList, $this->pi_getLL('sortmenu4','First Name'), 4);
				else if (!strcasecmp($this->sortOptionsToShow[$n],'full_name'))
					array_push($sortMenuList, $this->pi_getLL('sortmenu7','Name'), 13);					
				else if (!strcasecmp($this->sortOptionsToShow[$n],'backend_sorting'))
					array_push($sortMenuList, $this->pi_getLL('sortmenu5','Default'), 5);
				else if (!strcasecmp($this->sortOptionsToShow[$n],'display_order'))
					array_push($sortMenuList, $this->pi_getLL('sortmenu6','Default'), 6);
			}
			$sortMenu = '<select name="choose_sort" size="1" onchange="location.href=this.options[this.selectedIndex].value;">';
			//$sortMenu = '<select name="' . $this->prefixId. '[sortby]" size="1" onChange="document.forms[\'searchForm\'].submit();">';
			for ($k = 0; $k < count($sortMenuList); $k+=2) {
				$sortParams['sortby'] = $sortMenuList[$k+1];
				$sortGotoURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $this->pi_linkTP_keepPIvars_url($sortParams,true, true);
				$sortMenu .= '<option value="' . $sortGotoURL . '" '.(($this->sortOrder == $sortMenuList[$k+1]) ? "selected" : '') . '>' . $sortMenuList[$k] . '</option>';
			}
			$sortMenu .= '</select>';
			$markerArray['###SORT_MENU###'] = $this->pi_getLL('sort_by','Sort By: ') . $sortMenu;
		}
		// add search if enabled
		if ($this->config['show_search']) {
			$markerArray['###ACTION_URL###'] = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $this->pi_linkTP_keepPIvars_url($urlParameters,true, true);
			# search: text label
			$markerArray['###SEARCH###'] = $this->pi_getLL('search','Search: ');
			# search: fulltext
			$searchFulltext = '<input name="' . $this->prefixId . '[searchFulltext]" size="20" value="' . htmlentities($this->searchFulltext,ENT_COMPAT,$GLOBALS['TSFE']->renderCharset) . '" />';
			$markerArray['###SEARCH_FULLTEXT###'] = $searchFulltext;
			# search: button
			$searchButton = '<input type="submit" value="' . $this->pi_getLL('search_button','Go') . '" />';
			$markerArray['###SEARCH_BUTTON###'] = $searchButton;
		}
		// Add "Add Staff" button
		if ($this->isAdministrator || ($this->userStaffUID && $this->config['staff_can_add'])) {
			$params2['new'] = 1;
			$getURL = $this->pi_linkTP_keepPIvars_url($params2,true, true);
			$markerArray['###SHOW_ADD_BTN###'] = '<a class="button" href="' . $getURL . '"><span class="label addIcon">' . $this->pi_getLL('add_btn','Add New Staff') . '</span></a>';
		}
		// Add Paging
		//----------------------------------------------------
		// Make Next link
		if (($endAt > $beginAt + $numPerPage) && $numPerPage) {
			$next = ($beginAt + $numPerPage > $endAt) ? $endAt - $numPerPage: $beginAt + $numPerPage;
			$next = intval($next / $numPerPage);
			$markerArray['###PAGING_NEXT###'] = '<span class="pageLink">' . $this->pi_linkTP_keepPIvars($this->pi_getLL('next_page', 'Next >'), array('pagenum' => $next), $this->allowCaching) . '</span>';
		}
		else {
			$markerArray['###PAGING_NEXT###'] = '';
		}
		// Make Previous link
		if (($beginAt > 0) && $numPerPage) {
			$prev = ($beginAt - $numPerPage < 0) ? 0 : $beginAt - $numPerPage;
			$prev = intval($prev / $numPerPage);
			$markerArray['###PAGING_PREV###'] = '<span class="pageLink">' . $this->pi_linkTP_keepPIvars($this->pi_getLL('prev_page', '< Previous'), array('pagenum' => $prev), $this->allowCaching) . '</span>';
		}
		else {
			$markerArray['###PAGING_PREV###'] = '';
		}
		// then substitute all the markers in the template into appropriate places
		$content = $this->cObj->substituteMarkerArrayCached($template,$markerArray,$subpartArray, array());
		// clear out any empty template fields (so if ###CONTENT1### is not substituted, will not display)
		$content = preg_replace('/###.*?###/', '', $content);
		return $content;
	}
	/**
	 * Display the given staff person's page [SINGLE view]
	 *
	 * @param	integer 	$curStaffID:	 the id of the staff page being shown
	 * @param	string 		$subTemplateName:the template to use
	 * @return	string		the content for the staff page
	*/
	function display_staff_page($curStaffID, $subTemplateName = '###TEMPLATE_STAFF_SINGLE###') {
		if (!$this->templateCode)
			return $this->pi_getLL('no_template','No template file found.');
		// now read in the part of the template file with the PAGE subtemplatename
		$staffPageTemplate = $this->cObj->getSubpart($this->templateCode,$subTemplateName);
		// find staff that matches id...
		//-------------------------------
		$thisStaff = 0;
		for ($i = 0; $i < count($this->curStaffList); $i++) {
			if ($GLOBALS['TSFE']->sys_language_content) {
				if ($this->curStaffList[$i]['staff_lang_parent'] == $curStaffID) {
					$thisStaff = $this->curStaffList[$i];
					break;
				}
			}
			elseif ($this->curStaffList[$i]['staff_uid'] == $curStaffID) {
				$thisStaff = $this->curStaffList[$i];
				break;
			}
		}
		// if no staff found, then see if exists in alt language
		if (!$thisStaff) {
			if ($curStaffID) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_wecstaffdirectory_info','uid=' . $curStaffID);
		 		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				if ($row && ($newID = $row['l18n_parent'])) {
					return $this->display_staff_page($newID, $subTemplateName);
				}
			}
		}
		// now display all the staff information
		if ($thisStaff) {
			$personalFields = $this->config['personalpage_fields'];
			if (in_array('address',$personalFields)) {
				array_push($personalFields,'address2','city','zone','zip');
			}
			array_push($personalFields,'name_title','gender');
			
			// which fields to exclude
			$notFields = array_diff($this->dbShowFields, $personalFields);
			// for multiple departments, change | to ,
			$dept = $thisStaff['department'];
			if (strstr($dept,'|')) {
				$deptList = t3lib_div::trimExplode('|',$dept);
				$newDept = '';
				for ($k = 0; $k < count($deptList); $k++) {
					$thisDept = $deptList[$k];
					if ($this->ctype_digit_new(substr($thisDept,0,1)))
						$newDept .= substr($thisDept,strpos($thisDept,'-') + 1);
					else
						$newDept .= $thisDept;
					if ($k != (count($deptList) -1))
						$newDept .= ', ';
				}
				$dept = $newDept;
				$thisStaff['department'] = $newDept;
			}
			// translate dept (if put sort#- in front)
			if ($thisStaff['department'] && ($this->ctype_digit_new($firstCh = substr($thisStaff['department'],0,1)))) {
				if ($dashCh = strpos($thisStaff['department'], '-')) {
					$thisStaff['department'] = substr($thisStaff['department'], $dashCh+1);
				}
			}
			// add department (put in separators if needed)
			if ($deptList = $thisStaff['department_list']) {
				if ($sep = $this->pi_getLL('multiDept_separator',', ')) {
					$deptList = str_replace("|", $sep, $thisStaff['department']); 
				}
				$markerArray['###DEPARTMENT_LIST###'] = $deptList;
			}
			// fill in markers based on fields selected or db fields
			//------------------------------------------------------
			foreach ($personalFields as $theField) {
				if ($thisStaff[$theField]) { // if there is a value, then fill in field
					$markerArray['###'.strtoupper($theField).'###'] = $this->html_entity_decode($thisStaff[$theField]);
					$markerArray['###'.strtoupper($theField).'_LABEL###'] = $this->pi_getLL('form_'.$theField.'_label') . $this->pi_getLL('label_separator',':');
				}
				else if (!strcmp($theField,'photo_main')) { // to support blank image
					continue;
				}
				// @todo 	array_key_exists is a hack to make the map show up since its not a real DB field.
				elseif (!array_key_exists($theField, $personalFields)) {
					continue;
				}
				// if empty field, then clear out...
				else {
					array_push($notFields,$theField);
				}
			}
			// allow first and last name, if name is a field
			if (in_array('name',$personalFields)) {
				$markerArray['###FIRST_NAME###'] = $this->html_entity_decode($thisStaff['first_name']);
				$markerArray['###FIRST_NAME_LABEL###'] = $this->pi_getLL('form_first_name_label') . $this->pi_getLL('label_separator',':');
				$markerArray['###LAST_NAME###'] = $this->html_entity_decode($thisStaff['last_name']);
				$markerArray['###LAST_NAME_LABEL###'] = $this->pi_getLL('form_last_name_label') . $this->pi_getLL('label_separator',':');
			}
			
			// allow links here (mostly for custom or random views)
			$staffPID = $this->config['single_pid'] ? $this->config['single_pid'] : $GLOBALS['TSFE']->id;
			$params[$this->prefixId.'[curstaff]'] = $thisStaff['staff_uid'];
			$itemMarkerArray['###NAME_LINK###'] = $this->pi_linkTP($thisStaff['show_name'],$params, true, $staffPID);
			$itemMarkerArray['###PHOTO_LINK###'] = $this->pi_linkTP($itemMarkerArray['###PHOTO###'],$params, true, $staffPID);
			$itemMarkerArray['###PHOTO_SMALL_LINK###'] = $this->pi_linkTP($itemMarkerArray['###PHOTO_SMALL###'],$params, true, $staffPID);
						
			// hide any fields not used...
			foreach ($notFields as $hideField) {
				$subpartArray['###SHOW_'.strtoupper($hideField).'###'] = '';
			}
			// if no photo field, clear out marker
			if (isset($notFields['photo_main'])) {
				$subpartArray['###SHOW_PHOTO_MAIN###'] = '';
				$staffPageTemplate = $this->cObj->substituteSubpart($staffPageTemplate, '###SHOW_PHOTO_MAIN###', '');
			}
			if (isset($notFields['photos_etc'])) {
				$subpartArray['###SHOW_PHOTOS_ETC###'] = '';
				$staffPageTemplate = $this->cObj->substituteSubpart($staffPageTemplate, '###SHOW_PHOTOS_ETC###', '');
			}
			if (!$thisStaff['email']) { // do not show email if does not exist
				$subpartArray['###SHOW_EMAIL###'] = '';
			}
			if (!$thisStaff['address'] && !$thisStaff['address2'] && !$thisStaff['city'] && !$thisStaff['zone'] && !$thisStaff['zip']) {
				// @todo 	Might want to use this as a point to exclude the map if map data isn't present.
				$subpartArray['###SHOW_ADDRESS###'] = '';
			}
			// process email, photo, and other markers
			$markerArray = $this->process_markers($thisStaff,$markerArray, $staffPageTemplate);
			
//			$subpartArray['###SHOW_RESPONSE###'] = '';
		}
		else {
			$markerArray['###RESPONSE_TEXT###'] = $this->pi_getLL('no_staff_found','Staff was not found.');
			$this->responseReturn = true;
		}
		// process headers
		$markerArray['###JOB_INFO_HEADER###'] = $this->pi_getLL('job_header','Job Info');
		$markerArray['###CONTACT_INFO_HEADER###'] = $this->pi_getLL('contact_header','Contact Info');
		$markerArray['###BIOGRAPHY_HEADER###'] = $this->pi_getLL('biography_header','Personal Info');
		$markerArray['###NEWS_HEADER###'] = $this->pi_getLL('news_header','Latest News');
		if (!strlen($markerArray['###DEPARTMENT###']) && !strlen($markerArray['###POSITION_TITLE###']) && !strlen($markerArray['###POSITION_DESCRIPTION###'])) $subpartArray['###SHOW_JOB_INFO_HEADER###'] = '';
		if (!strlen($markerArray['###TELEPHONE###']) && !strlen($markerArray['###EMAIL###'])) $subpartArray['###SHOW_CONTACT_INFO_HEADER###'] = '';
		if (!strlen($markerArray['###BIOGRAPHY###'])) $subpartArray['###SHOW_BIOGRAPHY###'] = '';
		if (!strlen($markerArray['###NEWS###'])) $subpartArray['###SHOW_NEWS###'] = '';
		// add button/link back to staff listing
		$backPID = $this->config['back_pid'] ? $this->config['back_pid'] : $GLOBALS['TSFE']->id;
		$getURL = $this->pi_linkTP_keepPIvars_url(array(),true,true,$backPID);
		$markerArray['###BACK_TO_STAFFLIST_BTN###'] = '<a class="button smallButton" href="'.$this->pi_getPageLink($backPID).'"><span class="label prevIcon">' . $this->pi_getLL('back_btn','Return to Staff Listing') . '</span></a>';
//		$markerArray['###BACK_TO_STAFFLIST_BTN###'] = $this->pi_linkTP($this->pi_getLL('back_btn','Return to Staff Listing'),$params,true,$backPID);
		$markerArray = $this->process_page_markers($markerArray);
		if (!$this->responseText)
			$subpartArray['###SHOW_RESPONSE###'] = '';
		else if ($this->responseReturn) {
			$subpartArray['###SHOW_NAVIGATION###'] = '';
			$subpartArray['###SHOW_LISTING_HEADER###'] = '';
		}
		
		// set the title of the single view staff page to the name of the staff on current page
		if ($this->conf['substitutePagetitle']) {
			$GLOBALS['TSFE']->page['title'] = $thisStaff['name'];
			// set pagetitle for indexed search to staff name
			$GLOBALS['TSFE']->indexedDocTitle = $thisStaff['name'];
		}
		// if multiple plugins on a page, then clear out some for single view
		if ($this->conf['multiplePluginsPerPage']) {
			$markerArray['###BACK_TO_STAFFLIST_BTN###'] = '<div>&nbsp;</div>';
			$markerArray['###STAFF_DROPDOWN_MENU###'] = '<div>&nbsp;</div>';
		}
		//set empty subparts if they have no field value in the markerarray
		foreach($this->dbShowFields as $markerArrayField) {
			$markerArrayField = strtoupper($markerArrayField);
			if(!array_key_exists('###'.$markerArrayField.'###', $markerArray)) {
				// add empty subparts if there is no field value in the markerarray
				$subpartArray['###SHOW_'.$markerArrayField.'###'] = '';
			}
		}
		
		// then substitute all the markers in the template into appropriate places
		$content = $this->cObj->substituteMarkerArrayCached($staffPageTemplate,$markerArray,$subpartArray, array());
		// clear out any empty template fields (so if ###CONTENT1### is not substituted, will not display)
		$content = preg_replace('/###.*?###/', '', $content);
		
		return $content;
	}
	/**
	 * Display the given staff person's page [SINGLE view]
	 *
	 * @param	integer 	$curStaffID:	 the id of the staff page being shown
	 * @param	string 		$subTemplateName:the template to use
	 * @return	string		the content for the staff page
	*/
	function display_random_staff_page() {
		// if do not change every time...then figure out if need to change
		if ($this->config['display_random'] != 'EVERYTIME') {
			// see if a random one has been chosen
			$savedTime = $this->cObj->data['tstamp'];
			$savedPerson = $this->cObj->data['splash_layout'];
			$newTime = 0;
			switch ($this->config['display_random']) {
				case 'EVERYDAY': // 24 hours have passed since last updated
					if (!$savedPerson || (date('z') - date('z',$savedTime)) > 1)
						$newTime = mktime();
					break;
				case 'EVERYWEEK':
					if (!$savedPerson || ((date('w') > date('w',$savedTime)) && (date('z') - date('z',$savedTime) > 7)))
						$newTime = mktime(0,0,0,date('Y'),date('n'),date('d')-(date('z')));
					break;
				case 'EVERYMONTH':
					if (!$savedPerson || (date('n') != date('n',$savedTime)))
						$newTime = mktime(0,0,0,date('Y'),date('n'),1);
					break;
				default:
					$newTime = mktime();
			}
			// save the new time
			if ($newTime) {
				// pick a new random one -- make sure not the same as before
				$c = 0;
				$sz = count($this->curStaffList);
				do {
					$randomStaff = (rand() % $sz);
					$c++;
				} while (($randomStaff == $savedPerson) && ($c < $sz*2));
				// save new values to flexform
				$saveValue = $newTime . '|' . $randomStaff;
				// write to tt_content to save values
				$where = 'uid='.$this->cObj->data['uid'];
				$newRec['tstamp'] = $newTime;
				$newRec['splash_layout'] = $randomStaff;
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_content', $where, $newRec);
			}
			else
				$randomStaff = $savedPerson;
		}
		else {
			// randomly pick out a staff member
			$randomStaff = (rand() % count($this->curStaffList));
		}
		return $this->display_staff_page($this->curStaffList[$randomStaff]['uid'], '###TEMPLATE_STAFF_RANDOM###');
	}
	/**
	 *   fill in common markers between staff listing and staff page
	 *
	 *   @param  	array 	$item			the item information (usually comes from $row in database table)
	 *   @param	 	array 	$markerArray	the marker array to update
	 *   @param		string  $curTemplate	the current template
	 *   @return	array	return the updated markerArray
	*/
	function process_markers($item, $markerArray, $curTemplate = 0) {
		$photo_img = 0;
		$photo_img_small = 0;
		// check if ###PHOTO and/or ###PHOTO_SMALL are there because don't want to process images if not needed
		$usePhotos = true;
		if ($curTemplate && (strpos($curTemplate,'###PHOTO###') === FALSE) && (strpos($curTemplate,'###PHOTO_LINK###') === FALSE)) $usePhotos = false;
		$useSmallPhotos = true;
		if ($curTemplate && (strpos($curTemplate,'###PHOTO_SMALL') === FALSE)) $useSmallPhotos = false;
		// fill in photos with special code for <img> and width/height
		$imgWd =  $this->conf['imageWidth']  ? $this->conf['imageWidth'] : '';
		$imgHt = $this->conf['imageHeight'] ? $this->conf['imageHeight'] : '';
		$imgWdSm = $this->conf['smallImageWidth']  ? $this->conf['smallImageWidth'] : '';
		$imgHtSm = $this->conf['smallImageHeight'] ? $this->conf['smallImageHeight'] : '';
		$maleBlank = $this->conf['imagePhotoBlank'];
		$femaleBlank = $this->conf['imagePhotoBlank2'];
		// determine which photo to use...
		//-------------------------------------------------------
		//  photo_main in uploads/tx_wecstaffdirectory
//		if (($img = $item['photo_main']) && (strpos($this->altImagePath,'wecstaffdirectory')) && (!$this->conf['useFEPhoto'] || !$item['image'])) {
		if (($img = $item['photo_main']) && strlen($this->conf['altImagePath']) && (!$this->conf['useFEPhoto'] || !$item['image'])) {
			$imgFile = $this->altImagePath . $item['photo_main'];
		}
		else if ($img = $item['image']) {
			$imgFile = 'uploads/tx_srfeuserregister/' . $item['image'];
		}
		else if ((($item['gender'] == 1) && ($img = $femaleBlank)) || ($img = $maleBlank)) {
			$imgFile = 0;
		}
		else { // blank image
			$photo_img = '<div style="' . ($this->conf['imageWidth'] ? 'width:'.$this->conf['imageWidth'].';' : '') . ($this->conf['imageHeight'] ? 'height:'.$this->conf['imageHeight'].';' : '' ) . '">&nbsp;</div>';
			$photo_img_small = '<div style="' .($this->conf['smallImageWidth'] ? 'width:'.$this->conf['smallImageWidth'].';' : '') . ($this->conf['smallImageHeight'] ? 'height:'.$this->conf['smallImageHeight'].';' : '') . '">&nbsp;</div>';
		}
		// add alt and title properties
		$this->conf['main_photo.']['titleText'] = $item['name'];
		$this->conf['main_photo.']['altText'] = $item['name'];
		$this->conf['main_photo_small.']['titleText'] = $item['name'];
		$this->conf['main_photo_small.']['altText'] = $item['name'];
		$addParams = ' title="' . $item['name'] . '" alt="' . $item['name'] . '"';
		
		// create photo image
		if ($usePhotos && !$photo_img) {
			if (!($photo_img = $this->getImage($img,$this->conf['main_photo.']))) {
				if ($imgFile) {
					$cleanImageDim = array('m','px');
					$imgWd = str_replace($cleanImageDim,'', $imgWd);
					$imgHt = str_replace($cleanImageDim,'', $imgHt);
					$photo_img = '<img src="' . $imgFile.'"' . ($imgWd ? ' width="'.$imgWd.'"' : '') . ($imgHt ? ' height="'.$imgHt.'"' : '') . $addParams .' border="0" />';
				}
				else if ($img) {
					$photo_img = $this->cObj->fileResource($img, $addParams);
					$photo_img = preg_replace('/width="([^"]*)"/','width="' . $imgWd . '"', $photo_img);
					$photo_img = preg_replace('/height="([^"]*)"/','height="' . $imgHt . '"', $photo_img);
				}
			}
		}
		// create small photo image
		if ($useSmallPhotos && !$photo_img_small) {
			if (!($photo_img_small = $this->getImage($img,$this->conf['main_photo_small.']))) {
				if ($imgFile) {
					$cleanImageDim = array('m','px');
					$imgWdSm = str_replace($cleanImageDim,'', $imgWdSm);
					$imgHtSm = str_replace($cleanImageDim,'', $imgHtSm);
					$photo_img_small = '<img src="'.$imgFile.'"' . ($imgWdSm ? ' width="'.$imgWdSm.'"' : '') . ($imgHtSm ? ' height="'.$imgHtSm.'"' : '') . $addParams . ' border="0" />';
				}
				else if ($img) {
					$photo_img_small = $this->cObj->fileResource($img,$addParams);
					$photo_img_small = preg_replace('/width="([^"]*)"/','width="'.$imgWdSm.'"',$photo_img_small);
					$photo_img_small = preg_replace('/height="([^"]*)"/','height="'.$imgHtSm.'"',$photo_img_small);
				}
			}
		}
		// fill in the photo image(s)
		$markerArray['###PHOTO###'] = $photo_img;
		$markerArray['###PHOTO_SMALL###'] = $photo_img_small;
		// add any additional images here too..
		if ($item['photos_etc']) {
			$otherPhotos = t3lib_div::trimExplode(',',$item['photos_etc']);
			$imgSize  = $this->conf['etcImageWidth']  ? " width=".$this->conf['etcImageWidth'] : '';
			$imgSize .= $this->conf['etcImageHeight'] ? " height=".$this->conf['etcImageHeight'] : '';
			for ($i = 0; $i < count($otherPhotos); $i++) {
				$photos_etc_img = '';
				if ($otherPhotos[$i] && !($photos_etc_img = $this->getImage($otherPhotos[$i],$this->conf['photos_etc.']))) {
					$imgFile = $this->altImagePath . $otherPhotos[$i];
					$photos_etc_img = '<img src="'.$imgFile.'" '.$imgSize.' border="0" />';
				}
				$markerArray['###PHOTO'.($i+1).'###'] = $photos_etc_img;
			}
		}
		// handle BIO_SHORT and NEWS_SHORT
		if ($item['biography']) {
			$markerArray['###BIO_SHORT###'] = $this->local_cObj->stdWrap($item['biography'], $this->conf['biographyShort_stdWrap.']);
		}
		if ($item['news']) {
			$markerArray['###NEWS_SHORT###'] = $this->local_cObj->stdWrap($item['news'], $this->conf['newsShort_stdWrap.']);
		}
		// handle TELEPHONE_OR_CELL
		if ($item['telephone'] || $item['cellphone']) {
			$markerArray['###TELEPHONE_OR_CELL###'] = $item['telephone'] ? $item['telephone'] : $item['cellphone'] . ' '. $this->pi_getLL('form_cellphone_notify_label');
			$markerArray['###CELL_OR_TELEPHONE###'] = $item['cellphone'] ? $item['cellphone'] . ' '. $this->pi_getLL('form_cellphone_notify_label') : $item['telephone'];
			$markerArray['###TELEPHONE_AND_CELL###'] = $item['telephone']  && $item['cellphone'] ? ($item['telephone'] . $this->pi_getLL('field_separator',' | ') . $item['cellphone'] . ' '. $this->pi_getLL('form_cellphone_notify_label')) : ($item['telephone'] ? $item['telephone'] : ($item['cellphone'] .' '. $this->pi_getLL('form_cellphone_notify_label')) );
		}
		// if can edit the specific staff page, then put in edit button
		if ($this->isAdministrator || (($this->userID == $item['feuser_id']) && $this->useFEUsers && $this->config['staff_can_edit'])) {
			$params['edit'] = $item['staff_uid'];
			$getURL = $this->pi_linkTP_keepPIvars_url($params,true,true);
			$markerArray['###SHOW_EDIT_BTN###'] = '<a class="button xsmallButton" href="' . $getURL . '"><span class="label editIcon">'. $this->pi_getLL('edit_btn','Edit') . '</span></a>';
//			$markerArray['###SHOW_EDIT_BTN###'] = '<span class="button smallbutton">'.$this->pi_linkTP($this->pi_getLL('edit_btn','Edit'),$params,true).'</span>';
		}
		// add delete button for admin
		if ($this->isAdministrator || ($this->userStaffUID  && $this->useFEUsers && $this->config['staff_can_delete'])) {
			$params2['delete'] = $item['staff_uid'];
			$getURL = $this->pi_linkTP_keepPIvars_url($params2,true,true);
			$markerArray['###SHOW_DELETE_BTN###'] = '<a class="button xsmallButton" href="' . $getURL . '"><span class="label deleteIcon">'. $this->pi_getLL('delete_btn','Delete') . '</span></a>';
//			$markerArray['###SHOW_DELETE_BTN###'] = $this->pi_getLL('btn_separator',' ') .'<span class="button smallbutton">'.$this->pi_linkTP($this->pi_getLL('delete_btn','Delete'),$params2,true).'</span>';
		}
		// fill in email
		if ($item['email']) {
			$markerArray['###EMAIL_LINK###'] = $this->cObj->getTypoLink($item['email'],$item['email']);
			if ($this->conf['emailIcon'])
				$markerArray['###EMAIL_ICON###'] = $this->cObj->getTypoLink($this->cObj->fileResource($this->conf['emailIcon']),$item['email']);
		}
		// fill in social contacts
		for ($k = 1; $k <= 3; $k++) {
			$social_field = 'social_contact' . $k;
			if ($item[$social_field]) {
				$this->local_cObj->data[$social_field] = $item[$social_field];
				$markerArray['###'.strtoupper($social_field).'_LINK###'] = $this->local_cObj->cObjGetSingle($this->conf[$social_field], $this->conf[$social_field."."]);
				if ($this->conf[$social_field.'Icon'])
					$markerArray['###'.strtoupper($social_field).'_ICON###'] = $this->local_cObj->cObjGetSingle($this->conf[$social_field], $this->conf[$social_field."."]);
			}
		}
		// add map, if setup
		$item['uid'] = $item['staff_uid'];
		$markerArray['###MAP###'] = $this->drawMap($item);
		// add start date
		$markerArray['###START_DATE###'] = ($item['start_date']) ? $this->getStrftime($this->pi_getLL('date_format', '%m/%d/%Y'), $item['start_date']) : '';
		// add gender 
		$markerArray['###GENDER###'] = $item['gender'] ? $this->pi_getLL('gender_female','female') : $this->pi_getLL('gender_male','male');
		
		// Adds hook for processing of extra global markers
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecstaffdirectory_pi1']['extraItemMarkerHook'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecstaffdirectory_pi1']['extraItemMarkerHook'] as $_classRef) {
				$_procObj = & t3lib_div::getUserObj($_classRef);
				$markerArray = $_procObj->extraGlobalMarkerProcessor($markerArray, $item, $this);
			}
		}
		return $markerArray;
	}
	/**
	 *   fill in common markers between staff listing and staff page
	 *
	 *   @param	 	array 	$markerArray
	 *   @return	array	return the markerArray
	*/
	function process_page_markers($markerArray) {
		if ($this->responseText) {
			$markerArray['###RESPONSE_TEXT###'] = $this->responseText;
			if ($this->responseReturn) {
				$backPID = $this->config['back_pid'] ? $this->config['back_pid'] : $GLOBALS['TSFE']->id;
				$getURL = $this->pi_linkTP_keepPIvars_url(array(),true,true,$backPID);
				$markerArray['###SHOW_BACK_BTN###'] = '<a class="button smallButton" href="'.$this->pi_getPageLink($backPID).'"><span class="label prevIcon">' . $this->pi_getLL('back_btn','Return to Staff Listing') . '</span></a>';
			}
		}
		if ($this->formErrorText) {
			$markerArray['###FORM_ERROR_TEXT###'] = $this->formErrorText;
		}
		// add staff dropdown menu so can easily access any staff member quickly
		$staffmenu = '<select name="choose_staff" size="1" onchange="location.href=this.options[this.selectedIndex].value;">';
		$staffmenu .= '<option value="0">'.$this->pi_getLL('select_staff_menu','Select Staff To View...').'</option>';
		for ($k = 0; $k < count($this->curStaffList); $k++) {
			$thisStaff = $this->curStaffList[$k];
			$urlParam['curstaff'] = $thisStaff['staff_uid'];
			$goURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $this->pi_linkTP_keepPIvars_url($urlParam, true, true);
//			$urlParam[$this->prefixId.'[curstaff]'] = $thisStaff['staff_uid'];
//			$goURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $this->pi_getPageLink($GLOBALS['TSFE']->id,'',$urlParam);
			$staffmenu .= '<option value="' . $goURL . '">' . ($thisStaff['show_name'] ? $thisStaff['show_name'] : $thisStaff['name']) . '</option>';
		}
		$staffmenu .= '</select>';
		$markerArray['###STAFF_DROPDOWN_MENU###'] = $staffmenu;
		if ($this->curDeptList && $this->config['show_deptSelector']) {
			// add department dropdown so can just view a given department
			$deptMenu = '<select name="choose_dept" size="1" onchange="location.href=this.options[this.selectedIndex].value;">';
			$deptMenu .= '<option value="0">' . $this->pi_getLL('select_department_menu','Select Department To View...') . '</option>';
			// add all as dept so can see default view
			$urlParam = array();
			$goURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $this->pi_linkTP_keepPIvars_url($urlParam,true, true);
			$deptMenu .= '<option value="' . $goURL . '">' . $this->pi_getLL('select_department_all','All') . '</option>';
			// add each department here
			foreach($this->curDeptList as $deptUID=>$deptRec) {
				$urlParam['show_department'] = $deptUID;
				$goURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $this->pi_linkTP_keepPIvars_url($urlParam,true, true);
				$deptMenu .= '<option value="' . $goURL . '">' . $deptRec['department_name'] . '</option>';
			}
			$deptMenu .= '</select>';
			$markerArray['###DEPARTMENT_DROPDOWN_MENU###'] = $deptMenu;
		}
						
		return $markerArray;
	}
	/**
	 * display edit/add form for staff page
	 *
	 * @param	integer	$whichStaffID	staff user id of page editting (0 = create)
	 * @return	string	The form for editting the staff page
	*/
	function edit_staff_page($whichStaffID) {
		$subpartArray = array();
		
		if ($this->formErrorText)
			$markerArray['###FORM_ERROR_TEXT###'] = $this->formErrorText;
		else 
			$subpartArray['###SHOW_ERROR###'] = '';
			
//		$params['no_cache'] = 1;
		$markerArray['###ACTION_URL###'] = $this->pi_getPageLink($GLOBALS['TSFE']->id,$params);
		// grab staff info
		$thisStaff = 0;
		for ($i = 0; $i < count($this->curStaffList); $i++) {
			if ($this->curStaffList[$i]['staff_uid'] == $whichStaffID) {
				$thisStaff = $this->curStaffList[$i];
				break;
			}
		}
		// pre-process BIO and NEWS by stripping all <p> tags out and converting <br> to \r\n
		$thisBio  = $this->html_entity_decode($thisStaff['biography']);
		$thisNews = $this->html_entity_decode($thisStaff['news']);
       	if ($thisBio) {
			if (preg_match_all('#<p[^>]*>(.+?)</p>#', $thisBio, $found))
 	   			$thisBio = str_replace($found[0],$found[1],$thisBio);
			if (preg_match_all('#<br[^>]*>#', $thisBio, $found))
		   		$thisBio = str_replace($found[0],"\r\n",$thisBio);
			$thisStaff['biography'] = $thisBio;
		}
		if ($thisNews) {
		    if (preg_match_all('#<p[^>]*>(.+?)</p>#', $thisNews, $found))
    	    	$thisNews = str_replace($found[0],$found[1],$thisNews);
       		if (preg_match_all('#<br[^>]*>#', $thisStaff['news'], $found))
 	   			$thisNews = str_replace($found[0],"\r\n",$thisStaff['news']);
			$thisStaff['news'] = $thisNews;
		}
		// fill in markers
		$markerArray['###FORMTITLE###'] = ($whichStaffID > 0) ? $this->pi_getLL('edit_info', 'Edit Your Staff Info') : $this->pi_getLL('add_new_staff', 'Add New Staff');
		$markerArray['###SAVE_BUTTON###'] = '<input name="save_staff_btn" type="submit" value="'.(($whichStaffID > 0) ? $this->pi_getLL('save_btn','Save Your Info') : $this->pi_getLL('create_new_btn','Create New Staff')).'"/>';
		$markerArray['###CANCEL_BUTTON###'] = '<input name="cancel_btn" type="button" onclick="javascript:history.go(-1)" value="'.$this->pi_getLL('cancel_btn','Cancel').'"/>';
		$markerArray['###HIDDEN_VARS###'] = '<input name="tx_wecstaffdirectory_pi1[staff_uid]" type="hidden" value="'.$whichStaffID.'"/>';
		$formFields = $this->dbShowFields;
		foreach ($formFields as $field) {
			if ($field == 'department_rec') $field = 'department';
			$markerArray['###FORM_'.strtoupper($field).'###'] = $this->pi_getLL('form_'.$field,$field);
			$markerArray['###'.strtoupper($field).'_LABEL###'] = $this->pi_getLL('form_'.strtolower($field).'_label').':';
			if (isset($this->piVars[$field])) { # form posted, but with errors
				$markerArray['###VALUE_'.strtoupper($field).'###'] = $this->html_entity_decode($this->piVars[$field]);
			} else if ($thisStaff && isset($thisStaff[$field])) { # initial edit, take values from DB
				$markerArray['###VALUE_'.strtoupper($field).'###'] = $this->html_entity_decode($thisStaff[$field]);
			}
		}
		$markerArray = $this->process_page_markers($markerArray);
		if ($this->curDeptList && $this->useStaffDeptRecords) {
			// add department dropdown so can just view a given department
			$deptMenu = '<select name="tx_wecstaffdirectory_pi1[department_rec]" size="1">';
			$urlParam = array();
			// add each department here
			foreach($this->curDeptList as $deptUID=>$deptRec) {
				$optVal = $deptUID;
				$isSel = ($deptUID == $thisStaff['uid_foreign']) ? ' selected' : '';
				$deptMenu .= '<option value="' . $optVal . '"' . $isSel . '>' . $deptRec['department_name'] . '</option>';
			}
			$deptMenu .= '</select>';
			$markerArray['###DEPARTMENT_INPUT###'] = $deptMenu;
		}
		else {
			$markerArray['###DEPARTMENT_INPUT###'] = '<input name="tx_wecstaffdirectory_pi1[department]" type="text" value="###VALUE_DEPARTMENT###" size="20" maxlength="40">';
		}
		
		for ($i = 1; $i <= 9; $i++)
			$markerArray['###PHOTOS_ETC'.$i.'_LABEL###'] = $this->pi_getLL('form_photos_etc_label') . ' #'.$i;
		// add showing pics (determine where img is)
		$imgFile = 0;
		if (($img = $thisStaff['photo_main']) && (strpos($this->altImagePath,'wecstaffdirectory')) && (!$this->conf['useFEPhoto'] || !$thisStaff['image'])) {
			$imgFile = $this->altImagePath . $thisStaff['photo_main'];
		}
		else if ($img = $thisStaff['image']) {
			$imgFile = 'uploads/tx_srfeuserregister/' . $thisStaff['image'];
		}
		if ($imgFile) {
			$markerArray['###PHOTO_MAIN_IMAGE###'] = '<img id="photo_main" src="'.$imgFile.'" width="70" border="0" />';
			$markerArray['###HIDDEN_VARS###'] .= '<input id="photo_main_save" name="tx_wecstaffdirectory_pi1[photo_main_save]" type="hidden" value="'.$thisStaff['photo_main'].'"/>
			<input id="photo_main_save" name="tx_wecstaffdirectory_pi1[fe_photo_save]" type="hidden" value="'.$thisStaff['image'].'"/>';
		}
		// add as many pics that are in photos_etc field
		if ($thisStaff['photos_etc']) {
			$miscPhotos = t3lib_div::trimExplode(',',$thisStaff['photos_etc']);
			for ($i = 0; $i < count($miscPhotos); $i++) {
				if (strlen($miscPhotos[$i])) {
					$imgFile = $this->altImagePath . $miscPhotos[$i];
					$markerArray['###PHOTOS_ETC_IMAGE'.($i+1).'###'] = '<img id="photos_etc'.($i+1).'" src="'.$imgFile.'" width="70" border="0" />';
					$markerArray['###PHOTOS_ETC_CLEAR_BTN'.($i+1).'###'] = '<input type="button" class="button" value="Clear Image" onclick="clearImage(\'photos_etc'.($i+1).'_save\',\'photos_etc'.($i+1).'\');">';
				}
			}
			// add photos to incoming vars because images are not saved in the form
			$markerArray['###HIDDEN_VARS###'] .= '<input id="photos_etc1_save" name="tx_wecstaffdirectory_pi1[photos_etc1_save]" type="hidden" value="'.$miscPhotos[0].'"/>' .
			'<input id="photos_etc2_save" name="tx_wecstaffdirectory_pi1[photos_etc2_save]" type="hidden" value="'.$miscPhotos[1].'"/>' .
			'<input id="photos_etc3_save" name="tx_wecstaffdirectory_pi1[photos_etc3_save]" type="hidden" value="'.$miscPhotos[2].'"/>';
		}
		// hide any fields NOT supposed to be shown
		$notFields = array_diff($this->dbShowFields, $this->config['editpersonalpage_fields']);
		foreach ($notFields as $hideField) {
			$subpartArray['###SHOW_'.strtoupper($hideField).'###'] = '';
		}
		// if no photo field, clear out marker
		if ($notFields['photo_main'] && $notFields['photos_etc']) {
			$subpartArray['###SHOW_PHOTO###'] = '';
		}
		$subpartArray['###SHOW_FEUSER###'] = ''; // only for adding
		if ($this->useFEUsers) {
			$markerArray['###FEUSER_LABEL###'] = $this->pi_getLL('form_feuser_label');
			$selFEUsers = '<select name="tx_wecstaffdirectory_pi1[feuser_link]" size="1">';
			$selFEUsers .= '<option SELECTED>'.$this->pi_getLL('select_staff_add','Select Staff To Link To...').'</option>';
			$selFEUsers .= '<option value="0">'.$this->pi_getLL('select_staff_new','Create New User').'</option>';
			for ($k = 0; $k < count($this->curStaffList); $k++) {
				$thisStaff = $this->curStaffList[$k];
				$selFEUsers .= '<option value="' . $thisStaff['staff_uid'] . '">' . $thisStaff['name'] . '</option>';
			}
			$selFEUsers .= '</select>';
			$markerArray['###FEUSER_SELECT###'] = $selFEUsers;
		}
		// process template
		//----------------------------------
		// now read in the part of the template file with the PAGE subtemplatename
		$template = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_EDITFORM###');
		// then substitute all the markers in the template into appropriate places
		$content = $this->cObj->substituteMarkerArrayCached($template,$markerArray,$subpartArray, array());
		// clear out any empty template fields (so if ###CONTENT1### is not substituted, will not display)
		$content = preg_replace('/###.*?###/', '', $content);
		
		// return form
		return $content;
	}
	/**
	 * process(save) edit/add form for staff page
	 *
	 * @param	array	$formVars	the form variables passed in from edit form
	 * @return	void
	*/
	function process_edit_form($formVars) {
		if (!($staffID = $formVars['staff_uid'])) {
			$this->formErrorText =  $this->pi_getLL('bad_edit_form','You cannot edit this now');
			return false;
		}
		// check if valid values...if not return error
		if (isset($formVars['email']) && strlen($formVars['email']) && in_array('email',$this->config['editpersonalpage_fields']) && (t3lib_div::validEmail($formVars['email']) == false)) {
			$this->formErrorText = $this->pi_getLL('bad_email_format', 'Your email is not formatted properly (email@where.com)');
			return false;
		}
		if (isset($formVars['name']) && in_array('name',$this->config['editpersonalpage_fields']) && strlen($formVars['name']) <= 1) {
			$this->formErrorText = $this->pi_getLL('bad_name','Your name needs to be at least two characters');
			return false;
		}
		// check user authentication here...
		if (!($this->isAdministrator ||
			(($staffID > 0) && ($this->userStaffUID == $staffID) && $this->config['staff_can_edit']) ||
			(($staffID <= 0) && $this->userStaffUID  && $this->config['staff_can_add'])
			)) {
			$this->formErrorText = $this->pi_getLL('bad_editor','You cannot edit this page');
			return false;
		}
		
		// if uploading files, check file name(s) for valid extensions
		if ($fileList = $_FILES[$this->prefixId]['name']['photo_main']) {
			// grab file extension(s) for photo_main
			$filePathInfo = pathinfo($fileList);
			$fileExt = $filePathInfo['extension'];
			// see if in list...if not, then bad file name
			if (!t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],strtolower($fileExt))) {
				$this->formErrorText = $this->pi_getLL('bad_imagefile_format','An image file was expected with a file extension of: ' . $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']);
				return false;
			}
		}
		if ($fileList = $_FILES[$this->prefixId]['name']['photos_etc']) {
			for ($i = 0; $i < count($fileList); $i++) {
				if ($photoEtcFile = $fileList[$i]) {
					// grab file extension(s) for photos_etc
					$filePathInfo = pathinfo($photoEtcFile);
					$fileExt = $filePathInfo['extension'];
					// see if in list...if not, then bad file name
					if (!t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],strtolower($fileExt))) {
						$this->formErrorText = $this->pi_getLL('bad_imagefile_format','An image file was expected with a file extension of: ' . $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']);
						return false;
					}
				}
			}
		}
		// find fe_user info (if available)
		$feUserRec = array();
		$feUserID = 0;
		$thisStaffIndex = -1;
		for ($i = 0; $i < count($this->curStaffList); $i++) {
			if ($this->curStaffList[$i]['staff_uid'] == $staffID) {
				if ($this->useFEUsers) $feUserID = $this->curStaffList[$i]['feuser_id'];
				$thisStaffIndex = $i;
				break;
			}
		}
		// put in <br /> where newlines are...
		if ($formVars['biography']) {
			$formVars['biography'] = str_replace("\r\n",'<br />',$formVars['biography']);
			$formVars['biography'] = $this->formatStr( $formVars['biography'] );
		}
		if ($formVars['news']) {
			$formVars['news'] = str_replace("\r\n",'<br />',$formVars['news']);
			$formVars['news'] =  $this->formatStr( $formVars['news'] );
		}
		// read in form variables passed in
		$newRec = array();
		foreach ($this->dbShowFields AS $field) {
			if (array_key_exists($field, $formVars))
				$newRec[$field] = $formVars[$field];
		}
		// update tstamp to show updated
		$newRec['tstamp'] = mktime();
		if ($whichStaffID < 0) $newRec['crdate'] = mktime();
		// Add any images uploaded...either to fe_user record or staff_directory_info record
		$mainImg = $this->uploadImages('photo_main');
		if (strlen($mainImg[0])) {
			if ($this->conf['useFEPhoto'] && $this->useFEUsers) {
				$feUserRec['image'] = $mainImg[0];
			}
			else {
				$newRec['photo_main'] = $mainImg[0];
			}
		}
		else {
			if ($this->conf['useFEPhoto'] && $this->useFEUsers)
				$feUserRec['image'] = $formVars['fe_photo_save'];
			$newRec['photo_main'] = $formVars['photo_main_save'];
		}
		$miscImgs = $this->uploadImages('photos_etc');
		if ($miscImgs && count($miscImgs))
			$newRec['photos_etc'] = implode(',',$miscImgs);
		else
			$newRec['photos_etc'] =  $formVars['photos_etc1_save'] . ',' . $formVars['photos_etc2_save'] . ',' . $formVars['photos_etc3_save'] ;
		// now go through and pull out any fe_user fields...
		if ($this->useFEUsers) {
			$feUserFields = array('name','email','telephone','fax','address','address2','city','state','zip','country','title');
			// add any extra fields through TS
			foreach (explode(',',$this->conf['feUserFields']) as $extraFeUserField) {
				if (!isset($feUserFields[trim($extraFeUserField)])) {
					$feUserFields[] = trim($extraFeUserField);
				}
			}
			foreach ($feUserFields as $uRec)
				foreach ($newRec as $field => $value) {
					if (!strcmp($uRec, $field)) {
						$feUserRec[$field] = $value;
						break;
					}
			}
		}
		// break up the name field to first and last name
		$nm = ($this->useFEUsers) ? $feUserRec['name'] : $newRec['name'];
		if (strlen($nm)) {
			$nameArray = t3lib_div::trimExplode(' ',$nm);
			if (count($nameArray)) {
				$firstNm = $nameArray[0];
				$lastNm = $nameArray[count($nameArray) - 1];
				if ($this->useFEUsers) {
					$feUserRec['first_name'] = $nameArray[0];
					if (count($nameArray) > 1)
						$feUserRec['last_name'] = $lastNm;
				}
				else {
					$newRec['first_name'] = $nameArray[0];
					if (count($nameArray)> 1)
						$newRec['last_name'] = $lastNm;
				}
			}
		}
		// only update the fields for staffInfo...
		$newRec = array_diff_assoc($newRec,$feUserRec);
		if (!$this->useFEUsers) {
			if (!$newRec['full_name']) {
				$newRec['full_name'] = $newRec['name'];
				unset($newRec['name']);
			}
		}
		// UPDATE THE STAFF RECORD TO DATABASE
		if ($staffID > 0) {
			// Update the Staff Directory Info record
			if (count($newRec)) {
				$where = 'uid=' . $staffID;
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->staffTable, $where, $newRec);
				if (mysql_error())	t3lib_div::debug(mysql_error(),'Could not update ' . $this->staffTable . ' with: ' . $newRec);
				
				// add department link if using staff department records
				if ($this->useStaffDeptRecords) {
					$mmRec = array();
					if ($thisStaffIndex) {
						$where2 = 'uid_local='.$this->curStaffList[$thisStaffIndex]['uid_local'] . ' AND uid_foreign='.$this->curStaffList[$thisStaffIndex]['uid_foreign'];
					}
					else {
						$where2 = 'uid_local='.$staffID;
					}
					$mmRec['uid_local'] = $staffID;
					$mmRec['uid_foreign'] = $newRec['department_rec'];
					$res2 = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_wecstaffdirectory_department_mm', $where2, $mmRec);
					if (mysql_error())	t3lib_div::debug(array(mysql_error(),'Could not insert tx_wecstaffdirectory_department_mm with: '.$mmRec));
				}				
			}
			// Update the FE User record if applicable
			if (count($feUserRec) && $feUserID) {
				$where = 'uid=' . $feUserID;
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', $where, $feUserRec);
				if (mysql_error())	t3lib_div::debug(mysql_error(),'Could not update \'fe_users\' with: ' . $feUserRec);
			}
			$params['tx_wecstaffdirectory_pi1']['msg'] = 1;
		}
		// CREATE NEW STAFF RECORD IN DATABASE
		else {
			if (!$this->useFEUsers) {
				$newRec = array_merge($newRec,$feUserRec);
				$feUserRec = array();
			}
			$newStaffID = $formVars['feuser_link'] ? $formVars['feuser_link'] : -1;
			if (count($feUserRec)) {
				// if link to existing staff, then just update otherwise create new fe_user entry
				if (!$newStaffID || ($newStaffID == -1)) {
					$rootPid = $GLOBALS['TSFE']->getStorageSiterootPids();
					$feUserRec['pid'] = $rootPid['_STORAGE_PID'];
					$feUserRec['tx_wecstaffdirectory_in'] = 1;
					$feUserRec['username'] = str_replace(' ','',strtolower($feUserRec['name']));
					$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('fe_users', $feUserRec);
					if (mysql_error())	t3lib_div::debug(array(mysql_error(),'Could not insert \'fe_users\' with: '.$feUserRec));
					$newStaffID = $GLOBALS['TYPO3_DB']->sql_insert_id();
				}
				else {
					$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.$newStaffID, $feUserRec);
					if (mysql_error())	t3lib_div::debug(array(mysql_error(),'Could not update \'fe_users\' with: '.$feUserRec));
				}
			}
			if (count($newRec)) {
				$staffID = $newStaffID;
				if ($staffID >= 0) $newRec['feuser_id'] = $staffID;
				$newRec['pid'] = $this->pid_list;
				if ($this->useStaffDeptRecords && isset($newRec['department_rec'])) {
					$deptRec = $newRec['department_rec'];
					$newRec['department_rec'] = 1;
				}
				
				$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($this->staffTable, $newRec);
				if (mysql_error())	t3lib_div::debug(array(mysql_error(),'Could not insert '.$this->staffTable.' with: '.$newRec));
				$staffID = $GLOBALS['TYPO3_DB']->sql_insert_id();
				
				// add department link if using staff department records
				if ($this->useStaffDeptRecords) { 
					$mmRec = array();
					$mmRec['uid_local'] = $staffID;
					$mmRec['uid_foreign'] = $deptRec;
					$res2 = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_wecstaffdirectory_department_mm', $mmRec);
					if (mysql_error())	t3lib_div::debug(array(mysql_error(),'Could not insert tx_wecstaffdirectory_department_mm with: '.$mmRec));
				}
			}
			$params['tx_wecstaffdirectory_pi1']['msg'] = 2;
		}
		// clear plugin cache
		$this->clearCache();
		// goto showing staff listing page
		$params['tx_wecstaffdirectory_pi1']['curstaff'] = $staffID;
		$staffPID = $this->config['single_pid'] ? $this->config['single_pid'] : $GLOBALS['TSFE']->id;
		$gotoURL = $this->pi_getPageLink($staffPID, '', $params);
		header('Location: '.t3lib_div::locationHeaderUrl($gotoURL));
	}
 	/**
	 * process delete request for staff page [cb]
	 *
	 * @param	array	$formVars	the form variables passed in from edit form
	 * @return	void
	*/
	function process_delete_form($formVars) {
		if (!($staffID = $formVars['delete'])) {
			$this->formErrorText =  $this->pi_getLL('bad_delete_form','You cannot delete this now');
			return false;
		}
		// check user authentication here...
		if (!($this->isAdministrator || ($this->userStaffUID && $this->config['staff_can_delete']))) {
			$this->formErrorText = $this->pi_getLL('bad_deleter','You cannot delete staff pages');
			return false;
		}
		if ((int) $formVars['delete'] <= 0) {
			$this->formErrorText = $this->pi_getLL('bad_delete_id','Invalid delete request');
			return false;
		}
		// read in form variables passed in
		$newRec = array();
		// update tstamp to show when deleted
		$newRec['tstamp'] = mktime();
		// set "deleted" flag
		$newRec['deleted'] = 1;
		// DELETE THE STAFF RECORD FROM DATABASE (just mark deleted)
		//------------------------------------------------------
		if (count($newRec)) {
			$where = 'uid='.$staffID;
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->staffTable, $where, $newRec);
			if (mysql_error())	t3lib_div::debug(array(mysql_error(),'Could not update '.$this->staffTable.' with: '.$newRec));
		}
		// clear plugin cache
		$this->clearCache();
		$params['tx_wecstaffdirectory_pi1']['msg'] = 3;
		$gotoURL = $this->pi_getPageLink($GLOBALS['TSFE']->id, '', $params);
		header('Location: '.t3lib_div::locationHeaderUrl($gotoURL));
	}
	/**
	 * Handle the uploading of images
	 *
	 * @param	string		$fieldName the field for the uploaded image
	 * @return	array		the array holding image info
	 */
	function uploadImages($fieldName) {
		$goodImageCount = 0;
		$fileArray = array();
		$numFiles = isset($_FILES[$this->prefixId]['name'][$fieldName]) && is_array($_FILES[$this->prefixId]['name'][$fieldName]) ? count($_FILES[$this->prefixId]['name'][$fieldName]) : isset($_FILES[$this->prefixId]['name'][$fieldName]);
		if (!$numFiles)
			return 0;
		require_once(PATH_t3lib.'class.t3lib_basicfilefunc.php');
		// go through each file and upload it
		for ($i = 0; $i < $numFiles; $i++ ) {
			$img = 0;
			$imgName    = ($numFiles > 1) ? $_FILES[$this->prefixId]['name'][$fieldName][$i] : $_FILES[$this->prefixId]['name'][$fieldName];
			$imgSize    = ($numFiles > 1) ? $_FILES[$this->prefixId]['size'][$fieldName][$i] : $_FILES[$this->prefixId]['size'][$fieldName];
			$imgType    = ($numFiles > 1) ? $_FILES[$this->prefixId]['type'][$fieldName][$i] : $_FILES[$this->prefixId]['type'][$fieldName];
			$imgTmpName = ($numFiles > 1) ? $_FILES[$this->prefixId]['tmp_name'][$fieldName][$i] : $_FILES[$this->prefixId]['tmp_name'][$fieldName];
			if ($imgSize && $imgTmpName) {
				$this->fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');
				$imgName = $this->fileFunc->cleanFileName($imgName);
				$imgPath  = PATH_site.$this->altImagePath;
				$imgUniqueName = $this->fileFunc->getUniqueName($imgName,$imgPath);
				move_uploaded_file($imgTmpName,$imgUniqueName);
				$imgPathInfo = pathinfo($imgUniqueName);
				array_push($fileArray,$imgPathInfo['basename']);
				$goodImageCount++;
			}
			else {
				array_push($fileArray,'');
			}
		}
		if ($goodImageCount == 0)
			return array();
		else
			return $fileArray;
	}
	/**
	 * Format string with general_stdWrap from configuration (borrowed from tt_news)
	 *
	 * @param	string		$string to wrap
	 * @return	string		wrapped string
	 */
	function formatStr($str) {
		$str = $this->html_entity_decode($str);
		if (is_array($this->conf['general_stdWrap.'])) {
			$str = $this->local_cObj->stdWrap($str, $this->conf['general_stdWrap.']);
		}
		return $str;
	}
    /**
     * Returns an image given by $TSconf
     *
     * @param string	$filename	the file name of image to get
     * @param array		$TSconf		the TypoScript configuration array
     * @return string	the image information
     */
    function getImage($filename,$TSconf)    {
    	if (!$TSconf) return false;
        list($theImage) = t3lib_div::trimExplode(',',$filename);
        if (strstr($theImage,'EXT:'))
        	$TSconf['file'] = $theImage;
        else
	        $TSconf['file'] = $this->altImagePath . $theImage;
		// let TYPO3 (ImageMagick + gdlib) do it's thing
        $img = $this->cObj->IMAGE($TSconf);
		// if empty image (because invalid file or file not exists anymore), then return nothing
		if (!$img || strstr($img,"src=\"\""))
			return false;
		// let's check to see if ImageMagick/GraphicMagick/gdlib is installed. If not, then set image width and height to original values
		$lastImage = $GLOBALS["TSFE"]->lastImageInfo;
		$setWd = (int) $TSconf['file.']['width'];
		$setHt = (int) $TSconf['file.']['height'];
		
		// if the specified ht/wd is different than the generated ht/wd, then IM installed
		$IM_installed = false;
		if (($setWd && ($lastImage[0] == $setWd)) || ($setHt && ($lastImage[1] == $setHt))) {
			$IM_installed = true;
		}
		else if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['image_processing'] && $GLOBALS['TYPO3_CONF_VARS']['GFX']['im']) {
			$IM_installed = true;
		}
		if (!$IM_installed) {
			$replaceWd = $setWd ? 'width="'.$setWd.'"' : '';
			$img = preg_replace('/width="[0-9]+"/i',$replaceWd,$img);
			$replaceHt = $setHt ? 'height="'.$setHt.'"' : '';
			$img = preg_replace('/height="[0-9]+"/i',$replaceHt,$img);
		}
		
		// return new image string <img...>
        return $img;
    }
	/**
	 * getConfigVal: Return the value from either plugin flexform, typoscript, or default value, in that order
	 *
	 * @param	object		$Obj: Parent object calling this function
	 * @param	string		$ffField: Field name of the flexform value
	 * @param	string		$ffSheet: Sheet name where flexform value is located
	 * @param	string		$TSfieldname: Property name of typoscript value
	 * @param	array		$lConf: TypoScript configuration array from local scope
	 * @param	mixed		$default: The default value to assign if no other values are assigned from TypoScript or Plugin Flexform
	 * @return	mixed		Configuration value found in any config, or default
	 */
	function getConfigVal( &$Obj, $ffField, $ffSheet, $TSfieldname='', $lConf='', $default = '' ) {
		if (!$lConf && $Obj->conf) $lConf = $Obj->conf;
		if (!$TSfieldname) $TSfieldname = $ffField;
		//	Retrieve values stored in flexform and typoscript
		$ffValue = $Obj->pi_getFFvalue($Obj->cObj->data['pi_flexform'], $ffField, $ffSheet);
		$tsValue = $lConf[$TSfieldname];
		//	Use flexform value if present, otherwise typoscript value
		$retVal = $ffValue ? $ffValue : $tsValue;
			//	Return value if found, otherwise default
		return $retVal ? $retVal : $default;
	}
	function html_entity_decode($str,$quoteStyle=ENT_COMPAT) {
		if (version_compare(phpversion(),"4.3.0",">="))
			return html_entity_decode($str,$quoteStyle,$GLOBALS['TSFE']->renderCharset);
	    // replace numeric entities
	    $string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $str);
	    $string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $str);
	    // replace literal entities
	    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
	    $trans_tbl = array_flip($trans_tbl);
	    return strtr($str, $trans_tbl);
	}
	/**
	 * Draws a map for the provided staff record.  Works with both staff directory records and frontend user records.
	 * @param		array		Database row from the staff directory table or frontend user table.
	 * @return		string		HTML output for a map.
	 */
	function drawMap($staffRecord) {
		// Only draw the map if the extension is loaded and have address info.
		if (t3lib_extMgm::isLoaded('wec_map') && $staffRecord['address'] && $staffRecord['city']) {
			// Pull some neccessary values out of the record and check extension config options.
			$confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wec_staffdirectory']);
			if($confArray['useFEUsers']) {
				// Get the address fields for a frontend user record
				$table = 'fe_users';
				$uid = $staffRecord['feuser_id'];
			} else {
				// Get the address fields for a staff directory record
				$table = 'tx_wecstaffdirectory_info';
				$uid = $staffRecord['uid'];
			}
			if ($staffRecord['name']) {
				$title = $staffRecord['name'];
			} else {
				$title = $staffRecord['first_name'].' '.$staffRecord['last_name'];
			}
			// Include Typoscript configuration options.
			$width = $this->conf['map.']['mapWidth'];
			$height = $this->conf['map.']['mapHeight'];
			$centerLat = $this->conf['map.']['centerLat'];
			$centerLong = $this->conf['map.']['centerLong'];
			$zoomLevel = $this->conf['map.']['zoomLevel'];
			$controlSize = $this->conf['map.']['controlSize'];
			$showOverviewMap = $this->conf['map.']['showOverviewMap'];
			$showMapType = $this->conf['map.']['showMapType'];
			$showScale = $this->conf['map.']['showScale'];
			$showInfoOnLoad = $this->conf['map.']['showInfoOnLoad'];
			$showDirections = $this->conf['map.']['showDirections'];
			$showWrittenDirections = $this->conf['map.']['showWrittenDirections'];
			$prefillAddress = $this->conf['map.']['prefillAddress'];
			// Create the map and give it a unique ID.
			include_once(t3lib_extMgm::extPath('wec_map').'map_service/google/class.tx_wecmap_map_google.php');
			$className=t3lib_div::makeInstanceClassName('tx_wecmap_map_google');
			$mapName = 'staff'.$uid;
			$map = new $className($apiKey, $width, $height, $centerLat, $centerLong, $zoomLevel, $mapName);
			// Evaluate config to see which map controls we need to show.
			if($controlSize == 'large') {
				$map->addControl('largeMap');
			} else if ($controlSize == 'small') {
				$map->addControl('smallMap');
			} else if ($controlSize == 'zoomonly') {
				$map->addControl('smallZoom');
			}
			if($showScale) $map->addControl('scale');
			if($showOverviewMap) $map->addControl('overviewMap');
			if($showMapType) $map->addControl('mapType');
			// Check whether to show the directions tab and/or prefill addresses and/or written directions.
			if($showDirections && $showWrittenDirections && $prefillAddress) $map->enableDirections(true, 'directions-'.$mapName);
			if($showDirections && $showWrittenDirections && !$prefillAddress) $map->enableDirections(false, '-directions-'.$mapName);
			if($showDirections && !$showWrittenDirections && $prefillAddress) $map->enableDirections(true);
			if($showDirections && !$showWrittenDirections && !$prefillAddress) $map->enableDirections();
			// See if we need to open the marker bubble on load.
			if($showInfoOnLoad) $map->showInfoOnLoad();
			// Add the marker for staff record.
			$map->addMarkerByTCA($table, $uid, '<h3>'.$title.'</h3>');
			// Finally draw the map.
			$map = $map->drawMap();
		} else {
			$map = '';
		}
		return $map;
	}
	/**
	*==================================================================================
	*  GetStrftime -- get strftime with locale conversion
	*
	*   @param	string		$format: format string for strftime
	*   @param	string		$content: data to format
	* 	@return formatted date string
	*==================================================================================
	*/
	function getStrftime($format,$content) {
		$content = strftime($format,$content);
		$tmp_charset = $conf['strftime.']['charset'] ? $conf['strftime.']['charset'] : $GLOBALS['TSFE']->localeCharset;
		if ($tmp_charset)	{
				$content = $GLOBALS['TSFE']->csConv($content,$tmp_charset);
		}
		return $content;
	}
	/**
	*==================================================================================
	*  clearCache -- clear cache for this extension only
	*
	* 	@return none
	*==================================================================================
	*/
	function clearCache() {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages', 'reg1=414');
	}
	
	/**
	*==================================================================================
	*  ctype_digit_new -- handle it better because of problems with PHP
	*
	* 	@return boolean
	*==================================================================================
	*/	
	function ctype_digit_new($str) {
	    return (is_string($str) || is_int($str) || is_float($str)) &&
	        preg_match('/^\d+\z/', $str);
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_staffdirectory/pi1/class.tx_wecstaffdirectory_pi1.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_staffdirectory/pi1/class.tx_wecstaffdirectory_pi1.php']);
}
?>
