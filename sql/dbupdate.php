<#1>
<?php
if($ilDB->tableColumnExists("object_data", "antrago_id"))
{
	$query = 'UPDATE object_data SET '.
		'import_id = antrago_id '.
		'WHERE antrago_id > 0';
	$res = $ilDB->manipulate($query);
}

?>
<#2>
<?php

$query = "DELETE FROM ctrl_classfile WHERE".$ilDB->like('plugin_path','text', './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/FhoevImport');
$res = $ilDB->manipulate($query);

?>
