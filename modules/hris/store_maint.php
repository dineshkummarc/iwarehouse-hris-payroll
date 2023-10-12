<?php
$program_code = 8;
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
<div class="w3-col s12 w3-panel w3-small">
    <div class="w3-col s12 w3-margin-bottom w3-padding">
        <div class="w3-panel w3-bottombar w3-padding">
            <span class="w3-small"><b>STORE MAINTENANCE</b></span>
            <button class="w3-button w3-red w3-right w3-round-medium" onclick="dashboard()">Close</button>
        </div>
    </div>
    <div class="w3-col s12 m4 w3-panel w3-border w3-round-medium w3-padding-small">
        <div class="w3-col s12 w3-margin-bottom">
            <div class="w3-col s12 w3-container">
                <label class="w3-label">Store Name</label>
                <input name="id" type="hidden" id="id" maxlength="50" style="width: 100%" class="w2ui-input w3-round-medium w3-padding-small w3-border" placeholder="Store ID"/>
                <input name="store" type="text" id="store" maxlength="50" style="width: 100%" class="w2ui-input w3-round-medium w3-padding-small w3-border" placeholder="Store Name"/>
            </div>
        </div>
        <div class="w3-col s12 w3-margin-bottom">
            <div class="w3-col s12 w3-container">
                <label class="w3-label">Store Location</label>
                <textarea class="w3-padding-small w2ui-input" style="width: 100%; height: 70px; resize: none" placeholder="Store Location" id="address"></textarea>
            </div>
        </div>
        <div class="w3-container w3-margin-bottom w3-col s12">
            <button class="w3-button w3-padding-small w3-right w3-round-medium w3-green w3-hover-black" id="save" onclick="save_store()">Save</button>
            <button class="w3-button w3-padding-small w3-right w3-round-medium w3-orange w3-hover-black w3-margin-right" id="reset" onclick="reset()">Reset</button>
            <button class="w3-button w3-padding-small w3-right w3-round-medium w3-red w3-hover-black w3-margin-right" style="display: none;" id="remove" onclick="del_store()">Remove</button>
        </div>
    </div>
    <div class="w3-col s6 m8 w3-panel">
        <div class="w3-border w3-round-medium" id="store_list"></div>
    </div>
</div>
<script>

    $(document).ready(function(){
        get_store();
    });

    function get_store(){
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-store"
            },
            dataType: "json",
            success: function (jObject){
                if(jObject.status === "success"){
                    $('#store_list').html(jObject.data);
                }else{
                    w2alert("Sorry, there was a problem in server connection!");
                }
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
            }
        })
    }

    function get_store_data(id){
        $('.store_list').removeClass('w3-orange w3-text-white');
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-store-id",
                id : id
            },
            success: function (data){
                if (data !== "") {
                    var _response = jQuery.parseJSON(data);
                    if (_response.status === "success") {
                        store_id = _response.store_id;
                        store_name = _response.store_name;
                        address = _response.address;
                        $('button#save').text('Update');
                        $('button#remove').show();
                        $('input#store').val(store_name);
                        $('input#id').val(store_id);
                        $('textarea#address').val(address);
                        $('#store_'+id).addClass('w3-orange w3-text-white');
                    }
                }
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }

    function save_store(){
        if($('#id').val() == ''){
            save_stores();
        }else{
            update_store();
        }
    }

    function save_stores() {
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "save-store",
                store_name: $("#store").val(),
                store_loc: $("#address").val()
            },
            dataType: "json",
            success: function (jObject) {
                if (jObject.status === "success") {
                    $("#store").val("");
                    $("#address").val("");
                    get_store();
                }else{
                    w2alert(jObject.message);
                }
            },
            error: function () {
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }

    function update_store() {
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "update-store",
                store_id: $('#id').val(),
                store_name: $("#store").val(),
                store_loc: $("#address").val()
            },
            dataType: "json",
            success: function (jObject) {
                if (jObject.status === "success") {
                    $('#id').val("");
                    $("#store").val("");
                    $("#address").val("");
                    $('button#remove').hide();
                    $('button#save').text("Save");
                    get_store();
                }else{
                    w2alert(jObject.message);
                }
            },
            error: function () {
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }


    function del_store() {
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "del-store",
                store_id: $('#id').val()
            },
            dataType: "json",
            success: function (jObject) {
                if (jObject.status === "success") {
                    $('#id').val("");
                    $("#store").val("");
                    $("#address").val("");
                    $('button#remove').hide();
                    $('button#save').text("Save");
                    get_store();
                }else{
                    w2alert(jObject.message);
                }
            },
            error: function () {
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }

    function reset(){
        $('#id').val("");
        $("#store").val("");
        $("#address").val("");
        $('.store_list').removeClass('w3-orange w3-text-white');
        $('button#save').text("Save");
        $('button#remove').hide();
    }
</script>