<?php

$program_code = 28;
require_once('../../../system.config.php');
require_once('../../../common_functions.php');
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
$plevel = $cfn->get_program_level($program_code);
$level = $cfn->get_user_level();
if (substr($access_rights, 6, 2) !== "B+") {
    if($level <= $plevel ){
        echo json_encode(array("status" => "error", "message" => "Higher level required!"));
        return;
    }
    echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    return;
}

if (isset($_REQUEST["cmd"])) {
    try {
        if ($db->beginTransaction()) {
            switch ($_REQUEST["cmd"]) {
                case "get-payroll-summary":
                    $payroll_date=$cfn->datefromtable($_REQUEST["trans_date"]);
                    get_payroll_summary($payroll_date,$access_rights,1);
                break;
                case "print-summary":
                    if (substr($access_rights, 8, 2) === "P+") {
                        if($_REQUEST["token"] === $_SESSION["security_key"]){
                            $payroll_date=$cfn->datefromtable($_REQUEST["date"]);
                            $records = get_payroll_summary($payroll_date,$access_rights,0);
                            $columns = get_columns();
                            $title = strtoupper($cfn->sysconfig("company"))." PAYROLL SUMMARY OF ".(new DateTime($payroll_date))->format("m-d-Y");
                            print_summary($columns,$records,$title);
                        }else{
                            echo json_encode(array("status" => "error", "message" => "Invalid token!"));
                            return;
                        }
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "default":
                    $pay_date = get_pay_dates();
                    echo json_encode(array("status" => "success", "pay_dates" => $pay_date));
                    break;
            }
            $db->commit();
        return false;
        }
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(array("status" => "error", "message" => "database is not ready!", "e" => $e));
        exit();
    }
}

function get_pay_dates(){
    global $db, $db_hris;

    $dates = $db->prepare("SELECT `payroll_date` FROM $db_hris.`payroll_trans` GROUP BY `payroll_date` ORDER BY `payroll_date` DESC");
    $date_list = array();
    $dates->execute();
    if ($dates->rowCount()) {
        while ($data = $dates->fetch(PDO::FETCH_ASSOC)) {
        $date_list[] = array("id" => (new DateTime($data["payroll_date"]))->format("m/d/Y"), "text" => (new DateTime($data["payroll_date"]))->format("M d, Y"));
        }
    }
    return $date_list;
}

function get_columns(){
    $items = array();
    $items[] = array('field' => 'recid', 'caption' => '<b>EMPLOYEE NO</b>', 'size' => '120px', 'attr' => 'align=center' );
    $items[] = array('field' => 'bank', 'caption' => '<b>BANK ACCOUNT NO</b>', 'size' => '150px', 'attr' => 'align=center' );
    $items[] = array('field' => 'name', 'caption' => '<b>NAME</b>', 'size' => '200px' );
    $items[] = array('field' => 'atm', 'caption' => '<b>ATM Pay</b>', 'size' => '100px', 'render' => 'float:2' );
    $items[] = array('field' => 'cash', 'caption' => '<b>CASH Pay</b>', 'size' => '100px', 'render' => 'float:2' );
    return $items;
}

function get_payroll_summary($payroll_date,$access_rights,$option){
    global $db, $db_hris;

    $records = array();
    $payroll_trans = $db->prepare("SELECT * FROM $db_hris.`payroll_trans`, $db_hris.`master_data`, $db_hris.`master_id` WHERE `payroll_date`=:pay_date AND `payroll_trans`.`employee_no`=`master_data`.`employee_no` AND `payroll_trans`.`employee_no`=`master_id`.`employee_no` AND `net_pay` > 0 ORDER BY `family_name`, `given_name`, `middle_name`");
    $payroll_trans->execute(array(":pay_date" => $payroll_date));
    if($payroll_trans->rowCount()){
        $atm_count=$total_net_pay=$atm=$cash=$cash_count=0;
        $summary = array("w2ui" => array("summary" => true), "recid" => "", "summary" => 1, "atm" => 0, "cash" => 0, "name" => "<span class=\"w3-right\"><b>GRAND TOTAL</b></span>");
        $summary1 = array("w2ui" => array("summary" => true), "recid" => "", "summary" => 2, "atm" => 0, "cash" => 0, "name" => "<span class=\"w3-right\"><b>RECORD COUNT</b></span>");
        while($payroll_trans_data = $payroll_trans->fetch(PDO::FETCH_ASSOC)){
            $total_net_pay+=$payroll_trans_data["net_pay"];
            $record["recid"] = $payroll_trans_data["pin"];
            $record["bank"] = $payroll_trans_data["bank_account"];
            $record["name"] = $payroll_trans_data["family_name"].", ".$payroll_trans_data["given_name"]." ".  substr($payroll_trans_data["middle_name"], 0,1);
            if($payroll_trans_data["bank_account"]){
                $record["atm"] = $payroll_trans_data["net_pay"];
                $record["cash"] = 0;
                $atm+=$payroll_trans_data["net_pay"];
                $atm_count++;
            }
            if(!$payroll_trans_data["bank_account"]){
                $cash+=$payroll_trans_data["net_pay"];
                $record["cash"] = $payroll_trans_data["net_pay"]; 
                $record["atm"] = 0;
                $cash_count++;
            }
            $summary["atm"] = $atm;
            $summary["cash"] = $cash;
            $summary1["atm"] = $atm_count;
            $summary1["cash"] = $cash_count;
            $records[] = $record;
        }
        if (count($records)) {
            $records[] = $summary;
            $records[] = $summary1;
        }
    }
    if($option){
        echo json_encode(array("status" => "success", "columns" => get_columns(), "records" => $records, "can_print" => substr($access_rights, 8, 2) === "P+" ? 1 : 0, "rights" => $access_rights));
    }else{
        return $records;
    }
}

function print_summary($columns, $records, $title) { ?>
    <!DOCTYPE html>
    <html>
        <head>
            <title><?php echo $title; ?></title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <link rel="stylesheet" type="text/css" href="css/w2ui.min.css"/>
            <link rel="stylesheet" type="text/css" href="css/w3-css.css"/>
            <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style type="text/css" media="print">
                @media all {
                    table thead {
                        display: table-header-group;
                    }
                    table tbody {
                        display: table-row-group;
                    }
                    thead, th {
                        padding: 2px 2px;
                    }
                    table, th, td {
                        border: 1px solid black;
                        border-collapse: collapse;
                        padding: 2px 2px;
                    }
                    @page { 
                        size: 8.5in 11in;
                    }
                }
            </style>
        </head>

        <body>
            <div class="w3-row" style="width: 100%;">
                <table class="w3-col s12">
                    <thead>
                        <tr>
                            <th colspan="6"><span style="font-size: 20px;"><?php echo $title; ?></span></th>
                        </tr>
                        <tr>
                            <th colspan="2" style="width: 100px;"><?php echo $columns[0]["caption"]; ?></th>
                            <th style="width: 150px;"><?php echo $columns[1]["caption"]; ?></th>
                            <th style="width: auto;"><?php echo $columns[2]["caption"]; ?></th>
                            <th style="width: 100px;"><?php echo $columns[3]["caption"]; ?></th>
                            <th style="width: 100px;"><?php echo $columns[4]["caption"]; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $cnt = 0;
                            foreach ($records as $record) {
                                if(!empty($record["recid"])){  ?>
                        <tr>
                            <td><?php echo ++$cnt; ?>.</td>
                            <td><?php echo $record["recid"]; ?></td>
                            <td align="center"><?php echo $record["bank"]; ?></td>
                            <td><?php echo $record["name"]; ?></td>
                            <td align="right"><?php echo number_format($record["atm"], 2); ?></td>
                            <td align="right"><?php echo number_format($record["cash"], 2); ?></td>
                        </tr>
                        <?php
                                }
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <?php
                            foreach ($records as $record) {
                                if(empty($record["recid"])){  ?>
                        <tr>
                            <td colspan="4" align="right"><?php echo $record["name"]; ?></td>
                            <td align="right"><b><?php echo number_format($record["atm"], 2); ?></b></td>
                            <td align="right"><b><?php echo number_format($record["cash"], 2); ?></b></td>
                        </tr>
                        <?php
                                }
                        }
                        ?>
                    </tfoot>
                </table>
            </div>
        </body>
    </html>
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
<?php
}