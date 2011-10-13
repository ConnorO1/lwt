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
Call: new_word.php?...
			... text=[textid]&lang=[langid] ... new term input
			... op=Save ... do the insert
New word, created while reading or testing
***************************************************************/

require 'lwt-startup.php';

// INSERT

if (isset($_REQUEST['op'])) {

	if ($_REQUEST['op'] == 'Save') {

		$text = trim(prepare_textdata($_REQUEST["WoText"]));
		$textlc = mb_strtolower($text, 'UTF-8');
		$translation_raw = repl_tab_nl(getreq("WoTranslation"));
		if ( $translation_raw == '' ) $translation = '*';
		else $translation = $translation_raw;

		$titeltext = "New Term: " . tohtml($textlc);
		pagestart_nobody($titeltext);
		echo '<h4><span class="bigger">' . $titeltext . '</span></h4>';

		$message = runsql('insert into words (WoLgID, WoTextLC, WoText, ' .
			'WoStatus, WoTranslation, WoSentence, WoRomanization, WoStatusChanged,' .  make_score_random_insert_update('iv') . ') values( ' .
			$_REQUEST["WoLgID"] . ', ' .
			convert_string_to_sqlsyntax($textlc) . ', ' .
			convert_string_to_sqlsyntax($text) . ', ' .
			$_REQUEST["WoStatus"] . ', ' .
			convert_string_to_sqlsyntax($translation) . ', ' .
			convert_string_to_sqlsyntax(repl_tab_nl($_REQUEST["WoSentence"])) . ', ' .
			convert_string_to_sqlsyntax($_REQUEST["WoRomanization"]) . ', NOW(), ' .
make_score_random_insert_update('id') . ')', "Term saved");

		if (substr($message,0,22) == 'Error: Duplicate entry') {
			$message = 'Error: Duplicate entry for ' . $textlc;
		}

		$wid = get_last_key();

		$hex = strToClassName(prepare_textdata($textlc));

		saveWordTags($wid);

		$showAll = getSetting('showallwords');
		$showAll = ($showAll == '' ? 1 : (((int) $showAll != 0) ? 1 : 0));
?>

<p><?php echo tohtml($message); ?></p>

<?php
		if (substr($message,0,5) != 'Error') {
?>

<script type="text/javascript">
//<![CDATA[
var context = window.parent.frames['l'].document;
var contexth = window.parent.frames['h'].document;
var woid = <?php echo prepare_textdata_js($wid); ?>;
var status = <?php echo prepare_textdata_js($_REQUEST["WoStatus"]); ?>;
var trans = <?php echo prepare_textdata_js($translation . getWordTagList($wid,' ',1,0)); ?>;
var roman = <?php echo prepare_textdata_js($_REQUEST["WoRomanization"]); ?>;
var title = make_tooltip(<?php echo prepare_textdata_js($text); ?>,trans,roman,status);
$('.TERM<?php echo $hex; ?>', context).removeClass('status0 hide').addClass('word' + woid + ' ' + 'status' + status).attr('data_trans',trans).attr('data_rom',roman).attr('data_status',status).attr('data_wid',woid).attr('title',title);
$('#learnstatus', contexth).html('<?php echo texttodocount2($_REQUEST['tid']); ?>');
window.parent.frames['l'].focus();
window.parent.frames['l'].setTimeout('cClick()', 100);
<?php
		if (! $showAll) echo refreshText($text,$_REQUEST['tid']);
?>
//]]>
</script>

<?php
		} // (substr($message,0,5) != 'Error')

	} // $_REQUEST['op'] == 'Save'

} // if (isset($_REQUEST['op']))

// FORM

else {  // if (! isset($_REQUEST['op']))

	// new_word.php?text=..&lang=..

	$lang = getreq('lang') + 0;
	$text = getreq('text') + 0;
	pagestart_nobody('');
	$scrdir = getScriptDirectionTag($lang);

?>

	<form name="newword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<input type="hidden" name="WoLgID" value="<?php echo $lang; ?>" />
	<input type="hidden" name="tid" value="<?php echo $text; ?>" />
	<table class="tab3" cellspacing="0" cellpadding="5">
	<tr>
	<td class="td1 right"><b>New Term:</b></td>
	<td class="td1"><input <?php echo $scrdir; ?> class="notempty setfocus" type="text" name="WoText" value="" maxlength="250" size="40" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
	</tr>
	<tr>
	<td class="td1 right">Translation:</td>
	<td class="td1"><textarea class="textarea-noreturn checklength" data_maxlength="500" data_info="Translation" name="WoTranslation" cols="40" rows="3"></textarea></td>
	</tr>
	<tr>
	<td class="td1 right">Tags:</td>
	<td class="td1">
	<?php echo getWordTags(0); ?>
	</td>
	</tr>
	<tr>
	<td class="td1 right">Romaniz.:</td>
	<td class="td1"><input type="text" name="WoRomanization" value="" maxlength="100" size="40" /></td>
	</tr>
	<tr>
	<td class="td1 right">Sentence<br />Term in {...}:</td>
	<td class="td1"><textarea <?php echo $scrdir; ?> name="WoSentence" cols="40" rows="3" class="textarea-noreturn checklength" data_maxlength="1000" data_info="Sentence"></textarea></td>
	</tr>
	<tr>
	<td class="td1 right">Status:</td>
	<td class="td1">
	<?php echo get_wordstatus_radiooptions(1); ?>
	</td>
	</tr>
	<tr>
	<td class="td1 right" colspan="2">  &nbsp;
	<?php echo createDictLinksInEditWin3($lang,'document.forms[\'newword\'].WoSentence','document.forms[\'newword\'].WoText'); ?>
	&nbsp; &nbsp;
	<input type="submit" name="op" value="Save" /></td>
	</tr>
	</table>
	</form>

<?php

}

pageend();

?>