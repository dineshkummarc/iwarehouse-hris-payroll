<?php
global $db_hris, $db;
$program_code = 3;
include('modules/system/system.config.php');
include('session.php');

$check_level = mysqli_query($con, "SELECT `user_level` FROM `_user` where `user_id`='".$session_name."'");
$level = mysqli_fetch_array($check_level);

if($level['user_level'] <= $program_code){
    exit();
}
?>
<style type="text/css">
    td, th {
      text-align: left;
      padding: 2px;
    }
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
    }

    /* Firefox */
    input[type=number] {
    -moz-appearance: textfield;
    }
</style>
<table class="w3-small w3-table-all w3-hoverable">
    <thead>
        <tr>
            <th></th>
            <th>Swipe Memo Option</th>
            <th>Penalty Amount</th>
            <th>Penalty To</th>
            <th>is Penalized</th>
            <th>is UpdateTime</th>
            <th>User ID</th>
            <th>Station ID</th>
            <th>TimeStamp</th>
        </tr>
    </thead>
    <tbody>
        <tr id="new_swipe">
            <td><input type="hidden" id="swipe_code" value=""></td>
            <td><input id="swipe_desc" type="text" name="swipe_desc" class="w3-input w3-small w3-padding-small w3-border w3-border-silver w3-round-medium"></td>
            <td><input id="penalty_amt" type="number" name="penalty_amt" class="w3-input w3-small w3-padding-small w3-border w3-border-silver w3-round-medium" style="width: 200px;"></td>
            <td>
                <select id="penalty_to" type="select" name="penalty_to" class="w3-select w3-small w3-padding-small w3-border w3-border-silver w3-round-medium" style="width: 200px;">
                    <option class="w3-small" value="">Select Options..</option>
                    <option class="w3-small" value="0">None</option>
                    <?php
                        $ded = $db->query("SELECT * FROM $db_hris.`deduction` WHERE !is_computed ORDER BY `deduction_description`");
                        if ($ded->rowCount()) {
                            $count = 0;
                            while ($ded_data = $ded->fetch(PDO::FETCH_ASSOC)) {
                                $ded_desc=$ded_data['deduction_description'];
                                $ded_code=$ded_data['deduction_no'];
                        ?>
                        <option class="w3-small" value="<?php echo $ded_code; ?>"><?php echo $ded_desc; ?></option>
                    <?php }
                    } ?>
                </select>
            </td>
            <td>
                <select id="penalized" type="select" name="penalized" class="w3-select w3-small w3-padding-small w3-border w3-border-silver w3-round-medium" style="width: 100px;">
                    <option id="0" value="">Select Options</option>
                    <option id="yes" value="1">YES</option>
                    <option id="no" value="0">NO</option>
                </select>
            </td>
            <td>
                <select id="update_time" type="select" name="update_time" class="w3-select w3-small w3-padding-small w3-border w3-border-silver w3-round-medium" style="width: 100px;">
                    <option id="0" value="">Select Options</option>
                    <option id="yes" value="1">YES (Whole Day)</option>
                    <option id="yesh" value="2">YES (Half Day)</option>
                    <option id="no" value="0">NO</option>
                </select>
            </td>
            <td>
                <button class="w3-small w3-margin-right" id="save" onclick="save_data();"><ion-icon class="w3-large" name="save-outline" style="padding-top: 5px;"></ion-icon></button>
                <button class="w3-small w3-hide" id="clear" onclick="clear_data();"><ion-icon class="w3-large" name="trash-outline" style="padding-top: 5px;"></ion-icon></button>
            </td>
            <td colspan="2"></td>
        </tr>
    <?php

        $swipe_memo = $db->query("SELECT * FROM $db_hris.`swipe_memo_code` ORDER BY `description`");
        if ($swipe_memo->rowCount()) {
            $count = 0;
            $deduction = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:no");
            while ($swipe_memo_data = $swipe_memo->fetch(PDO::FETCH_ASSOC)) {
                $count++;
                $deduction->execute(array(":no" => $swipe_memo_data["penalty_to"]));
                if ($deduction->rowCount()) {
                    $data = $deduction->fetch(PDO::FETCH_ASSOC); ?>
                    <tr id="<?php echo $swipe_memo_data['swipe_memo_code']; ?>" style="cursor: pointer;" onclick="edit_data(<?php echo $swipe_memo_data['swipe_memo_code']; ?>)">
                        <td><?php echo $count; ?></td>
                        <td><?php echo $swipe_memo_data['description'];; ?></td>
                        <td><?php echo $swipe_memo_data['penalty_amount']; ?></td>
                        <td><?php echo $data['deduction_description']; ?></td>
                        <td><?php if($swipe_memo_data['is_penalized']) echo "YES"; else echo "NO"; ?></td>
                        <td><?php if($swipe_memo_data['is_update_time'] == 1) echo "YES (Whole Day)"; elseif($swipe_memo_data['is_update_time'] == 2) echo "YES (Half Day)"; else echo "NO"; ?></td>
                        <td><?php echo $swipe_memo_data['user_id']; ?></td>
                        <td><?php echo $swipe_memo_data['station_id']; ?></td>
                        <td><?php echo $swipe_memo_data['_timestamp']; ?></td>
                    </tr>
                <?php
                }else{ ?>
                    <tr id="<?php echo $swipe_memo_data['swipe_memo_code']; ?>" style="cursor: pointer;" onclick="edit_data(<?php echo $swipe_memo_data['swipe_memo_code']; ?>)">
                        <td><?php echo $count; ?></td>
                        <td><?php echo $swipe_memo_data['description']; ?></td>
                        <td><?php echo $swipe_memo_data['penalty_amount']; ?></td>
                        <td>NONE</td>
                        <td><?php if($swipe_memo_data['is_penalized']) echo "YES"; else echo "NO"; ?></td>
                        <td><?php if($swipe_memo_data['is_update_time'] == 1) echo "YES (Whole Day)"; elseif($swipe_memo_data['is_update_time'] == 2) echo "YES (Half Day)"; else echo "NO"; ?></td>
                        <td><?php echo $swipe_memo_data['user_id']; ?></td>
                        <td><?php echo $swipe_memo_data['station_id']; ?></td>
                        <td><?php echo $swipe_memo_data['_timestamp']; ?></td>
                    </tr>
                <?php
                }
            }
        }
                ?>
        </tbody>
    </table>
<?php
?>

<script type="text/javascript">

    const src = "page/swipe_memo";

    function clear_data(){
        $('input#swipe_code').val('');
        $('input#swipe_desc').val('');
        $('input#penalty_amt').val('');
        $('select#penalty_to').val('');
        $('select#penalized').val('');
        $('select#update_time').val('');
    }

    function save_data(){
        let memo_no = $('input#swipe_code').val();
        let swipe_desc = $('input#swipe_desc').val();
        let penalty = $('input#penalty_amt').val();
        let to = $('select#penalty_to').val();
        let penalized = $('select#penalized').val();
        let update_time = $('select#update_time').val();
        console.table(memo_no,swipe_desc,penalty,to,penalized,update_time);
        $.ajax({
            url: src,
            method: "POST",
            data:{
                cmd: "add-update-swipe-data",
                memo_no : memo_no,
                swipe_desc : swipe_desc,
                penalty : penalty,
                to : to,
                penalized : penalized,
                update_time : update_time
            },
            success: function(data){
                if (data !== ""){
                    var _return = jQuery.parseJSON(data);
                    if(_return.status === "success"){
                        swipe_memo();
                        $('#save').addClass('w3-hide');
                        $('#clear').addClass('w3-hide');
                    }else{
                        w2alert("Sorry, there was a problem in server connection!");
                    }
                }
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }

function edit_data(memo_no){
    $.ajax({
        url: src,
        method: "POST",
        data:{
            cmd: "get-swipe-data",
            memo_no : memo_no
        },
        success: function (data){
            if (data !== ""){
                var _return = jQuery.parseJSON(data);
                if(_return.status === "success"){
                    $('input#swipe_code').val(memo_no);
                    $('input#swipe_desc').val(_return.swipe_desc);
                    $('input#penalty_amt').val(_return.penalty_amt);
                    $('select#penalty_to').val(_return.penalty_to);
                    $('select#penalized').val(_return.penalized);
                    $('select#update_time').val(_return.update);
                    $('#save').removeClass('w3-hide');
                    $('#clear').removeClass('w3-hide');
                }else{
                    w2alert("Sorry, No DATA found!");
                }
            }
        },
        error: function (){
            w2alert("Sorry, there was a problem in server connection!");
        }
    })
}

</script>