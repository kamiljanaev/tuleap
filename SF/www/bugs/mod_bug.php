<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$


$sql="SELECT * FROM bug WHERE bug_id='$bug_id' AND group_id='$group_id'";
$fields_per_line=2;
$max_size=40;

$result=db_query($sql);

if (db_numrows($result) > 0) {

    // Prepare all the necessary fields in case the user wants to 
    // Create a new task based on this bug

    // assigned_to is passed along
    $assigned_to = db_result($result,0,'assigned_to');

    // Check if hours is used. If so pass it along as well
    if ( bug_data_is_used('hours') ) {
        $hours = db_result($result,0,'hours');
    } else {
        $hours = '';
    }
    
    // Insert a reference to the originating bug in the task description
    $task_details = db_result($result,0,'details')."\n\nSee bug #$bug_id\nhttp://".
	$GLOBALS['sys_default_domain']."/bugs/?func=detailbug&bug_id=$bug_id&group_id=$group_id";

    bug_header(array ('title'=>'Modify a Bug',
                      'create_task'=>'Create task',
                      'summary' => db_result($result,0,'summary'),
                      'details' => $task_details,
                      'assigned_to' => $assigned_to,
                      'hours' => $hours,
                      'bug_id' => $bug_id
                      ));
    
    // First display some  internal fields - Cannot be modified by the user
?>
    <H2>[ Bug #<?php echo $bug_id.' ] '.db_result($result,0,'summary');?></H2>

    <FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST" enctype="multipart/form-data" NAME="bug_form">
    <INPUT TYPE="HIDDEN" NAME="func" VALUE="postmodbug">
    <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id;; ?>">
    <INPUT TYPE="HIDDEN" NAME="bug_id" VALUE="<?php echo $bug_id; ?>">

    <TABLE cellpadding="0">
      <TR><TD><B>Submitted By:</B>&nbsp;</td><td><?php echo user_getname(db_result($result,0,'submitted_by')); ?></TD>
          <TD><B>Group:</B>&nbsp;</td><td><?php echo group_getname($group_id); ?></TD>
      </TR>
      <TR><TD><B>Submitted on:</B>&nbsp;</td><td><?php  echo format_date($sys_datefmt,db_result($result,0,'date')); ?></TD>
          <TD colspan="2"><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes"></TD>
      </TR>
      <TR><TD COLSPAN="<?php echo 2*$fields_per_line; ?>">&nbsp</TD></TR>
      <script language="JavaScript" src="/include/calendar.js"></script>

<?php
      // Now display the variable part of the field list (depend on the project)

      $i=0;
      while ( $field_name = bug_list_all_fields() ) {

	  // if the field is a special field or if not used byt his project 
	  // then skip it.
	  if ( !bug_data_is_special($field_name) &&
	       bug_data_is_used($field_name) ) {
				   
	      // display the bug field
	      // if field size is greatest than max_size chars then force it to
	      // appear alone on a new line or it won't fit in the page
	      $field_value = db_result($result,0,$field_name);
	      list($sz,) = bug_data_get_display_size($field_name);
	      if ($sz > $max_size) {
		  echo "\n<TR>".
		      '<TD valign="middle">'.bug_field_label_display($field_name,$group_id,false,false).'</td>'.
		      '<TD valign="middle" colspan="'.(2*$fields_per_line-1).'">'.
		      bug_field_display($field_name,$group_id,$field_value,false,false).'</TD>'.		      
		      "\n</TR>";
		  $i=0;
	      } else {
		  echo ($i % $fields_per_line ? '':"\n<TR>");
		  //echo '<TD valign="middle">'.bug_field_display($field_name,$group_id,$field_value).'</TD>';
		  echo '<TD valign="middle">'.bug_field_label_display($field_name,$group_id,false,false).'</td>'.
		      '<TD valign="middle">'.bug_field_display($field_name,$group_id,$field_value,false,false).'</TD>';
		  $i++;
		  echo ($i % $fields_per_line ? '':"\n</TR>");
	      }
	  }
      }
      
      // Now display other special fields

      // Summary first. It is a special field because it is both displayed in the
      // title of the bug form and here as a text field
?>
      <TR><TD colspan="<?php echo 2*$fields_per_line; ?>"><br>
<?php echo bug_field_display('summary',$group_id,db_result($result,0,'summary')); ?>
      </td></tr>
      </table>

      <table cellspacing="0">
      <TR><TD colspan="2" align="top"><HR></td></TR>
      <TR><TD colspan="2" ><B>Use a Canned Response:</B>&nbsp;
      <?php
      echo bug_canned_response_box ($group_id,'canned_response');
      echo '&nbsp;&nbsp;&nbsp;<A HREF="/bugs/admin/index.php?group_id='.$group_id.'&create_canned=1">Or define a new Canned Response</A><P>';
      ?>
      </TD></TR>
 
      <TR><TD colspan="2">
      <P><B>Post a followup comment of type:</B>
      <?php echo bug_field_box('comment_type_id','',$group_id,'',true,'None'); ?><BR>
      <?php echo bug_field_textarea('details',''); ?>
      <P>
      <B>Original Submission:</B><BR>
      <?php
      echo util_make_links(nl2br(db_result($result,0,'details')));
      echo show_bug_details($bug_id); 
      ?>
      </td></tr>

      <TR><TD colspan="2"><hr></td></tr>

      <TR><TD colspan="2">
      <h3>CC list</h3>
	  <b><u>Note:</b></u> for CodeX users use their login name rather than their email addresses.<p>
	  <B>Add CC:&nbsp;</b><input type="text" name="add_cc" size="30">&nbsp;&nbsp;&nbsp;
	  <B>Comment:&nbsp;</b><input type="text" name="cc_comment" size="40" maxlength="255"><p>
	  <?php show_bug_cc_list($bug_id, $group_id); ?>
      </TD></TR>

      <TR><TD colspan="2"><hr></td></tr>

      <TR><TD colspan="2">

      <h3>Bug Attachments</h3>
      <A href="javascript:help_window('/help/mod_bug.php?helpname=attach_file')"><b>(?)</b></a>
       <B>Check to Upload&hellip;  <input type="checkbox" name="add_file" VALUE="1">
      &nbsp;&hellip;&amp; Attach File:</B>
      <input type="file" name="input_file" size="40">
      <P>
      <B>File Description:</B>&nbsp;
      <input type="text" name="file_description" size="60" maxlength="255">
      <P>
      <?php show_bug_attached_files($bug_id,$group_id); ?>
      </TD></TR>

      <TR><TD colspan="2"><hr></td></tr>

      <TR ><TD colspan="2" valign="top">
      <h3>Bug Dependencies</h3>
      </td></TR>

	<TR><TD VALIGN="TOP">
	<B>Dependent on Task:</B><BR>
	<?php 
	/*
		Dependent on Task........
	*/

	echo bug_multiple_task_depend_box ('dependent_on_task[]',$group_id,$bug_id);

	?>
	</TD><TD VALIGN="TOP">
	<B>Dependent on Bug:</B><BR>
	<?php
	/*
		Dependent on Bug........
	*/
	echo bug_multiple_bug_depend_box ('dependent_on_bug[]',$group_id,$bug_id)

	?>
	</TD></TR>

	<TR><TD colspan="2" >
		<?php echo show_dependent_bugs($bug_id,$group_id); ?>
	</TD></TR>

        <TR><TD colspan="2"><hr></td></tr>

	<TR><TD colspan="2" >
		<?php echo show_bughistory($bug_id,$group_id); ?>
	</TD></TR>

	<TR><TD colspan="2" ALIGN="MIDDLE">
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes">
		</FORM>
	</TD></TR>

	</TABLE>

<?php

} else {

    bug_header(array ('title'=>'Modify a Bug'));
    
	echo '
	<H1>Bug Not Found</H1>';
	echo db_error();
}

bug_footer(array());

?>
