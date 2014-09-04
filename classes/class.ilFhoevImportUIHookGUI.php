<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/UIComponent/classes/class.ilUIHookPluginGUI.php';

/**
 * Description of class
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilFhoevImportUIHookGUI extends ilUIHookPluginGUI 
{
	function getHTML($a_comp, $a_part, $a_par = array())
	{
		return array(
			"mode" => ilUIHookPluginGUI::KEEP,
			"html" => ""
		);
	}

}
?>
