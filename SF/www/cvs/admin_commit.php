<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$



if (!$group_id) {
    exit_no_group(); // need a group_id !!!
}

$LANG->loadLanguageMsg('cvs/cvs');

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

commits_header(array ('title'=>$LANG->getText('cvs_admin_commit', 'title'),
		      'help' => 'CVSWebInterface.html#CVSAdministration'));

// get project name
$sql = "SELECT unix_group_name, cvs_tracker, cvs_events_mailing_list, cvs_events_mailing_header, cvs_preamble from groups where group_id=$group_id";

$result = db_query($sql);
$projectname = db_result($result, 0, 'unix_group_name');
$cvs_tracked = db_result($result, 0, 'cvs_tracker');
$cvs_mailing_list = db_result($result, 0, 'cvs_events_mailing_list');
$cvs_mailing_header = db_result($result, 0, 'cvs_events_mailing_header');
$cvs_preamble = db_result($result, 0, 'cvs_preamble');

if ($cvs_mailing_list == 'NULL') {
  $cvs_mailing_list = '';
}
$custom_mailing_header = $cvs_mailing_header;

if ($cvs_mailing_header == 'NULL') {
  $custom_mailing_header = "";
}


echo "<h2>".$LANG->getText('cvs_admin_commit', 'title')."</h2>";
  
echo '<FORM ACTION="'. $PHP_SELF .'" METHOD="GET">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="setAdmin">
	<h3>'.$LANG->getText('cvs_admin_commit', 'tracking_hdr').
'</H3><p>'.$LANG->getText('cvs_admin_commit', 'tracking_msg',array($GLOBALS['sys_name'])).
        '<p>'.$LANG->getText('cvs_admin_commit', 'tracking_lbl').
        '&nbsp;&nbsp;&nbsp;&nbsp;<SELECT name="tracked"> '.
 	'<OPTION VALUE="1"'.(($cvs_tracked == '1') ? ' SELECTED':'').'>on</OPTION>'.
 	'<OPTION VALUE="0"'.(($cvs_tracked == '0') ? ' SELECTED':'').'>off</OPTION>'.
	'</SELECT></p>'.
        '<H3>'.$LANG->getText('cvs_admin_commit', 'notif_hdr').
        '</H3><p>'.$LANG->getText('cvs_admin_commit', 'notif_msg').'</p>'.
        '<br>'.$LANG->getText('cvs_admin_commit', 'mail_to').
         ':<br><INPUT TYPE="TEXT" SIZE="70" NAME="mailing_list" VALUE="'.$cvs_mailing_list.'">'.
        '<p>'.$LANG->getText('cvs_admin_commit', 'subject').': <br>'.
        '<INPUT TYPE="TEXT" SIZE="30" NAME="custom_mailing_header" VALUE="'.$custom_mailing_header.
        '"></p> <h3>'.$LANG->getText('cvs_admin_commit', 'preamble_hdr').
'</h3><P>'.$LANG->getText('cvs_admin_commit', 'preamble_msg',array("/cvs/?func=info&group_id=".$group_id, $GLOBALS['sys_name'])).
        '<p><TEXTAREA cols="70" rows="8" wrap="virtual" name="form_preamble">'.$cvs_preamble.'</TEXTAREA>';
echo '</p><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit"></p></FORM>';

commits_footer(array()); 
?>
