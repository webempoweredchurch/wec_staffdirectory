<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_wecstaffdirectory_info=1
');

  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_wecstaffdirectory_pi1 = < plugin.tx_wecstaffdirectory_pi1.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_wecstaffdirectory_pi1.php','_pi1','list_type',1);

/* Include a custom userFunc for checking whether we are editing a form or not (change USER TO USER_INT) */
require_once(t3lib_extMgm::extPath('wec_staffdirectory') . 'pi1/class.tx_wecstaffdirectory_isNotCached.php');
?>