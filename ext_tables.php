<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wec_staffdirectory']);

require_once(t3lib_extMgm::extPath('wec_staffdirectory') . "pi1/class.tx_wecstaffdirectory_itemsProcFunc.php");

t3lib_extMgm::allowTableOnStandardPages("tx_wecstaffdirectory_info");
t3lib_extMgm::addToInsertRecords("tx_wecstaffdirectory_info");

$TCA["tx_wecstaffdirectory_info"] = Array (
	"ctrl" => Array (
		'title' => 'LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info',
	 	'label' => ($confArr['useFEUsers'] ? 'feuser_id' : 'full_name'),
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		"languageField" => "sys_language_uid",
		"transOrigPointerField" => "l18n_parent",
		"transOrigDiffSourceField" => "l18n_diffsource",		
		"sortby" => "sorting",
		"delete" => "deleted",
		"enablecolumns" => Array (
			"disabled" => "hidden",
		),
		"versioningWS" => TRUE,
		"origUid" => "t3_origuid",
		"shadowColumnsForNewPlaceholders" => "sys_language_uid,l18n_parent",
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."res/icon_tx_wecstaffdirectory_info.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "full_name, sys_language_uid, l18n_parent, l18n_diffsource, position_title, position_description, department, start_date, biography, news, photo_main, photos_etc, cellphone, fax, social_contact1, social_contact2, social_contact3, misc, display_order, hidden",
	)
);


$TCA["tx_wecstaffdirectory_department"] = Array (
	"ctrl" => Array (
		'title' => 'LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_department',
	 	'label' => 'department_name',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		"languageField" => "sys_language_uid",
		"transOrigPointerField" => "l18n_parent",
		"transOrigDiffSourceField" => "l18n_diffsource",		
		"sortby" => "sorting",
		"delete" => "deleted",
		"enablecolumns" => Array (
			"disabled" => "hidden",
		),
		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',		
		"shadowColumnsForNewPlaceholders" => "sys_language_uid,l18n_parent",
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."res/icon_tx_wecstaffdirectory_department.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "department_name, sys_language_uid, l18n_parent, l18n_diffsource, description, parent_department, display_order, hidden",
	)
);

t3lib_extMgm::allowTableOnStandardPages("tx_wecstaffdirectory_department");
t3lib_extMgm::addToInsertRecords("tx_wecstaffdirectory_department");

//-------------------------------------------------------
require_once(t3lib_extMgm::extPath($_EXTKEY).'class.tx_wecstaffdirectory_labels.php');

// enable label_userFunc only for TYPO3 v 4.1 and higher
if (t3lib_div::int_from_ver(TYPO3_version) >= 4001000) {
	$TCA['tx_wecstaffdirectory_info']['ctrl']['label_userFunc']="tx_wecstaffdirectory_labels->getStaffLabel";
}

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages,recursive';

t3lib_extMgm::addPlugin(Array('LLL:EXT:wec_staffdirectory/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');

if (TYPO3_MODE=="BE")	$TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["tx_wecstaffdirectory_pi1_wizicon"] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_wecstaffdirectory_pi1_wizicon.php';

$tempColumns = Array (
	"tx_wecstaffdirectory_in" => Array (
		"exclude" => 1,
		"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:fe_users.tx_wecstaffdirectory_in",
		"config" => Array (
			"type" => "check",
			"default" => 0
		)
	),
	'country' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:lang/locallang_general.php:LGL.country',
		'config' => Array (
			'type' => 'input',
			'size' => '10',
			'max' => '30',
			'eval' => 'trim',
			'default' => ''
		)
	),
	'zone' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:wec_staffdirectory/locallang_db.xml:fe_users.zone',
		'config' => Array (
			'type' => 'input',
			'size' => '10',
			'max' => '30',
			'eval' => 'trim',
			'default' => ''
		)
	),
	'first_name' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:wec_staffdirectory/locallang_db.xml:fe_users.first_name',
		'config' => Array (
			'type' => 'input',
			'size' => '15',
			'max' => '40',
			'eval' => 'trim',
			'default' => ''
		)
	),
	'last_name' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:wec_staffdirectory/locallang_db.xml:fe_users.last_name',
		'config' => Array (
			'type' => 'input',
			'size' => '15',
			'max' => '40',
			'eval' => 'trim',
			'default' => ''
		)
	),
	'gender' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:wec_staffdirectory/locallang_db.xml:fe_users.gender',
		'config' => Array (
			'type' => 'radio',
			'items' => Array (
				Array('LLL:EXT:wec_staffdirectory/locallang_db.xml:fe_users.gender.I.0', '0'),
				Array('LLL:EXT:wec_staffdirectory/locallang_db.xml:fe_users.gender.I.1', '1'),
			),
		)
	),	
	'title' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title_person',
		'config' => Array (
			'type' => 'input',
			'size' => '10',
			'eval' => 'trim',
			'max' => '20'
		)
	),
);

$TCA["tt_content"]["types"]["list"]["subtypes_addlist"][$_EXTKEY."_pi1"]="pi_flexform";
	// switch the XML files for the FlexForm depending on if "use StoragePid"(general record Storage Page) is set or not.
if ($confArr['useStaffDeptRecords']) {
	t3lib_extMgm::addPiFlexFormValue($_EXTKEY."_pi1", "FILE:EXT:wec_staffdirectory/flexform_ds_dept.xml");
} else {
	t3lib_extMgm::addPiFlexFormValue($_EXTKEY."_pi1", "FILE:EXT:wec_staffdirectory/flexform_ds.xml");
}


t3lib_div::loadTCA("fe_users");
t3lib_extMgm::addTCAcolumns("fe_users",$tempColumns,1);
//t3lib_extMgm::addToAllTCAtypes("fe_users","tx_wecstaffdirectory_in;;;;1-1-1",false,"after:fe_users.tabs.extended");
t3lib_extMgm::addToAllTCAtypes("fe_users","tx_wecstaffdirectory_in;;;;1-1-1");

// fix of sr_feuser_register v2.5.7 - v2.5.8 (does not display first name, last name in fe_user record)
//if ((t3lib_extMgm::isLoaded('sr_feuser_register'))  && 
//   ($feuserStr = $TCA['fe_users']['palettes']['1']['showitem']) && (strpos($feuserStr,'gender,first_name,last_name,title') === FALSE)) {
//	$TCA['fe_users']['palettes']['1']['showitem'] = str_replace('title', 'gender,first_name,last_name,title', $TCA['fe_users']['palettes']['1']['showitem']);
//}

$tempColumns2 = Array (
	'full_name' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:lang/locallang_general.php:LGL.name',
		'config' => Array (
			'type' => 'input',
			'size' => '20',
			'max' => '40',
			'eval' => '',
			'default' => ''
		)
	),
	'address' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:lang/locallang_general.php:LGL.address',
		'config' => Array (
			'type' => 'input',
			'size' => '30',
			'max' => '40',
			'eval' => '',
			'default' => ''
		)
	),
	'address2' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.address2',
		'config' => Array (
			'type' => 'input',
			'size' => '30',
			'max' => '40',
			'eval' => '',
			'default' => ''
		)
	),	
	'city' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:lang/locallang_general.php:LGL.city',
		'config' => Array (
			'type' => 'input',
			'size' => '15',
			'max' => '30',
			'eval' => 'trim',
			'default' => ''
		)
	),
	'zip' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:lang/locallang_general.php:LGL.zip',
		'config' => Array (
			'type' => 'input',
			'size' => '10',
			'max' => '20',
			'eval' => 'trim',
			'default' => ''
		)
	),
	'email' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:lang/locallang_general.php:LGL.email',
		'config' => Array (
			'type' => 'input',
			'size' => '20',
			'max' => '40',
			'eval' => 'trim',
			'default' => ''
		)
	),
	'telephone' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:lang/locallang_general.php:LGL.phone',
		'config' => Array (
			'type' => 'input',
			'size' => '15',
			'max' => '20',
			'eval' => 'trim',
			'default' => ''
		)
	)
);

if ($confArr['useFEUsers']) {
	unset($tempColumns["tx_wecstaffdirectory_in"]); // remove from wecstaffdirectory_info
	t3lib_extMgm::addTCAcolumns("tx_wecstaffdirectory_info",$tempColumns,1);
}
else {
	$tempColumnsMerged = array_merge($tempColumns,$tempColumns2);
	unset($tempColumnsMerged["tx_wecstaffdirectory_in"]); // remove from wecstaffdirectory_info
	t3lib_extMgm::addTCAcolumns("tx_wecstaffdirectory_info",$tempColumnsMerged);
	t3lib_extMgm::addToAllTCAtypes("tx_wecstaffdirectory_info","full_name;;1,address;;2,telephone,email",0,"position_title");
}

$tempDeptRecord =  Array (
	"department_rec" => Array (
		"exclude" => 1,
		"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.department",
		"config" => Array (
			"type" => "select",
			'foreign_table' => 'tx_wecstaffdirectory_department',
			'foreign_table_where' => 'AND (tx_wecstaffdirectory_department.pid=###CURRENT_PID### OR tx_wecstaffdirectory_department.pid=###STORAGE_PID###) ORDER BY tx_wecstaffdirectory_department.sorting',
			"size" => 10,
			"autoSizeMax" => 10,
			"minitems" => 0,
			"maxitems" => 50,
			'MM' => 'tx_wecstaffdirectory_department_mm',
		),
	)
);

if ($confArr['useStaffDeptRecords']) {
	t3lib_extMgm::addTCAcolumns("tx_wecstaffdirectory_info",$tempDeptRecord,1);
	$TCA['tx_wecstaffdirectory_info']['interface']['showRecordFieldList'] .= ',department_rec';
	t3lib_extMgm::addToAllTCAtypes("tx_wecstaffdirectory_info","department_rec",0,"team");
}


// add wec_map support
$TCA['tx_wecstaffdirectory_info']['ctrl']['EXT']['wec_map'] = array (
	'isMappable' => 1,
	'addressFields' => array (
		'street' => 'address',
		'city' => 'city',
		'state' => 'zone',
		'zip' => 'zip',
		'country' => 'country',
	),
);

if(t3lib_extMgm::isLoaded('wec_map')) {
	require_once(t3lib_extMgm::extPath('wec_map').'class.tx_wecmap_backend.php');
	// If we want to show a map in frontend user records, add it to the TCA 
	if($confArr['showMap'] && !$confArr['useFEUsers']) {
		$mapTCA = array (
			'tx_wecmap_map' => array (		
				'exclude' => 1,		
				'label' => 'LLL:EXT:wec_map/locallang_db.xml:berecord_maplabel',		
				'config' => array (
					'type' => 'passthrough',
					'form_type' => 'user',
					'userFunc' => 'tx_wecmap_backend->drawMap',
				),
			),
		);
		
		t3lib_extMgm::addTCAcolumns('tx_wecstaffdirectory_info', $mapTCA, 1);
		$TCA['tx_wecstaffdirectory_info']['interface']['showRecordFieldList'] .= ',tx_wecmap_map';
		t3lib_extMgm::addToAllTCAtypes('tx_wecstaffdirectory_info', '--div--;LLL:EXT:wec_map/locallang_db.xml:berecord_maplabel,tx_wecmap_map');

		// If we want to show the geocoding status in frontend user records, add it to the TCA
		$geocodeTCA = array (
			'tx_wecmap_geocode' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:wec_map/locallang_db.xml:berecord_geocodelabel',
				'config' => array(
					'type' => 'passthrough',
					'form_type' => 'user',
					'userFunc' => 'tx_wecmap_backend->checkGeocodeStatus',
				),
			),
		);

		t3lib_extMgm::addTCAcolumns('tx_wecstaffdirectory_info', $geocodeTCA, 1);
		$TCA['tx_wecstaffdirectory_info']['interface']['showRecordFieldList'] .= ',tx_wecmap_geocode';
		t3lib_extMgm::addToAllTCAtypes('tx_wecstaffdirectory_info', 'tx_wecmap_geocode');
	}
}


t3lib_extMgm::addStaticFile($_EXTKEY,'static/ts/','WEC Staff Directory (old) template');
t3lib_extMgm::addStaticFile($_EXTKEY,'static/tsnew/','WEC Staff Directory template');

?>