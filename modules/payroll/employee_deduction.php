<?php
$program_code = 19;
require_once('../../system.config.php');
require_once('../../common_functions.php');
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
?>
<div id="employee_ded" class="w3-col s12 w3-small w3-padding-small">
    <div id="my_toolbar" style="padding: 4px; border: 1px solid #dfdfdf; border-radius: 3px;"></div>
    <div class="w3-col s4 m4">
        <div id="my_grid" style="width: 95%;"></div>
    </div>
    <div class="w3-col s8 m8 w3-hide" id="ded_ledger">
        <div id="my_grid1" style="width: 100%;"></div>
    </div>
</div>
<script type="text/javascript">

    $(document).ready(function () {
        var c = $("div#employee_ded");
        var g = $("#my_grid, #my_grid1");
        var h = window.innerHeight - 100;
        c.css("height", h);
        g.css("height", h - 50);
        get_default();
    });

    $(function () {
        $('#my_toolbar').w2toolbar({
            name: 'my_toolbar',
            items: [
                { type: 'html', html: '<input id="emp_list" type="list" class="w3-input w3-margin-left" size="50" />' },
                { type: 'button',  id: 'get',  text: 'GET EMPLOYEE' },
                { type: 'break' },
                { type: 'button',  id: 'show',  text: 'SHOW ALL EMPLOYEES WITH DEDUCTIONS' },
                { type: 'button',  id: 'close',  text: 'CLOSE', hidden: true }
            ],
            onClick: function (event) {
                switch (event.target){
                    case 'get':
                        get_employee(1);
                    break;
                    case 'show':
                        get_all_employee(0);
                    break;
                    case 'close':
                        $("#emp_list").prop('readonly', false);
                        $("#ded_ledger").addClass("w3-hide");
                        w2ui.my_grid.reset();
                        w2ui.my_grid.clear();
                        w2ui.my_grid1.reset();
                        w2ui.my_grid1.clear();
                        w2ui.my_toolbar.show('show');
                        w2ui.my_toolbar.hide('close');
                        w2ui.my_grid.toolbar.show("add");
                        w2ui.my_grid1.toolbar.hide("cm");
                        w2ui.my_toolbar.enable('get');
                    break;
                }
            }
        }); 

        $('#my_grid').w2grid({ 
            name: 'my_grid', 
            show: { 
                toolbar: true,
                footer: false,
                lineNumbers: true,
                toolbarReload: false,
                toolbarSearch: false,
                toolbarInput: false,
                toolbarColumns: false,
            },
            multiSearch: false,
            columnGroups: [],
            columns: [],
            records: [],
            onUnselect: function(event) {
                $("#ded_ledger").addClass("w3-hide");
                w2ui.my_grid1.reset();
                w2ui.my_grid1.clear();
                w2ui.my_grid.toolbar.disable("add");
            },
            onSelect: function(event) {
                if(event.recid > 1000){
                    get_ded_ledger(event.recid);
                }else{
                    view_deduction_details(event.recid);
                }
                w2ui.my_grid.toolbar.enable("add");
            },
            toolbar: {
                items: [
                    { type: 'spacer' },
                    { type: 'button',  id: 'add',  text: 'ADD BALANCE', icon: 'fa-solid fa-plus', disabled: true }
                ],
                onClick: function (event) {
                    switch (event.target){
                        case "add":
                            $().w2popup('open',{
                                showMax: false,
                                showClose: true,
                                body: '<div id="form" style="width: 100%; height: 150px; margin-top: 50px;"></div>',
                                width: 450,
                                height: 300,
                                title: "ADD/NEW DEDUCTION",
                                onOpen: function (event) {
                                    event.onComplete = function () {
                                        var div = $('#main');
                                        w2utils.lock(div, 'Please wait..', true);
                                        $("div#form").load("./modules/payroll/page/balance_form.php");
                                        $.ajax({
                                            url: src,
                                            type: "post",
                                            data: {
                                                cmd: "new-emp-ded",
                                                recid : w2ui.my_grid.getSelection()[0]
                                            },
                                            dataType: "json",
                                            success: function (jObject){
                                                if (jObject !== ""){
                                                    if(jObject.status === "success"){
                                                        setTimeout(() => {
                                                            $('input#emp_no').val(jObject.emp_no);
                                                            $('input#ded_no').val(jObject.ded_no);
                                                            $('input#amount').val(jObject.ded_amt);
                                                            $('input#bal1').val(jObject.ded_bal);
                                                            $('span#ded_name').text(jObject.ded_name);
                                                            $('span#cbal').text(jObject.ded_bal);
                                                            w2utils.unlock(div);
                                                        }, 200);
                                                    }else{
                                                        w2alert(jObject.message);
                                                        w2utils.unlock(div);
                                                    }
                                                    w2utils.unlock(div);
                                                }
                                            },
                                            error: function (){
                                            w2alert("Sorry, there was a problem in server connection!");
                                            w2utils.unlock(div);
                                            }
                                        });
                                    };
                                }
                            });
                        break;
                    }
                }
            }
        });

        $('#my_grid1').w2grid({ 
            name: 'my_grid1', 
            show: { 
                toolbar: true,
                footer: false,
                lineNumbers: false,
                toolbarReload: false,
                toolbarSearch: false,
                toolbarInput: false,
                toolbarColumns: false,
            },
            multiSearch: false,
            columnGroups: [],
            columns: [],
            records: [],
            toolbar: {
                items: [
                    { type: 'spacer' },
                    { type: 'button',  id: 'cm',  text: 'DM BALANCE', icon: 'fa-solid fa-pencil', hidden: true }
                ],
                onClick: function (event) {
                    switch (event.target){
                        case "cm":
                            if(w2ui.my_grid1.getSelection().length > 0){
                                $().w2popup('open',{
                                    showMax: false,
                                    showClose: true,
                                    body: '<div id="form" style="width: 100%; height: 150px; margin-top: 50px;"></div>',
                                    width: 450,
                                    height: 300,
                                    title: "CM DEDUCTION",
                                    onOpen: function (event) {
                                        event.onComplete = function () {
                                            var div = $('#main');
                                            w2utils.lock(div, 'Please wait..', true);
                                            $("div#form").load("./modules/payroll/page/dm_form.php");
                                            $.ajax({
                                                url: src,
                                                type: "post",
                                                data: {
                                                    cmd: "get-ded-data",
                                                    recid : w2ui.my_grid1.getSelection()[0]
                                                },
                                                dataType: "json",
                                                success: function (jObject){
                                                    if (jObject !== ""){
                                                        if(jObject.status === "success"){
                                                            setTimeout( function(){
                                                                $('input#emp_no').val(jObject.emp_no);
                                                                $('input#ded_no').val(jObject.ded_no);
                                                                $('span#avail_amount').text(jObject.avail_amount);
                                                                $('span#cm_name').text(jObject.cm_name);
                                                            }, 100);
                                                            w2utils.unlock(div);
                                                        }else{
                                                            w2alert(jObject.message);
                                                            w2utils.unlock(div);
                                                        }
                                                        w2utils.unlock(div);
                                                    }
                                                },
                                                error: function (){
                                                w2alert("Sorry, there was a problem in server connection!");
                                                w2utils.unlock(div);
                                                }
                                            });
                                        };
                                    }
                                });
                            }else{
                                w2alert("Please select deduction to CM");
                            }
                        break;
                    }
                }
             }
        });
    });

    function get_default(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-default"
            },
            dataType: "json",
            success: function (jObject){
                if (jObject !== ""){
                    if(jObject.status === "success"){
                        $('input#emp_list').w2field('list', { items: jObject.employee_list });
                    }else{
                        w2alert("Sorry, No DATA found!");
                    }
                }
                w2utils.unlock(div);
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

    function get_employee(option){
        $("#ded_ledger").addClass("w3-hide");
        w2ui.my_grid1.reset();
        w2ui.my_grid1.clear();
        if($("#emp_list").val() === ""){
            $("#emp_list").focus();
        }else{
            var recid = $('input#emp_list').w2field().get().id;
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            w2ui.my_grid.clear();
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-employee-deductions",
                    recid : recid,
                    option : option
                },
                dataType: "json",
                success: function (jObject){
                    if (jObject.status === "success") {
                        w2ui.my_grid.columnGroups = jObject.col_group;
                        w2ui.my_grid.columns = jObject.columns;
                        w2ui.my_grid.refresh();
                        w2ui.my_grid.add(jObject.records);
                        w2utils.unlock(div);
                    }else{
                        w2alert(jObject.message);
                        w2utils.unlock(div);
                    }
                },
                error: function (){
                    w2alert("Sorry, there was a problem in server connection!");
                    w2utils.unlock(div);
                }
            });
        }
    }

    function get_all_employee(option){
        $("#ded_ledger").addClass("w3-hide");
        w2ui.my_grid1.reset();
        w2ui.my_grid1.clear();
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        w2ui.my_grid.reset();
        w2ui.my_grid.clear();
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "show-all-emp-ded",
                option : option
            },
            dataType: "json",
            success: function (jObject){
                if (jObject.status === "success") {
                    w2ui.my_grid.columnGroups = jObject.col_group;
                    w2ui.my_grid.columns = jObject.columns;
                    w2ui.my_grid.refresh();
                    w2ui.my_grid.add(jObject.records);
                    w2ui.my_grid.toolbar.hide('add');
                    w2ui.my_grid1.toolbar.show('cm');
                    $("#emp_list").prop('readonly', true);
                    w2ui.my_toolbar.disable('get');
                    w2ui.my_toolbar.hide('show');
                    w2ui.my_toolbar.show('close');
                    w2utils.unlock(div);
                }else{
                    w2alert(jObject.message);
                    w2utils.unlock(div);
                }
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

    function get_ded_ledger(recid){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        w2ui.my_grid1.clear();
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-deduction-ledger",
                recid : recid
            },
            dataType: "json",
            success: function (jObject){
                if (jObject.status === "success") {
                    w2ui.my_grid1.columnGroups = jObject.col_group;
                    w2ui.my_grid1.columns = jObject.columns;
                    w2ui.my_grid1.refresh();
                    w2ui.my_grid1.add(jObject.records);
                    $("#ded_ledger").removeClass("w3-hide");
                    w2utils.unlock(div);
                }else{
                    w2alert(jObject.message);
                    w2utils.unlock(div);
                }
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

    function view_deduction_details(recid){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        w2ui.my_grid1.clear();
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "view-deduction-details",
                recid : recid
            },
            dataType: "json",
            success: function (jObject){
                if (jObject.status === "success") {
                    w2ui.my_grid1.columnGroups = jObject.col_group;
                    w2ui.my_grid1.columns = jObject.columns;
                    w2ui.my_grid1.refresh();
                    w2ui.my_grid1.add(jObject.records);
                    $("#ded_ledger").removeClass("w3-hide");
                    w2utils.unlock(div);
                }else{
                    w2alert(jObject.message);
                    w2utils.unlock(div);
                }
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

    function add_ded(){
        var data = get_data();
        if (data !== "") {
            save_update_ded(data);
        }else{
            w2alert("No data to validate!");
        }
    }

    function get_data(){
        var data = {}, record = "";
        data["emp_no"] = $("#emp_no").val();
        data["ded_no"] = $("#ded_no").val();
        data["ded_bal"] = $("#bal").val();
        data["ded_bal1"] = $("#bal1").val();
        data["ded_amt"] = $("#amount").val();
        record = data;
        return record;
    }

    function save_update_ded(data){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        w2ui.my_grid1.clear();
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "add-update-deductions",
                record : data
            },
            dataType: "json",
            success: function (jObject){
                if (jObject.status === "success") {
                    w2ui.my_grid.toolbar.disable("add");
                    w2popup.close();
                    get_employee(1);
                    get_ded_ledger(jObject.recid);
                    w2utils.unlock(div);
                }else{
                    w2alert(jObject.message);
                    w2utils.unlock(div);
                }
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

    function cm_ded(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "dm-balance",
                emp_no : $("#emp_no").val(),
                ded_no : $("#ded_no").val(),
                cm_amount : $("#cm_amount").val(),
                remarks : $("#remarks").val()
            },
            dataType: "json",
            success: function (jObject){
                if (jObject.status === "success") {
                    w2ui.my_grid.toolbar.disable("add");
                    w2popup.close();
                    get_all_employee(0)
                    w2utils.unlock(div);
                }else{
                    w2alert(jObject.message);
                    w2utils.unlock(div);
                }
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

</script>