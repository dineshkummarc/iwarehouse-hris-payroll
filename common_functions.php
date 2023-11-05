<?php
session_start();
class common_functions
{

    function GetNoOfDays($DateF, $DateT)
    {
        return round(abs(strtotime($DateT) - strtotime($DateF)) / 86400, 0) + 1;
    }

    function sysconfig($config_name)
    {
        global $db, $db_hris;

        $config = $db->prepare("SELECT * FROM $db_hris.`_sysconfig` WHERE `config_name` LIKE :name");
        $config->execute(array(":name" => $config_name));
        if ($config->rowCount()) {
            $config_data = $config->fetch(PDO::FETCH_ASSOC);
            $value = $config_data["config_value"];
        } else {
            $value = "";
        }
        return $value;
    }

    function get_store()
    {
        global $db, $db_hris;

        $config = $db->prepare("SELECT * FROM $db_hris.`store` WHERE `isTheStore`");
        $config->execute();
        if ($config->rowCount()) {
            $config_data = $config->fetch(PDO::FETCH_ASSOC);
            $value = $config_data["InitialName"];
        } else {
            $value = "";
        }
        return $value;
    }

    function log_activity($prog_open)
    {
        global $db, $db_hris;

        $date = new DateTime();
        $activity = $db->prepare("INSERT INTO $db_hris.`log_activity` SET `user_id`=:user_id, `fired_menu`=:menu, `trans_date`=:tdate");
        $activity->execute(array(":user_id" => $_SESSION["name"], ":menu" => strtoupper($prog_open), ":tdate" => $date->format("Y-m-d H:i:s")));
    }

    function GetProgID($Prefix)
    {
        $Code = md5(uniqid($Prefix, true));
        return $Code;
    }

    function get_user_level()
    {
        global $db, $db_hris;

        $user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_id` LIKE :uid AND `is_active`");
        $user->execute(array(":uid" => $_SESSION['name']));
        if ($user->rowCount()) {
            $user_data = $user->fetch(PDO::FETCH_ASSOC);
            $level = $user_data["user_level"];
        } else {
            $level = 0;
        }
        return $level;
    }

    function get_program_level($program_code)
    {
        global $db, $db_hris;

        $prog = $db->prepare("SELECT * FROM $db_hris.`_program` WHERE `program_code`=:pcode AND `is_active`");
        $prog->execute(array(":pcode" => $program_code));
        if ($prog->rowCount()) {
            $prog_data = $prog->fetch(PDO::FETCH_ASSOC);
            $level = $prog_data["program_level"];
        } else {
            $level = 0;
        }
        return $level;
    }

    function get_program_parent_record($program_parent_no)
    {
        global $db, $db_hris;

        $parent = $db->prepare("SELECT * FROM $db_hris.`_program_parent` WHERE `parent_no`=:no ORDER BY `sequence`");
        $parent->execute(array(":no" => $program_parent_no));
        if ($parent->rowCount()) {
            $parent_data = $parent->fetch(PDO::FETCH_ASSOC);
            $name = array("id" => $parent_data["parent_no"], "text" => $parent_data["parent_name"]);
        } else {
            $name = "";
        }
        return $name;
    }

    function get_program_parent_records()
    {
        global $db, $db_hris;
        $records = array();
        $user_level = $this->get_user_level();
        if ($user_level) {
            if (number_format($user_level, 0) === number_format(1, 0)) {
                $parent = $db->prepare("SELECT * FROM $db_hris.`_program_parent` WHERE `parent_no` ORDER BY `sequence`");
                $parent->execute();
            } else {
                $parent = $db->prepare("SELECT * FROM $db_hris.`_program_parent` WHERE (SELECT COUNT(*) FROM $db_hris.`_program`, $db_hris.`_user_access` WHERE `_program`.`program_id`=`_user_access`.`program_id` AND `_program`.`program_parent_no`=`_program_parent`.`parent_no` AND `_user_access`.`user_id`=:user_id LIMIT 1) AND `parent_no` ORDER BY `_program_parent`.`sequence`");
                $parent->execute(array(":user_id" => $_SESSION["name"]));
            }
            if ($parent->rowCount()) {
                while ($parent_data = $parent->fetch(PDO::FETCH_ASSOC)) {
                    $records[] = array("id" => $parent_data["parent_no"], "text" => $parent_data["parent_name"]);
                }
            }
        }
        return $records;
    }

    function master_journal($change_from, $change_to, $reference, $remark, $employee_no)
    {
        global $db, $db_hris;

        $date = date("Y-m-d H:i:s");
        $journal = $db->prepare("INSERT INTO $db_hris.`master_journal` (`employee_no`, `reference`, `change_from`, `change_to`, `remarks`, `user_id`, `station_id`) VALUES (:eno, :ref, :fr, :to, :rmrk, :uid, :host)");
        $journal->execute(array(":eno" => $employee_no, ":ref" => $reference, ":fr" => $change_from, ":to" => $change_to, ":rmrk" => $remark, ":uid" => $_SESSION["name"], ":host" => $_SERVER['REMOTE_ADDR']));
        if ($journal->rowCount()) {
            $data = 1;
        } else {
            $data = 0;
        }
        return $data;
    }


    function post_shift($current_date)
    {
        global $db, $db_hris;

        set_time_limit(300);
        $del = $db->prepare("DELETE FROM $db_hris.`employee_work_schedule` WHERE `trans_date`=:tdate");
        $del->execute(array(":tdate" => $current_date));
        $day = date('w', mktime(0, 0, 0, substr($current_date, 5, 2), substr($current_date, 8, 2), substr($current_date, 0, 4)));
        $master =  $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE !`is_inactive` AND `work_schedule`!=''");
        $master->execute();
        if ($master->rowCount()) {
            while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
                set_time_limit(30);
                $shift = explode(",", $master_data["work_schedule"]);
                $shift_schedule = $shift[$day];
                $ins = $db->prepare("INSERT INTO $db_hris.`employee_work_schedule` (`employee_no`, `trans_date`, `shift_code`) VALUES (:eno, :date, :shift)");
                $ins->execute(array(":eno" => $master_data["employee_no"], ":date" => $current_date, ":shift" => $shift_schedule));
            }
        }
    }

    function alter_it($master_access, $my_access, $attrib)
    {
        $is_alter = false;
        switch ($attrib) {
            case "A":
                if (substr($master_access, 0, 2) === "A+") {
                    if (substr($my_access, 0, 2) === "A+") {
                        $my_access = str_replace("A+", "A-", $my_access);
                    } else {
                        $my_access = str_replace("A-", "A+", $my_access);
                        $is_alter = true;
                    }
                }
                break;
            case "E":
                if (substr($master_access, 2, 2) === "E+") {
                    if (substr($my_access, 2, 2) === "E+") {
                        $my_access = str_replace("E+", "E-", $my_access);
                    } else {
                        $my_access = str_replace("E-", "E+", $my_access);
                        $is_alter = true;
                    }
                }
                break;
            case "D":
                if (substr($master_access, 4, 2) === "D+") {
                    if (substr($my_access, 4, 2) === "D+") {
                        $my_access = str_replace("D+", "D-", $my_access);
                    } else {
                        $my_access = str_replace("D-", "D+", $my_access);
                        $is_alter = true;
                    }
                }
                break;
            case "B":
                if (substr($master_access, 6, 2) === "B+") {
                    if (substr($my_access, 6, 2) === "B+") {
                        $my_access = str_replace("B+", "B-", $my_access);
                        if (strpos($my_access, "+")) {
                            $my_access = str_replace("B-", "B+", $my_access);
                        }
                    } else {
                        $my_access = str_replace("B-", "B+", $my_access);
                        $is_alter = true;
                    }
                }
                break;
            case "P":
                if (substr($master_access, 8, 2) === "P+") {
                    if (substr($my_access, 8, 2) === "P+") {
                        $my_access = str_replace("P+", "P-", $my_access);
                    } else {
                        $my_access = str_replace("P-", "P+", $my_access);
                        $is_alter = true;
                    }
                }
                break;
        }
        if ($is_alter) {
            if (strpos($my_access, "B-")) {
                $my_access = str_replace("B-", "B+", $my_access);
            }
        }
        return $my_access;
    }

    function translate_access_rights($access_rights)
    {
        $rights = "";
        if (strpos($access_rights, "A+")) {
            $rights .= " Add;";
        }
        if (strpos($access_rights, "E+")) {
            $rights .= " Edit;";
        }
        if (strpos($access_rights, "D+")) {
            $rights .= " Delete;";
        }
        if (strpos($access_rights, "P+")) {
            $rights .= " Print;";
        }
        if (strpos($access_rights, "B+")) {
            $rights .= " Browse;";
        }
        if ($rights !== "") {
            $rights = substr($rights, 0, strlen($rights) - 1);
        }
        return $rights;
    }

    function set_rights($access_rights)
    {
        $set = $users_rights = array();
        if (substr($access_rights, 0, 2) === "A+") {
            $users_rights[] = "Add";
        }
        if (substr($access_rights, 2, 2) === "E+") {
            $users_rights[] = "Edit";
        }
        if (substr($access_rights, 4, 2) === "D+") {
            $users_rights[] = "Delete";
        }
        if (substr($access_rights, 6, 2) === "B+") {
            $users_rights[] = "Browse";
        }
        if (substr($access_rights, 8, 2) === "P+") {
            $users_rights[] = "Print";
        }
        if (count($users_rights)) {
            $set = implode(", ", $users_rights);
        }
        return $set;
    }

    function get_user_rights($program_code)
    {
        global $db, $db_hris;

        $user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_id` LIKE :uid");
        $user->execute(array(":uid" => $_SESSION["name"]));
        if ($user->rowCount()) {
            $user_data = $user->fetch(PDO::FETCH_ASSOC);
            if ($_SESSION["security_key"] === $user_data["security_key"]) {
                if ($user_data["is_active"]) {
                    $level = $this->get_user_level();
                    if (number_format($level, 0) < number_format(8, 0)) {
                        $src = $db->prepare("SELECT * FROM $db_hris.`_program` WHERE `program_code`=:code AND `is_active` AND `program_level`<>10");
                        $src->execute(array(":code" => $program_code));
                        if ($src->rowCount()) {
                            if (number_format($level, 0) <= number_format(8, 0)) {
                                $prog = $db->prepare("SELECT * FROM $db_hris.`_user_access` WHERE `program_code`=:code AND `user_id`=:uid");
                                $prog->execute(array(":code" => $program_code, ":uid" => $_SESSION["name"]));
                                if ($prog->rowCount()) {
                                    $prog_data = $prog->fetch(PDO::FETCH_ASSOC);
                                    $rights = $prog_data["access_rights"];
                                } else {
                                    $rights = "A-E-D-B-P-d";
                                }
                            } else {
                                $rights = "A+E+D+B+P+";
                            }
                        } else {
                            $rights = "A-E-D-B-P-c";
                        }
                    } else {
                        $rights = "A+E+D+B+P+";
                    }
                } else {
                    $rights = "A-E-D-B-P-b";
                }
            } else {
                $_SESSION = array();
                session_destroy();
                exit();
            }
        } else {
            echo json_encode(array("status" => "error", "nessage" => "Please login again!"));
            session_destroy();
            exit();
        }
        return $rights;
    }

    function user_program_access($program_code, $account_id)
    {
        global $db, $db_hris;
        $user = $db->prepare("SELECT * FROM $db_hris.`_user_program` WHERE `program_code`=:code AND `account_id`=:aid");
        $user->execute(array(":code" => $program_code, ":aid" => $account_id));
        if ($user->rowCount()) {
            $user_data = $user->fetch(PDO::FETCH_ASSOC);
            $rights = $user_data["access_rights"];
        } else {
            $rights = "A-E-D-B-P-";
        }
        return $rights;
    }

    function SetField($Data, $FieldLen = "8", $Adjust = "L")
    {
        $DataLen = strlen($Data);
        $ExtraSpace = $FieldLen - $DataLen;
        if ($ExtraSpace < 0)
            $ExtraSpace = 0;
        if ($Adjust == "C") {
            $Half = number_format($ExtraSpace / 2, 0, '', '') * 1;
            $OtherHalf = $ExtraSpace - $Half;
            $ExactField = str_repeat(" ", $Half) . $Data . str_repeat(" ", $OtherHalf);
        } elseif ($Adjust == "R")
            $ExactField = str_repeat(" ", $ExtraSpace) . $Data;
        else
            $ExactField = $Data;

        $ExactField = substr($ExactField . str_repeat(" ", $FieldLen), 0, $FieldLen);

        return $ExactField;
    }

    function GetHostName()
    {
        return gethostbyaddr($_SERVER['REMOTE_ADDR']);
    }

    function GetIPAdd()
    {
        $ipaddress = "";
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = "UNKNOWN";
        return $ipaddress;
    }

    function datefromdb($date)
    {
        $new = explode("-", $date);
        $a = array($new[1], $new[2], $new[0]);
        return $n_date = implode("/", $a);
    }

    function datefromtable($date)
    {
        if (strpos($date, "/"))
            $new = explode("/", $date);
        elseif (strpos($date, "-"))
            $new = explode("-", $date);
        elseif (strpos($date, "."))
            $new = explode(".", $date);
        if ($new[2] < 100)
            if ($new[2] <= 50)
                $new[2] = $new[2] + 2000;
            else
                $new[2] = $new[2] + 1900;
        $new[0] = substr(number_format($new[0]) + 100, -2);
        $new[1] = substr(number_format($new[1]) + 100, -2);
        $a = array($new[2], $new[0], $new[1]);
        return $n_date = implode("-", $a);
    }


    function download_csv($columns, $records, $filename = "")
    {
        if ($filename === "") {
            $filename = uniqid("", true) . ".csv";
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');
        if (count($columns)) {
            $items = array();
            foreach ($columns as $column) {
                $items[] = $column["caption"];
            }
            fputcsv($output, $items);
        }
        foreach ($records as $record) {
            $items = array();
            foreach ($columns as $column) {
                $items[] = $record[$column["field"]];
            }
            fputcsv($output, $items);
        }
    }

    function print_register($options){
        //$columns, $records, $title, $is_line_number = true, $is_flex = false, $is_draw_border = false, $force_render = false, $isCenter = false
        $columns = $options["columns"];
        $records = $options["records"];
        $title = $options["title"];
        $is_line_number = isset($options["is_line_number"]) ? $options["is_line_number"] : true;
        $is_flex = isset($options["is_flex"]) ? $options["is_flex"] : false;
        $is_draw_border = isset($options["is_draw_border"]) ? $options["is_draw_border"] : false;
        $force_render = isset($options["force_render"]) ? $options["force_render"] : false;
        $isCenter = isset($options["isCenter"]) ? $options["isCenter"] : false;
        $print = isset($options["print"]) ? $options["print"] : true;
        $company = strtoupper($this->sysconfig("company"));
        $address = strtoupper($this->sysconfig("branch")); ?>
        <!DOCTYPE html>
        <html>

        <head>
            <title>Register</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <link rel="stylesheet" href="../../../w3-css.css">
            <link rel="stylesheet" href="../../../font-awesome.min.css">
            <style type="text/css" media="print">
                @media all {
                    table thead {
                        display: table-header-group;
                    }

                    table tbody {
                        display: table-row-group;
                    }

                    thead,
                    th {
                        font-size: 90%;
                    }

                    tr.line td {
                        border-bottom: 1pt solid black;
                    }

                    table, th, td {
                        border: 1px solid black;
                        border-collapse: collapse;
                        padding: 2px 2px;
                    }

                    p {
                        font-size: 60%;
                        margin: 0 0 0 0;
                        padding: 0 0 0 0;
                    }
                }
            </style>
        </head>

        <body>
            <div class="w3-row" style="font-size: 90%; width: 100%;">
                <table style="width: 100%;" <?php
                        if ($is_draw_border) {
                            echo "style=\"border-collapse: collapse;\"";
                        }
                        if ($isCenter) {
                            echo " align=\"center\"";
                        }
                        ?>>
                    <thead>
                        <tr>
                            <th colspan="<?php echo count($columns["column"])+1; ?>" align="center" style="font-size: 110%;"><?php echo $company; ?></th>
                        </tr>
                        <?php if (!isset($options["no-company"])) { ?>
                            <tr>
                                <td colspan="<?php echo count($columns["column"])+1; ?>" align="center" style="font-size: 80%; margin: 0 0 0 0;"><?php echo $address; ?></td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <th colspan="<?php echo count($columns["column"])+1; ?>" align="center" style="font-size: 110%;"><?php echo $title; ?></th>
                        </tr>
                        <tr>
                            <td colspan="<?php echo count($columns["column"])+1; ?>" style="font-size: 40%;">&nbsp;</td>
                        </tr>
                        <?php if (isset($columns["group"])) { ?>
                            <tr style="font-size: 80%;">
                                <th></th>
                                <?php
                                foreach ($columns["group"] as $column) {
                                ?><th colspan="<?php echo $column["span"]; ?>"><?php echo $column["caption"]; ?></th>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                        <tr style="font-size: 80%;">
                            <th></th>
                            <?php
                            foreach ($columns["column"] as $column) {
                            ?><th <?php
                                if (isset($column["sizeCalculated"])) {
                                    echo " style=\"size: $column[sizeCalculated];\"";
                                } elseif (isset($column["size"]) and !$is_flex) {
                                    echo " style=\"size: $column[size];\"";
                                }
                    ?>><?php echo $column["caption"]; ?></th>
                            <?php
                            }
                            ?>
                        </tr>
                    </thead>
                    <tbody style="font-size: 90%"><?php
                                                    $cnt = 0;
                                                    foreach ($records as $record) {
                                                    ?>
                            <tr <?php
                                                        if (empty($record["recid"])) {
                                                            echo "style=\"font-weight: bolder;\"";
                                                        } else {
                                                            if ($is_draw_border) {
                                                                echo "style=\"border: 1px solid black;\"";
                                                            } elseif (isset($record["style"])) {
                                                                echo "style=\"$record[style]\"";
                                                            }
                                                        }
                                ?>>
                                <td align="right"><?php
                                                        if ($is_line_number) {
                                                            if (!empty($record["recid"])) {
                                                                echo number_format(++$cnt) . ".";
                                                            }
                                                        }
                                                    ?></td>
                                <?php foreach ($columns["column"] as $column) {
                                ?>
                                    <td <?php
                                                            if (isset($column["attr"])) {
                                                                echo $column["attr"];
                                                            } elseif (isset($column["render"])) {
                                                                if ($column["render"] !== "date") {
                                                                    echo "align=right";
                                                                }
                                                            }
                                        ?>><?php        if (array_key_exists('render', $column)) {
                                                            if (strpos($column["render"], ":")) {
                                                                $pos = substr($column["render"], strpos($column["render"], ":") + 1);
                                                                if ($force_render and !is_null($record[$column["field"]])) {
                                                                    echo number_format($record[$column["field"]], $pos);
                                                                } elseif (number_format($record[$column["field"]], $pos, '.', '') !== number_format(0, $pos, '.', '')) {
                                                                    echo number_format($record[$column["field"]], $pos);
                                                                }
                                                            } else {
                                                                if ($column["render"] === "date") {
                                                                    $date = new DateTime($record[$column["field"]]);
                                                                    if (strlen($record[$column["field"]]) <= 10) {
                                                                        echo $date->format("m/d/Y");
                                                                    } else {
                                                                        echo $date->format("m/d/Y H:i:s");
                                                                    }
                                                                } else {
                                                                    echo $record[$column["field"]];
                                                                }
                                                            }
                                                        }else{
                                                            echo $record[$column["field"]];
                                                        }
                            ?></td>
                                <?php }
                                ?>
                            </tr>
                        <?php }
                        ?>
                    </tbody>
                </table>
                <?php
                if (isset($options["footnote"])) {
                    echo "<span style=\"font-size: 100%;\">$options[footnote]</span><br>";
                }
                if (isset($options["footnote-date"])) {
                    echo "<span style=\"font-size: 50%;\">/date printed: " . date("m/d/Y H:i:s") . "</span><br>";
                }
                ?>
            </div>
        </body>

        </html>
        <?php if ($print) { ?>
            <script>
                window.onload = function() {
                    window.print();
                };
            </script>
        <?php } ?>
<?php
    }
}
