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
Call: do_test_header.php?lang=[langid]
Call: do_test_header.php?text=[textid]
Call: do_test_header.php?selection=1
			(SQL via $_SESSION['testsql'])
Show test header frame
***************************************************************/

require 'lwt-startup.php';

$p = '';
$message = '';

if (isset($_REQUEST['selection']) && isset($_SESSION['testsql'])) {
	$p = "selection=" . $_REQUEST['selection'];
	$testsql = $_SESSION['testsql'];
	$totalcount = get_first_value('select count(distinct WoID) as value from ' . $testsql);
	$title = 'Selected ' . $totalcount . ' Term' . ($totalcount==1 ? '' : 's');
	$cntlang = get_first_value('select count(distinct WoLgID) as value from ' . $testsql);
	if ($cntlang > 1)
		$message = 'Error: The selected terms are in ' . $cntlang . ' languages, but tests are only possible in one language at a time.';
	else
		$title .= ' in ' . get_first_value('select LgName as value from languages, ' . $testsql . ' and LgID = WoLgID limit 1');
}

if (isset($_REQUEST['lang'])) {
	$langid = getreq('lang');
	$p = "lang=" . $langid;
	$title = "All Terms in " . get_first_value('select LgName as value from languages where LgID = ' . $langid);
	$testsql = ' words where WoLgID = ' . $langid . ' ';
}

if (isset($_REQUEST['text'])) {
	$textid = getreq('text');
	$p = "text=" . $textid;
	$title = get_first_value('select TxTitle as value from texts where TxID = ' . $textid);
	saveSetting('currenttext',$_REQUEST['text']);
	$testsql = ' words, textitems where TiLgID = WoLgID and TiTextLC = WoTextLC and TiTxID = ' . $textid . ' ';

}

if ($p == '') die("Called with wrong parameters");

$totalcountdue = get_first_value('SELECT count(distinct WoID) as value FROM ' . $testsql . ' AND WoStatus BETWEEN 1 AND 5 AND WoTranslation != \'\' AND WoTranslation != \'*\' AND WoTodayScore < 0');

pagestart_nobody(tohtml($title));
echo '<h4>';
echo '<a href="edit_texts.php" target="_top">';
echo '<img src="img/lwt_icon.png" class="lwtlogo" alt="Logo" />Learning with Texts';
echo '</a>&nbsp; | &nbsp;';
quickMenu();
if (substr($p,0,4) == 'text') {
	echo '&nbsp; | &nbsp;<a href="do_text.php?start=' . $textid . '" target="_top"><img src="icn/book-open-bookmark.png" title="Read" alt="Read" /></a> &nbsp;<a href="print_text.php?text=' . $textid . '" target="_top"><img src="icn/printer.png" title="Print" alt="Print" /></a>';
}
echo '</h4><table><tr><td><h3>TEST&nbsp;▶</h3></td><td class="width99pc"><h3>' . tohtml($title) . ' (Due: ' . $totalcountdue . ')</h3></td></tr><tr><td colspan="2">';

$_SESSION['teststart'] = gmmktime() + 2;
$_SESSION['testcorrect'] = 0;
$_SESSION['testwrong'] = 0;
$_SESSION['testtotal'] = $totalcountdue;

if ($message != '') {
	echo error_message_with_hide($message,1);
}

else {  // OK

?>
<p><b>Choose:</b>
<input type="button" value="..[L2].." onclick="{parent.frames['ro'].location.href='empty.htm'; parent.frames['ru'].location.href='empty.htm'; parent.frames['l'].location.href='do_test_test.php?type=1&amp;<?php echo $p; ?>';}" />
<input type="button" value="..[L1].." onclick="{parent.frames['ro'].location.href='empty.htm'; parent.frames['ru'].location.href='empty.htm'; parent.frames['l'].location.href='do_test_test.php?type=2&amp;<?php echo $p; ?>';}" />
<input type="button" value="..[••].." onclick="{parent.frames['ro'].location.href='empty.htm'; parent.frames['ru'].location.href='empty.htm'; parent.frames['l'].location.href='do_test_test.php?type=3&amp;<?php echo $p; ?>';}" /> |
<input type="button" value="[L2]" onclick="{parent.frames['ro'].location.href='empty.htm'; parent.frames['ru'].location.href='empty.htm'; parent.frames['l'].location.href='do_test_test.php?type=4&amp;<?php echo $p; ?>';}" />
<input type="button" value="[L1]" onclick="{parent.frames['ro'].location.href='empty.htm'; parent.frames['ru'].location.href='empty.htm'; parent.frames['l'].location.href='do_test_test.php?type=5&amp;<?php echo $p; ?>';}" />
</p></td></tr></table>

<?php

}

pageend();

?>