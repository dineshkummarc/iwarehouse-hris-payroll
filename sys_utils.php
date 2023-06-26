<?php

$program_code = 3;
include("common_function.class.php");
$cfn = new common_functions();
include('modules/system/system.config.php');
include('session.php');
$access_rights = $cfn->get_user_rights($program_code);
if (substr($access_rights, 6, 2) !== "B+") {
    echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    return;
}
?>
<style type="text/css">
.tableFixHead,
.tableFixHead td {
	box-shadow: inset 1px -1px #000;
}
.tableFixHead th {
	box-shadow: inset 1px 1px #000, 0 1px #000;
}
</style>
<body>
	<table align="center" class="w3-small w3-table-all">
		<thead>
			<tr>
				<th colspan="2" class="w3-center">Config Name</th>
				<th>Value</th>
				<th>Description</th>
				<th colspan="3">Record Stamp</th>
			</tr>
		</thead>
    	<tbody>
			<?php
				$cnt = 0;
				$q = "SELECT * FROM _sysconfig ORDER BY config_code";
				$r = mysqli_query($con,$q);
				if (@mysqli_num_rows($r)) {
					while ($d = mysqli_fetch_array($r)) {
						$config_code = $d["config_code"];
				}
				$r = mysqli_query($con,$q);
				while ($d = mysqli_fetch_array($r)) {
					$config_code = $d["config_code"];
					?>
					<tr class="w3-hover-orange">
						<td align="right"><?php echo number_format(++$cnt); ?>.</td>
						<td><?php echo $d["config_name"]; ?></td>
						<td><?php echo $d["config_value"]; ?></td>
						<td><?php echo $d["config_title"]; ?></td>
						<td><?php echo $d["user_id"]; ?>&nbsp;</td>
						<td><?php echo $d["station_id"]; ?>&nbsp;</td>
						<td><?php echo $cfn->datefromdb(substr($d["time_stamp"], 0, 10)) . substr($d["time_stamp"], 10, 10); ?></td>
					</tr>
			<?php
			}
		} ?>
        </tbody>
	</table>
</body>
</html>
