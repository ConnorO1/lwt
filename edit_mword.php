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
Call: edit_mword.php?....
      ... op=Save ... do insert new
      ... op=Change ... do update
      ... tid=[textid]&ord=[textpos]&wid=[wordid] ... edit
      ... tid=[textid]&ord=[textpos]&txt=[word] ... new or edit
Edit/New Multi-word term (expression)
***************************************************************/

require 'lwt-startup.php';

$translation_raw = repl_tab_nl(getreq("WoTranslation"));
if ( $translation_raw == '' ) $translation = '*';
else $translation = $translation_raw;

// INS/UPD

if (isset($_REQUEST['op'])) {

	$textlc = trim(prepare_textdata($_REQUEST["WoTextLC"]));
	$text = trim(prepare_textdata($_REQUEST["WoText"]));

	if (mb_strtolower($text, 'UTF-8') == $textlc) {

		// INSERT

		if ($_REQUEST['op'] == 'Save') {

			$titeltext = "New Term: " . tohtml(prepare_textdata($_REQUEST["WoTextLC"]));
			pagestart_nobody($titeltext);
			echo '<h4><span class="bigger">' . $titeltext . '</span></h4>';

			$message = runsql('insert into words (WoLgID, WoTextLC, WoText, ' .
				'WoStatus, WoTranslation, WoSentence, WoRomanization, WoStatusChanged,' .  make_score_random_insert_update('iv') . ') values( ' .
				$_REQUEST["WoLgID"] . ', ' .
				db_text_prepare($_REQUEST["WoTextLC"]) . ', ' .
				db_text_prepare($_REQUEST["WoText"]) . ', ' .
				$_REQUEST["WoStatus"] . ', ' .
				db_text_prepare($translation) . ', ' .
				db_text_prepare(repl_tab_nl($_REQUEST["WoSentence"])) . ', ' .
				db_text_prepare($_REQUEST["WoRomanization"]) . ', NOW(), ' .
make_score_random_insert_update('id') . ')', "Term saved");
			$wid = get_last_key();

			$hex = strToClassName(prepare_textdata($_REQUEST["WoTextLC"]));


		} // $_REQUEST['op'] == 'Save'

		// UPDATE

		else {  // $_REQUEST['op'] != 'Save'

			$titeltext = "Edit Term: " . tohtml(prepare_textdata($_REQUEST["WoTextLC"]));
			pagestart_nobody($titeltext);
			echo '<h4><span class="bigger">' . $titeltext . '</span></h4>';

			$oldstatus = $_REQUEST["WoOldStatus"];
			$newstatus = $_REQUEST["WoStatus"];
			$xx = '';
			if ($oldstatus != $newstatus) $xx = ', WoStatus = ' .	$newstatus . ', WoStatusChanged = NOW()';

			$message = runsql('update words set WoText = ' .
			db_text_prepare($_REQUEST["WoText"]) . ', WoTranslation = ' .
			db_text_prepare($translation) . ', WoSentence = ' .
			db_text_prepare(repl_tab_nl($_REQUEST["WoSentence"])) . ', WoRomanization = ' .
			db_text_prepare($_REQUEST["WoRomanization"]) . $xx . ',' . make_score_random_insert_update('u') . ' where WoID = ' . $_REQUEST["WoID"], "Updated");

			$wid = $_REQUEST["WoID"];

		} // $_REQUEST['op'] != 'Save'

		saveWordTags($wid);

	} // (mb_strtolower($text, 'UTF-8') == $textlc)

	else { // (mb_strtolower($text, 'UTF-8') != $textlc)

		$titeltext = "New/Edit Term: " . tohtml(prepare_textdata($_REQUEST["WoTextLC"]));
		pagestart_nobody($titeltext);
		echo '<h4><span class="bigger">' . $titeltext . '</span></h4>';
		$message = 'Error: Term in lowercase must be exactly = "' . $textlc . '", please go back and correct this!';
		echo error_message_with_hide($message,0);
		pageend();
		exit();

	}

	?>

	<p>OK: <?php echo tohtml($message); ?></p>

<script type="text/javascript">
//<![CDATA[
var context = window.parent.frames['l'].document;
var contexth = window.parent.frames['h'].document;
var woid = <?php echo prepare_textdata_js($wid); ?>;
var status = <?php echo prepare_textdata_js($_REQUEST["WoStatus"]); ?>;
var trans = <?php echo prepare_textdata_js($translation . getWordTagList($wid,' ',1,0)); ?>;
var roman = <?php echo prepare_textdata_js($_REQUEST["WoRomanization"]); ?>;
var title = make_tooltip(<?php echo prepare_textdata_js($_REQUEST["WoText"]); ?>,trans,roman,status);
<?php
	if ($_REQUEST['op'] == 'Save') {
		// new
		$showAll = getSetting('showallwords');
		$showAll = ($showAll == '' ? 1 : (((int) $showAll != 0) ? 1 : 0));
?>
$('.TERM<?php echo $hex; ?>', context).removeClass('hide').addClass('word' + woid + ' ' + 'status' + status).attr('data_trans',trans).attr('data_rom',roman).attr('data_status',status).attr('data_wid',woid).attr('title',title);
$('#learnstatus', contexth).html('<?php echo texttodocount2($_REQUEST['tid']); ?>');
<?php
		if (! $showAll) echo refreshText($text,$_REQUEST['tid']);
?>
<?php
	} else {
?>
$('.word' + woid, context).attr('data_trans',trans).attr('data_rom',roman).attr('title',title).removeClass('status<?php echo $_REQUEST['WoOldStatus']; ?>').addClass('status' + status).attr('data_status',status);
$('#learnstatus', contexth).html('<?php echo texttodocount2($_REQUEST['tid']); ?>');
<?php
	}
?>
window.parent.frames['l'].focus();
window.parent.frames['l'].setTimeout('cClick()', 100);
//]]>
</script>

<?php

} // if (isset($_REQUEST['op']))

else {  // if (! isset($_REQUEST['op']))

	// edit_mword.php?tid=..&ord=..&wid=..  ODER  edit_mword.php?tid=..&ord=..&txt=..

	$wid = getreq('wid');

	if ($wid == '') {
		$lang = get_first_value("select TxLgID as value from texts where TxID = " . $_REQUEST['tid']);
		$term = prepare_textdata(getreq('txt'));
		$termlc =	mb_strtolower($term, 'UTF-8');

		$wid = get_first_value("select WoID as value from words where WoLgID = " . $lang . " and WoTextLC = " . db_text_prepare($termlc));
		if (isset($wid)) $term = get_first_value("select WoText as value from words where WoID = " . $wid);

	} else {

		$sql = 'select WoText, WoLgID from words where WoID = ' . $wid;
		$res = mysql_query($sql);
		if ($res == FALSE) die("Invalid Query: $sql");
		$record = mysql_fetch_assoc($res);
		if ( $record ) {
			$term = $record['WoText'];
			$lang = $record['WoLgID'];
		} else {
			die("Error: No results");
		}
		mysql_free_result($res);
		$termlc =	mb_strtolower($term, 'UTF-8');

	}

	$neu = (isset($wid) == FALSE);

	$titeltext = ($neu ? "New Term" : "Edit Term") . ": " . $term;
	pagestart_nobody($titeltext);
	$scrdir = getScriptDirectionTag($lang);

	// NEW

	if ($neu) {

		$seid = get_first_value("select TiSeID as value from textitems where TiTxID = " . $_REQUEST['tid'] . " and TiOrder = " . $_REQUEST['ord']);
		$sent = getSentence($seid, $termlc, (int) getSettingWithDefault('set-term-sentence-count'));

		?>

		<form name="newword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<input type="hidden" name="WoLgID" value="<?php echo $lang; ?>" />
		<input type="hidden" name="WoTextLC" value="<?php echo tohtml($termlc); ?>" />
		<input type="hidden" name="tid" value="<?php echo $_REQUEST['tid']; ?>" />
		<input type="hidden" name="ord" value="<?php echo $_REQUEST['ord']; ?>" />
		<table class="tab2" cellspacing="0" cellpadding="5">
		<tr title="Only change uppercase/lowercase!">
		<td class="td1 right"><b>New Term:</b></td>
		<td class="td1"><input <?php echo $scrdir; ?> class="notempty" type="text" name="WoText" value="<?php echo tohtml($term); ?>" maxlength="250" size="35" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
		</tr>
		<tr>
		<td class="td1 right">Translation:</td>
		<td class="td1"><textarea name="WoTranslation" class="setfocus textarea-noreturn checklength" data_maxlength="500" data_info="Translation" cols="35" rows="3"></textarea></td>
		</tr>
		<tr>
		<td class="td1 right">Tags:</td>
		<td class="td1">
		<?php echo getWordTags(0); ?>
		</td>
		</tr>
		<tr>
		<td class="td1 right">Romaniz.:</td>
		<td class="td1"><input type="text" name="WoRomanization" value="" maxlength="100" size="35" /></td>
		</tr>
		<tr>
		<td class="td1 right">Sentence<br />Term in {...}:</td>
		<td class="td1"><textarea <?php echo $scrdir; ?> name="WoSentence" class="textarea-noreturn checklength" data_maxlength="1000" data_info="Sentence" cols="35" rows="3"><?php echo tohtml(repl_tab_nl($sent[1])); ?></textarea></td>
		</tr>
		<tr>
		<td class="td1 right">Status:</td>
		<td class="td1">
		<?php echo get_wordstatus_radiooptions(1); ?>
		</td>
		</tr>
		<tr>
		<tr>
		<td class="td1 right" colspan="2">
		<?php echo createDictLinksInEditWin($lang,$term,'document.forms[0].WoSentence',1); ?>
		&nbsp; &nbsp; &nbsp;
		<input type="submit" name="op" value="Save" /></td>
		</tr>
		</table>
		</form>
		<div id="exsent"><span class="click" onclick="do_ajax_show_sentences(<?php echo $lang; ?>, <?php echo prepare_textdata_js($termlc) . ', ' . prepare_textdata_js("document.forms['newword'].WoSentence"); ?>);"><img src="icn/sticky-notes-stack.png" title="Show Sentences" alt="Show Sentences" /> Show Sentences</span></div>
		<?php
	}

	// CHG

	else {

		$sql = 'select WoTranslation, WoSentence, WoRomanization, WoStatus from words where WoID = ' . $wid;
		$res = mysql_query($sql);
		if ($res == FALSE) die("Invalid Query: $sql");
		if ($record = mysql_fetch_assoc($res)) {

			$status = $record['WoStatus'];
			if ($status >= 98) $status = 1;
			$sentence = repl_tab_nl($record['WoSentence']);
			if ($sentence == '') {
				$seid = get_first_value("select TiSeID as value from textitems where TiTxID = " . $_REQUEST['tid'] . " and TiOrder = " . $_REQUEST['ord']);
				$sent = getSentence($seid, $termlc, (int) getSettingWithDefault('set-term-sentence-count'));
				$sentence = repl_tab_nl($sent[1]);
			}
			$transl = repl_tab_nl($record['WoTranslation']);
			if($transl == '*') $transl='';
			?>

			<form name="editword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<input type="hidden" name="WoID" value="<?php echo $wid; ?>" />
			<input type="hidden" name="WoOldStatus" value="<?php echo $record['WoStatus']; ?>" />
			<input type="hidden" name="WoStatus" value="<?php echo $status; ?>" />
			<input type="hidden" name="WoTextLC" value="<?php echo tohtml($termlc); ?>" />
			<input type="hidden" name="tid" value="<?php echo $_REQUEST['tid']; ?>" />
			<input type="hidden" name="ord" value="<?php echo $_REQUEST['ord']; ?>" />
			<table class="tab2" cellspacing="0" cellpadding="5">
			<tr title="Only change uppercase/lowercase!">
			<td class="td1 right"><b>Edit Term:</b></td>
			<td class="td1"><input <?php echo $scrdir; ?> class="notempty" type="text" name="WoText" value="<?php echo tohtml($term); ?>" maxlength="250" size="35" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
			</tr>
			<tr>
			<td class="td1 right">Translation:</td>
			<td class="td1"><textarea name="WoTranslation" class="setfocus textarea-noreturn checklength" data_maxlength="500" data_info="Translation" cols="35" rows="3"><?php echo tohtml($transl); ?></textarea></td>
			</tr>
			<tr>
			<td class="td1 right">Tags:</td>
			<td class="td1">
			<?php echo getWordTags($wid); ?>
			</td>
			</tr>
			<tr>
			<td class="td1 right">Romaniz.:</td>
			<td class="td1"><input type="text" name="WoRomanization" maxlength="100" size="35"
			value="<?php echo tohtml($record['WoRomanization']); ?>" /></td>
			</tr>
			<tr>
			<td class="td1 right">Sentence<br />Term in {...}:</td>
			<td class="td1"><textarea <?php echo $scrdir; ?> name="WoSentence" class="textarea-noreturn checklength" data_maxlength="1000" data_info="Sentence" cols="35" rows="3"><?php echo tohtml($sentence); ?></textarea></td>
			</tr>
			<tr>
			<td class="td1 right">Status:</td>
			<td class="td1">
			<?php echo get_wordstatus_radiooptions($record['WoStatus']); ?>
			</td>
			</tr>
			<tr>
			<td class="td1 right" colspan="2">
			<?php echo createDictLinksInEditWin($lang,$term,'document.forms[0].WoSentence',1); ?>
			&nbsp; &nbsp; &nbsp;
			<input type="submit" name="op" value="Change" /></td>
			</tr>
			</table>
			</form>
			<div id="exsent"><span class="click" onclick="do_ajax_show_sentences(<?php echo $lang; ?>, <?php echo prepare_textdata_js($termlc) . ', ' . prepare_textdata_js("document.forms['editword'].WoSentence"); ?>);"><img src="icn/sticky-notes-stack.png" title="Show Sentences" alt="Show Sentences" /> Show Sentences</span></div>
			<?php
		}
		mysql_free_result($res);
	}

}

pageend();

?>