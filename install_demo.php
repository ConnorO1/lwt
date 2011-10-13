<?php

/**************************************************************
"Learning with Texts" (LWT) is released into the Public Domain.
This applies worldwide.
In case this is not legally possible, any entity is granted the
right to use this work for any purpose, without any conditions,
unless such conditions are required by law.

Developed by J. Pierre in 2011.
***************************************************************/

/**************************************************************
Call: install_demo.php
Install LWT Demo Database
***************************************************************/

require 'lwt-startup.php';

$message = '';

// RESTORE DEMO

if (isset($_REQUEST['install'])) {
	$file = getcwd() . '/install_demo_db.sql.gz';
	if ( file_exists($file) ) {
		$handle = gzopen ($file, "r");
		if ($handle === FALSE) {
			$message = "Error: File ' . $file . ' could not be opened";
		} // $handle not OK
		else { // $handle OK
			$lines = 0;
			$ok = 0;
			$errors = 0;
			$drops = 0;
			$inserts = 0;
			$creates = 0;
			$start = 1;
			while (! gzeof($handle)) {
				$sql_line = trim(
					str_replace("\r","",
					str_replace("\n","",
					gzgets($handle, 99999))));
				if ($sql_line != "") {
					if($start) {
						if (strpos($sql_line,"-- lwt-backup-") === false ) {
							$message = "Error: Invalid file (possibly not created by LWT backup)";
							break;
						}
						$start = 0;
					}
					if(strpos($sql_line, "--") === false) {
						//echo tohtml($sql_line) . "<br />"; $res=TRUE;
						$res = mysql_query($sql_line);
						$lines++;
						if ($res == FALSE) $errors++;
						else {
							$ok++;
							if (substr($sql_line,0,11) == "INSERT INTO") $inserts++;
							elseif (substr($sql_line,0,10) == "DROP TABLE") $drops++;
							elseif (substr($sql_line,0,12) == "CREATE TABLE") $creates++;
						}
					}
				}
			} // while (! feof($handle))
			gzclose ($handle);
			if ($errors == 0) {
				optimizedb();
				$message = "Success: Demo Database restored - " .
				$lines . " queries - " . $ok . " successful (" . $drops . "/" . $creates . " tables dropped/created, " . $inserts . " records added), " . $errors . " failed.";
			} else {
				$message = "Error: Demo Database NOT restored - " .
				$lines . " queries - " . $ok . " successful (" . $drops . "/" . $creates . " tables dropped/created, " . $inserts . " records added), " . $errors . " failed.";
			}
		} // $handle OK
	} // restore file specified
	else {
		$message = "Error: File ' . $file . ' does not exist";
	}
}

pagestart('Install LWT Demo Database',true);

echo error_message_with_hide($message,1);

$langcnt = get_first_value('select count(*) as value from languages');

?>
<form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<table class="tab3" cellspacing="0" cellpadding="5">
<tr>
<th class="th1 center">Install Demo</th>
<td class="td1">
<p class="smallgray2">
The database <i><?php echo tohtml($dbname); ?></i> will be replaced by the LWT demo database.

<?php
if ($langcnt > 0 ) {
	?>
	<br /><b>Please be careful - the existent database will be overwritten!</b>
	<?php
}
?>

</p>
<p class="right">&nbsp;<br />
<input type="submit" name="install" value="Install LWT demo database" /></p>
</td>
</tr>
<tr>
<td class="td1 right" colspan="2">
<input type="button" value="&lt;&lt; Back to LWT Main Menu" onclick="location.href='index.php';" /></td>
</tr>
</table>
</form>

<?php

pageend();

?>