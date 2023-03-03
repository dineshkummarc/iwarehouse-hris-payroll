<?php 

$program_code = 8;
require_once('../common/functions.php');

include("../common_function.class.php");
$cfn = new common_functions();

$output = '';
$group_no = $_GET["pay_group"];
$store = $_GET["store"];
$payroll_date=$cfn->datefromtable($_GET["pay_date"]);
$group_data = mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `employment_status` WHERE `employment_status_code`='$group_no'"));
$payroll_group =mysqli_query($con,"SELECT * FROM `payroll_group` WHERE `group_name` LIKE '$group_no'");
if (@mysqli_num_rows($payroll_group)) {
    set_time_limit(300);
    $payroll_group_data =mysqli_fetch_array($payroll_group);
    $log_cutoff = $payroll_group_data["cutoff_date"];
    $payroll_cutoff = $payroll_group_data["payroll_date"];

    $payroll_type_query= "SELECT * FROM `payroll_type` WHERE (SELECT COUNT(*) FROM `payroll_trans_pay`,`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_pay`.`employee_no` AND `master_data`.`store`='$store' AND `master_data`.`group_no`='$group_no' AND `payroll_date`='$payroll_date' AND `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` LIMIT 1) ORDER BY `payroll_type_no`";
    $deduction_query="SELECT * FROM `deduction` WHERE (SELECT COUNT(*) FROM `payroll_trans_ded`,`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_ded`.`employee_no` AND `master_data`.`store`='$store' AND `master_data`.`group_no`='$group_no' AND `payroll_trans_ded`.`payroll_date`='$payroll_date' AND `payroll_trans_ded`.`deduction_no`=`deduction`.`deduction_no` LIMIT 1) ORDER BY `deduction_no`";
    $payroll_trans= mysqli_query($con,"SELECT * FROM `payroll_trans`, `master_data` WHERE `payroll_trans`.`payroll_date`='$payroll_date' AND `payroll_trans`.`payroll_group_no`='$payroll_group_data[group_name]' AND `payroll_trans`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`='$store' AND `master_data`.`group_no`=`payroll_trans`.`payroll_group_no` ORDER BY `family_name`, `given_name`, `middle_name`");
    if(@mysqli_num_rows($payroll_trans)){ 
        $payroll_type= mysqli_query($con,$payroll_type_query);
        $deduction= mysqli_query($con,$deduction_query);

        $pay_type = @mysqli_num_rows($payroll_type)+1;
        $pay_ded = @mysqli_num_rows($deduction)+1;
        $from = date("F j",strtotime($log_cutoff));
        $to = date("F j".", "."Y",strtotime($payroll_cutoff));

        $header_col = $pay_type+$pay_ded+4;
        $store_data = mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `store` WHERE `StoreCode`='$store'"));
        $output .= '<table class="w3-table" border="1">
                <thead>
                    <tr>
                        <th colspan="'.$header_col.'" style="text-align: center;">'.$store_data["StoreName"].'</th>
                    </tr>
                    <tr>
                        <th colspan="'.$header_col.'" style="text-align: left;">Date: '.date("F j".", "."Y").'</th>
                    </tr>
                    <tr>
                        <th colspan="'.$header_col.'" style="text-align: left;">Payroll Period: '.$from.' - '.$to.'</th>
                    </tr>
                    <tr>
                        <th colspan="'.$header_col.'" style="text-align: left;">Payroll Group: '.$group_data["description"].'</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th colspan="'.$pay_type.'" class="w3-center w3-border">PAYROLL EARNINGS</th>
                        <th colspan="'.$pay_ded.'" class="w3-center w3-border">PAYROLL DEDUCTION</th>
                        <th></th>
                    </tr>
                    <tr>
                        <th></th>
                        <th class="w3-center w3-border">PIN</th>
                        <th class="w3-center w3-border">NAME</th>';
                        while($payroll_type_data= mysqli_fetch_array($payroll_type)){
                            $output .= '<th class="w3-center w3-border">'.$payroll_type_data["pay_type"].'</th>';
                        }
                        $output .= '<th class="w3-center w3-border">GROSS PAY</th>';
                        while($deduction_data = mysqli_fetch_array($deduction)){
                            $output .= '<th class="w3-center w3-border">'.$deduction_data["deduction_label"].'</th>';
                        }
                        $output .= '<th class="w3-center w3-border">TOTAL DED</th>';
                        $output .= '<th class="w3-center w3-border">NET PAY</th>';
        $output .= '</tr>
                </thead>
                <tbody>';
                $cnt=0;
                while($payroll_trans_data= mysqli_fetch_array($payroll_trans)){
                $output .= '<tr class="register">
                                <td>'.number_format(++$cnt).'</td>
                                <td class="w3-center w3-border">'.$payroll_trans_data["pin"].'</td>
                                <td>'.$payroll_trans_data["family_name"] . ", " . $payroll_trans_data["given_name"] . " " . substr($payroll_trans_data["middle_name"], 0, 1).'</td>';
                                $total = 0;
                                $payroll_type= mysqli_query($con,$payroll_type_query);
                                while($payroll_type_data= mysqli_fetch_array($payroll_type)){ 
                                    $payroll_trans_pay= mysqli_query($con,"SELECT * FROM `payroll_trans_pay`,`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_pay`.`employee_no` AND `master_data`.`store`='$store' AND `payroll_trans_pay`.`payroll_date`='$payroll_date' AND `payroll_trans_pay`.`employee_no`='$payroll_trans_data[employee_no]' AND `payroll_trans_pay`.`payroll_type_no`='$payroll_type_data[payroll_type_no]'");
                                $output .= '<td style="text-align: right;">';
                                if(@mysqli_num_rows($payroll_trans_pay)){
                                    $payroll_trans_pay_data= mysqli_fetch_array($payroll_trans_pay);
                                    $output .= number_format($payroll_trans_pay_data["pay_amount"],2);
                                }
                                $output .= '</td>';
                    }
                    $output .= '<td style="text-align: right;">'.number_format($payroll_trans_data["gross_pay"],2).'</td>';
                    $deduction= mysqli_query($con,$deduction_query);
                        while($deduction_data= mysqli_fetch_array($deduction)){
                        $output .= '<td style="text-align: right;">';
                        $payroll_trans_ded= mysqli_query($con,"SELECT * FROM `payroll_trans_ded`,`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_ded`.`employee_no` AND `master_data`.`store`='$store' AND `payroll_trans_ded`.`employee_no`='$payroll_trans_data[employee_no]' AND `payroll_trans_ded`.`payroll_date`='$payroll_date' AND `payroll_trans_ded`.`deduction_no`='$deduction_data[deduction_no]'");
                        if(@mysqli_num_rows($payroll_trans_ded)){
                            $payroll_trans_ded_data= mysqli_fetch_array($payroll_trans_ded);
                            $total +=number_format($payroll_trans_ded_data["deduction_actual"], 2, ".", "");
                            $output .= number_format($payroll_trans_ded_data["deduction_actual"],2);
                        }
                        $output .= '</td>';
                    }
                    $output .= '<td style="text-align: right;">'.number_format($payroll_trans_data["deduction"],2).'</td>
                                <td style="text-align: right;">'.number_format($payroll_trans_data["net_pay"],2).'</td>
                            </tr>';
                    }
                    $payroll_trans_data = mysqli_fetch_array(mysqli_query($con,"SELECT SUM(`gross_pay`) AS `gross_pay`, SUM(`deduction`) AS `deduction`, SUM(`net_pay`) AS `net_pay` FROM `payroll_trans` WHERE `payroll_date`='$payroll_date' AND (SELECT COUNT(*) FROM `master_id`,`master_data` WHERE `master_data`.`employee_no`=`payroll_trans`.`employee_no` AND `master_data`.`store`='$store' AND `master_id`.`employee_no`=`payroll_trans`.`employee_no` AND `pay_group`='$payroll_group_data[group_name]')"));
        $output .= '</tbody>
                    <tfoot>
                        <tr style="color: #5F9DF7;">
                            <th colspan="3"  style="text-align: right;"">GRAND TOTAL</th>
                            <th style="text-align: right;" colspan="'.$pay_type.'">'.number_format($payroll_trans_data["gross_pay"], 2).'</th>';
                            $total_deduction = $payroll_trans_data["deduction"];
                            $col_count = @mysqli_num_rows($deduction)+1;
                    $output .= '<th style="text-align: right;" colspan="'.$col_count.'">'.number_format($total_deduction, 2).'</th>
                                <th style="text-align: right;">'.number_format($payroll_trans_data["net_pay"], 2).'</th>
                            </tr>
                        </tfoot>
                    </table>';
    }
            
    header('Content-Type: application/xls');
    header('Content-Disposition: attachment; filename='.strtoupper($store_data["StoreName"]).' '.$group_data["description"].' PAYROLL ACCOUNT AS OF '.$payroll_date.'.xls');
    echo $output;
}
?>