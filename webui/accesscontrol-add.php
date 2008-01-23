<?php
# Module: AccessControl (add)
# Copyright (C) 2008, LinuxRulz
# 
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

include_once("includes/header.php");
include_once("includes/footer.php");
include_once("includes/db.php");



$db = connect_db();



printHeader(array(
		"Title" => "Access Control",
		"Tabs" => array(
			"Back to access cntrl" => "accesscontrol-main.php"
		),
));



if ($_POST['action'] == "add") {
?>
	<h1>Add Access Control</h1>

	<form method="post" action="accesscontrol-add.php">
		<div>
			<input type="hidden" name="action" value="add2" />
		</div>
		<table class="entry">
			<tr>
				<td class="entrytitle">Name</td>
				<td><input type="text" name="accesscontrol_name" /></td>
			</tr>
			<tr>
				<td class="entrytitle">Link to policy</td>
				<td>
					<select name="accesscontrol_policyid">
<?php
						$res = $db->query("SELECT ID, Name FROM policies ORDER BY Name");
						while ($row = $res->fetchObject()) {
?>
							<option value="<?php echo $row->id ?>"><?php echo $row->name ?></option>
<?php
						}
?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="entrytitle">Verdict</td>
				<td>
					<select name="accesscontrol_verdict">
						<option value="HOLD">Hold</option>
						<option value="REJECT" selected="selected">Reject</option>
						<option value="DISCARD">Discard (drop)</option>
						<option value="FILTER">Filter</option>
						<option value="REDIRECT">Redirect</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="entrytitle">Data</td>
				<td><input type="text" name="accesscontrol_data" /></td>
			</tr>
			<tr>
				<td class="entrytitle">Comment</td>
				<td><textarea name="accesscontrol_comment" cols="40" rows="5"></textarea></td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="submit" />
				</td>
			</tr>
		</table>
	</form>

<?php

# Check we have all params
} elseif ($_POST['action'] == "add2") {
?>
	<h1>Access Control Add Results</h1>

<?php
	# Check name
	if (empty($_POST['accesscontrol_policyid'])) {
?>
		<div class="warning">Policy ID cannot be empty</div>
<?php

	# Check name
	} elseif (empty($_POST['accesscontrol_name'])) {
?>
		<div class="warning">Name cannot be empty</div>
<?php

	# Check verdict
	} elseif (empty($_POST['accesscontrol_verdict'])) {
?>
		<div class="warning">Verdict cannot be empty</div>
<?php

	} else {
		$stmt = $db->prepare("INSERT INTO access_control (PolicyID,Name,Verdict,Data,Comment,Disabled) VALUES (?,?,?,?,?,1)");
		
		$res = $stmt->execute(array(
			$_POST['accesscontrol_policyid'],
			$_POST['accesscontrol_name'],
			$_POST['accesscontrol_verdict'],
			$_POST['accesscontrol_data'],
			$_POST['accesscontrol_comment']
		));
		
		if ($res) {
?>
			<div class="notice">Access control created</div>
<?php
		} else {
?>
			<div class="warning">Failed to create access control</div>
<?php
		}

	}


} else {
?>
	<div class="warning">Invalid invocation</div>
<?php
}

printFooter();


# vim: ts=4
?>