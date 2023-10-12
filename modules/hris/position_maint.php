<?php
error_reporting(0);
$program_code = 2;
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
<div class="w3-col s12 w3-small">
    <div class="w3-col s12 w3-margin-bottom w3-padding-small">
        <div class="w3-panel w3-bottombar w3-padding-small">
            <span class="w3-small"><b>POSITION MAINTENANCE</b></span>
            <button class="w3-button w3-red w3-right w3-round-medium" onclick="dashboard()">Close</button>
        </div>
    </div>
    <div class="w3-col s12 m4 w3-panel">
        <div class="w3-col s12 w3-margin-bottom">
            <div class="w3-col s12 w3-container">
                <input name="pos_id" type="hidden" id="pos_id"/>
                <input name="pos_name" type="text" id="pos_name" maxlength="200" style="width: 100%" class="w2ui-input w3-round-medium w3-padding-small w3-border" placeholder="Position..">
            <label class="w3-label">POSITION</label>
            </div>
        </div>
        <div class="w3-container w3-col s12">
            <textarea class="w3-padding-small" style="width: 100%; height: 200px; resize: none" placeholder="Explain job description here..." id="jobd"></textarea>
            <label class="w3-label">JOB DESCRIPTION</label>
        </div>
        <div class="w3-container w3-margin-bottom w3-col s12">
            <button class="w3-button w3-padding-small w3-right w3-round-medium w3-green w3-hover-black" id="save" onclick="save_pos()">Save</button>
            <button class="w3-button w3-padding-small w3-right w3-round-medium w3-orange w3-hover-black w3-margin-right" id="reset" onclick="reset()">Reset</button>
        </div>
    </div>
    <div class="w3-col s6 m4 w3-panel">
        <span class="w3-center">LIST OF JOBS POSITION</span>
        <div class="w3-margin-top w3-border w3-round-medium" id="pos_list"></div>
    </div>
    <div class="w3-col s6 m4 w3-panel" id="jd_list">
    </div>
</div>
<script>

    $(document).ready(function(){
        get_record();
    });

    function get_record(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-record"
            },
            success: function (data){
                $('#pos_list').html(data);
                w2utils.unlock(div);
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

    function get_jd(id){
        $('.jdclass').removeClass("w3-flat-alizarin");
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-jd",
                id : id
            },
            success: function (data){
                if (data !== ""){
                    var jObject = jQuery.parseJSON(data);
                    if (jObject.status === "success") {
                        $('#jd_list').html(jObject.data)
                        $('#jd'+id).addClass("w3-flat-alizarin");
                        $('#jd_list').show();
                        w2utils.unlock(div);
                    }else{
                        w2utils.unlock(div);
                        w2alert(jObject.message);
                    }
                }
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

    function edit_pos(id){
        $('#jd_list').hide();
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-pos-data",
                id : id
            },
            success: function (data){
                if (data !== "") {
                    var _response = jQuery.parseJSON(data);
                    if (_response.status === "success") {
                        pos_id = _response.pos_id;
                        pos_desc = _response.pos_desc;
                        job_desc = _response.job_desc;
                        $('button#save').text('Update');
                        $('input#pos_name').val(pos_desc);
                        $('input#pos_id').val(pos_id);
                        $('textarea#jobd').val(job_desc);
                        w2utils.unlock(div);
                    }else{
                        w2utils.unlock(div);
                        w2alert(_response.message);
                    }
                }
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

    function save_pos(){
        if($('#pos_id').val() == ''){
            save_position();
        }else{
            update_position();
        }
    }

    function save_position() {
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "save-data",
                pos_name: $("#pos_name").val(),
                jobd: $("#jobd").val()
            },
            dataType: "json",
            success: function (jObject) {
                if (jObject.status === "success") {
                    $("#pos_name").val("");
                    $("#jobd").val("");
                    $("#job_div").hide();
                    $("#job_div1").hide();
                    get_record();
                    w2utils.unlock(div);
                }else{
                    w2alert(jObject.message);
                }
            },
            error: function () {
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }


    function update_position() {
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "update-position",
                pos_id: $('#pos_id').val(),
                pos_name: $("#pos_name").val(),
                job_desc: $("#jobd").val()
            },
            dataType: "json",
            success: function (jObject) {
                if (jObject.status === "success") {
                    $('#pos_id').val("");
                    $("#pos_name").val("");
                    $("#jobd").val("");
                    $('button#save').text("Save");
                    $("#job_div").hide();
                    $("#job_div1").hide();
                    get_record();
                    w2utils.unlock(div);
                }else{
                    w2alert(jObject.message);
                }
            },
            error: function () {
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

    function del_pos(id) {
        w2confirm('Remove this position?', function (btn){
            if(btn === 'No'){
                get_record();
            }else{
                var div = $('#main');
                w2utils.lock(div, 'Please wait..', true);
                $.ajax({
                    url: src,
                    type: "post",
                    data: {
                        cmd: "del-pos",
                        pos_id : id
                    },
                    dataType: "json",
                    success: function (jObject) {
                        if (jObject.status === "success") {
                            get_record();
                            $("#job_div").hide();
                            $("#job_div1").hide();
                            w2utils.unlock(div);
                        }else{
                            w2alert(jObject.message);
                        }
                    },
                    error: function () {
                        w2alert("Sorry, there was a problem in server connection!");
                        w2utils.unlock(div);
                    }
                });
            }
        });
    }

    function reset(){
        $('#pos_id').val("");
        $("#pos_name").val("");
        $("#jobd").val("");
        $('#jd_list').show();
        $('button#save').text("Save");
    }
</script>
