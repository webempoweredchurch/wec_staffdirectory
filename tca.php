<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_wecstaffdirectory_info"] = Array (
	"ctrl" => $TCA["tx_wecstaffdirectory_info"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,feuser_id,position_title,position_description,department,team,start_date,biography,news,photo_main,photos_etc,cellphone,fax,misc,display_order"
	),
	"feInterface" => $TCA["tx_wecstaffdirectory_info"]["feInterface"],
	"columns" => Array (
		'sys_language_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_wecstaffdirectory_info',
				'foreign_table_where' => 'AND tx_wecstaffdirectory_info.pid=###CURRENT_PID### AND tx_wecstaffdirectory_info.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (
			'config' => Array (
				'type' => 'passthrough'
			)
		),		
		"hidden" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.xml:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"feuser_id" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.feuser_id",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "fe_users",
				"foreign_table_where" => "AND fe_users.tx_wecstaffdirectory_in <> 0 AND fe_users.deleted=0 ORDER BY fe_users.name",
				"size" => 10,
				"autoSizeMax" => 10,
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"position_title" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.position_title",
			"config" => Array (
				"type" => "input",
				"size" => "32",
				"max" => "128",
			)
		),
		"position_description" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.position_description",
			"config" => Array (
				"type" => "text",
				"cols" => "40",
				"rows" => "4",
			)
		),
		"department" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.department",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "128",
			)
		),		
		"team" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.team",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "128",
			)
		),		
		"start_date" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.start_date",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0"
			)
		),
		"biography" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.biography",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
				"wizards" => Array(
					"_PADDING" => 2,
					"RTE" => Array(
						"notNewRecords" => 1,
						"RTEonly" => 1,
						"type" => "script",
						"title" => "Full screen Rich Text Editing|Formatteret redigering i hele vinduet",
						"icon" => "wizard_rte2.gif",
						"script" => "wizard_rte.php",
					),
				),
			)
		),
		"news" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.news",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
				"wizards" => Array(
					"_PADDING" => 2,
					"RTE" => Array(
						"notNewRecords" => 1,
						"RTEonly" => 1,
						"type" => "script",
						"title" => "Full screen Rich Text Editing|Formatteret redigering i hele vinduet",
						"icon" => "wizard_rte2.gif",
						"script" => "wizard_rte.php",
					),
				),
			)
		),
		"photo_main" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.photo_main",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"],
				"max_size" => 1000,
				"uploadfolder" => "uploads/tx_wecstaffdirectory",
				"show_thumbs" => 1,
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"photos_etc" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.photos_etc",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "gif,png,jpeg,jpg",
				"max_size" => 500,
				"uploadfolder" => "uploads/tx_wecstaffdirectory",
				"size" => 3,
				"minitems" => 0,
				"maxitems" => 3,
			)
		),
		"cellphone" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.cellphone",
			"config" => Array (
				"type" => "input",
				"size" => "10",
				"max" => "24",
			)
		),
		"fax" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.fax",
			"config" => Array (
				"type" => "input",
				"size" => "10",
				"max" => "24",
			)
		),		
		"social_contact1" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.social_contact1",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"max" => "48",
			)
		),		
		"social_contact2" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.social_contact2",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"max" => "48",
			)
		),
		"social_contact3" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.social_contact3",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"max" => "48",
			)
		),				
		"misc" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.misc",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "128",
			)
		),
		"display_order" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_info.display_order",
			"config" => Array (
				"type" => "input",
				"size" => "5",
				"max" =>  "8",
			)
		),
		't3ver_label' => Array (
			'displayCond' => 'FIELD:t3ver_label:REQ:true',
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.versionLabel',
			'config' => Array (
				'type'=>'none',
				'cols' => 27
			)
		),		
	),
	"types" => Array (
		"0" => Array("showitem" => "sys_language_uid;;;;3-3-3, l18n_parent, l18n_diffsource, hidden, feuser_id, position_title, position_description, department, team, start_date, social_contact1, social_contact2, social_contact3, biography;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|image]:rte_transform[mode=ts], news;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|image]:rte_transform[mode=ts], photo_main, photos_etc, cellphone, fax, misc, display_order")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "gender,first_name,last_name,title"),
		"2" => Array("showitem" => "address2,city,zone,zip,country"),
		"3" => Array("showitem" => "l18n_parent,l18n_diffsource"),
	),
);

$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wec_staffdirectory']);
if (!$confArr['useFEUsers']) {
	unset($TCA["tx_wecstaffdirectory_info"]["columns"]["feuser_id"]);
	$showitemStr = $TCA["tx_wecstaffdirectory_info"]["types"]["0"]["showitem"];
	$showitemStr = str_replace('feuser_id, ','',$showitemStr);
	$TCA["tx_wecstaffdirectory_info"]["types"]["0"]["showitem"] = $showitemStr;
}
if ($confArr['useStaffDeptRecords']) {
	unset($TCA["tx_wecstaffdirectory_info"]["columns"]["department"]);
	$showitemStr = $TCA["tx_wecstaffdirectory_info"]["types"]["0"]["showitem"];
	$showitemStr = str_replace('department, ','',$showitemStr);
	$TCA["tx_wecstaffdirectory_info"]["types"]["0"]["showitem"] = $showitemStr;
}

$TCA["tx_wecstaffdirectory_department"] = Array (
	"ctrl" => $TCA["tx_wecstaffdirectory_department"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,department_name,description,parent_department,display_order,hidden"
	),
	"feInterface" => $TCA["tx_wecstaffdirectory_department"]["feInterface"],
	"columns" => Array (
		'sys_language_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_wecstaffdirectory_department',
				'foreign_table_where' => 'AND tx_wecstaffdirectory_department.pid=###CURRENT_PID### AND tx_wecstaffdirectory_department.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (
			'config' => Array (
				'type' => 'passthrough'
			)
		),		
		"hidden" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.xml:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"department_name" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_department.department_name",
			"config" => Array (
				"type" => "input",
				"size" => "32",
				"max" => "128",
			)
		),
		"description" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_department.description",
			"config" => Array (
				"type" => "text",
				"cols" => "40",
				"rows" => "4",
			)
		),
		"parent_department" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_department.parent_department",
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'tx_wecstaffdirectory_department',
				"foreign_table_where" => "ORDER BY department_name",
				"size" => 1,
				"autoSizeMax" => 10,
				"minitems" => 1,
				"maxitems" => 1,
				'items' => Array (
					Array('LLL:EXT:wec_staffdirectory/locallang_db.xml:tx_wecstaffdirectory_department.department_none', 0),
				),				
			),
		),
		't3ver_label' => Array (
			'displayCond' => 'FIELD:t3ver_label:REQ:true',
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.versionLabel',
			'config' => Array (
				'type'=>'none',
				'cols' => 27
			)
		),		
	),
	"types" => Array (
		"0" => Array("showitem" => "sys_language_uid;;;;3-3-3, l18n_parent, l18n_diffsource, hidden, department_name, description, parent_department, display_order")
	),
	"palettes" => Array (
		"3" => Array("showitem" => "l18n_parent,l18n_diffsource"),		
	),
);
				
?>