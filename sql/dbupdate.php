<#1>
<?php
	
	$query = 'UPDATE object_data SET '.
			'import_id = antrago_id '.
			'WHERE antrago_id > 0';
	$res = $ilDB->manipulate($query);
?>
