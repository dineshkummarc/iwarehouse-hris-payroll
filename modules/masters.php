<?php
error_reporting(0);
$program_code = 1;
require_once('../common/functions.php');
include('system/system.config.php');

function get_pin_id() {
    global $db, $hris;
    $user = $db->prepare("SELECT * FROM $hris.`master_data` WHERE `pin`=:id");
    $value = 100000000;
    $count = 0;
    while ($count < $value) {
        $random = substr(number_format(RAND(1, $value) + $value, 0, '.', ''), -2);
        $user->execute(array(":id" => $random));
        if ($user->rowCount()) {
            if ($count++ > $value) {
                $random = 0;
                break;
            }
        } else {
            break;
        }
    }
    $id = date("ym") . $random;
    return $id;
}

?>
<div id="form" style="width: 100%; height: 500px;"></div>
<script type="text/javascript">

    var pin = '<?php echo get_pin_id(); ?>';
    var date = '<?php echo date('m/d/Y'); ?>';
    var emp_no = '<?php echo $_GET["emp_no"]; ?>';


    var c = $("div#form");
    var h = window.innerHeight - 100;
    c.css("height", h);

    var gender = [{id :'1',text: 'Male'}, {id: '2',text: 'Female'}];
    var cstatus = ['Single', 'Married', 'Widow(er)', 'Separated'];
    var store;
    var position;
    const src = "page/master1";

    $(document).ready(function(){
        if(emp_no != 0){
            setTimeout(function(){
                get_position();
                get_store();
                get_emp_data(emp_no);
            },100);
        }else{
            setTimeout(function(){
                get_position();
                get_store();
                $('#emp_no').val(pin);
                $('#edate').val(date);
            },100); 
        }   
    });

    function get_position(){
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-position"
            },
            success: function (data){
                if (data !== ""){
                    var _return = jQuery.parseJSON(data);
                    if(_return.status === "success"){
                        position = _return.position;
                        $('input#position').w2field('list', { items: position });
                    }else{
                        w2alert("Sorry, No DATA found!");
                    }
                }
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }

    function get_emp_data(emp_no){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-emp-data",
                emp_no : emp_no
            },
            success: function (data){
                if (data !== ""){
                    var _return = jQuery.parseJSON(data);
                    if(_return.status === "success"){
                        $('img#pictid').attr('src',_return.profile_pic);
                        $('input#last_name').val(_return.last_name);
                        $('input#first_name').val(_return.first_name);
                        $('input#middle_name').val(_return.middle_name);
                        $('input#bday').val(_return.bday);
                        $('input#gender').w2field().set({id: _return.sex_id, text: _return.gender});
                        $('input#cs').w2field().set({text:_return.cs});
                        $('input#position').w2field().set({id: _return.pos_no, text:_return.position_name});
                        $('input#bday').val(_return.bday);
                        $('input#edate').val(_return.edate);
                        $('textarea#c_address').val(_return.c_address);
                        $('textarea#p_address').val(_return.p_address);
                        $('input#contact').val(_return.contact);
                        $('input#store').w2field().set({id: _return.store_id, text:_return.store});
                        $('input#emp_no').val(_return.emp_no);
                        $('input#emp_no1').val(_return.emp_no);
                        $('input#atm').val(_return.atm);
                        $('input#tin').val(_return.tin);
                        $('input#sss').val(_return.sss);
                        $('input#love').val(_return.love);
                        $('input#love_prem').val(_return.love_prem);
                        $('input#phealth').val(_return.phealth);
                        $('input#sss_compute[value="' + _return.com_sss + '"]').click();
                        $('input#love_compute[value="' + _return.com_love + '"]').click();
                        $('input#tin_compute[value="' + _return.com_tax + '"]').click();
                        $('input#ph_compute[value="' + _return.com_phealth + '"]').click();
                        w2utils.unlock(div);
                    }else{
                        w2alert("Sorry, No DATA found!");
                        w2utils.unlock(div);
                    }
                }
            },
            complete: function(){
                w2utils.unlock(div);
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }


    function get_store(){
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-store"
            },
            success: function (data){
                if (data !== ""){
                    var _return = jQuery.parseJSON(data);
                    if(_return.status === "success"){
                        store = _return.store;
                        $('input#store').w2field('list', { items: store });
                    }else{
                        w2alert("Sorry, No DATA found!");
                    }
                }
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }

    function change_image(){
        $('#imgupload').click();
        $('#upload_btn').removeClass('w3-hide');
    }

    function upload_profile(){
        let profile = $('#imgupload').val();
        var profileImg = document.getElementById('imgupload');
        if(profile == ''){
            w2alert('Please select profile to upload!');
        }else{
            var fsize = profileImg.files[0].size;
            var size = Math.round((fsize / 1024));
            if(size > 5000) {
                w2alert("File size is too large! Please select lower than 5MB");
                $('#imgupload').val('');
            }else{
                uploadProfile();
            }
        }
    }

    function uploadProfile(){
        fetch('img_viewer/img-upload', {
            method  : "POST",
            body : new FormData(document.getElementById('profileImg'))
        })
        .then((response)=>{
            if(response.status==200){
                w2alert('Changes saved!');
            }else{
                w2alert('There was a problem in server connection!');
            }
        })
        .catch(err => console.log(err));
    }

    $(function () {
        $('#form').w2form({ 
            name   : 'form',
            formURL: 'page/master_form',
            fields : [
                { field: 'first_name', type: 'text', required: true },
                { field: 'last_name',  type: 'text', required: true },
                { field: 'middle_name',   type: 'text'},
                { field: 'bday', type: 'date', required: true },
                { field: 'gender', type: 'list', required:  true,
                    options: {items: gender} },
                { field: 'cs', type: 'list', required: true,
                    options: {items: cstatus} },
                { field: 'edate', type: 'date', required: true, },
                { field: 'c_address', type: 'text', required: true },
                { field: 'p_address', type: 'text' },
                { field: 'sss',   type: 'text'},
                { field: 'love',   type: 'text'},
                { field: 'love_prem',   type: 'int'},
                { field: 'contact',   type: 'text', required: true},
                { field: 'tin',   type: 'text'},
                { field: 'phealth',   type: 'text'},
                { field: 'atm',   type: 'text'},
                { field: 'emp_no',   type: 'text', required: true },
                { field: 'store',   type: 'list', required: true },
                { field: 'position',   type: 'list', required: true },
                { field: 'remarks',   type: 'text', required: true }
            ],
            toolbar: {
                items: [
                    { id: 'bt3', type: 'spacer' },
                    { id: 'close', type: 'button', caption: 'Close' },
                    { type: 'break' },
                    { id: 'save', type: 'button', caption: 'Save' }
                ],
                onClick: function (event) {
                    switch(event.target){
                        case "close":
                            close_form();
                        break;
                        case "save":
                            save_Data();
                        break;
                    }
                }
            }
        });
    });


    //save function
    function save_Data(){
        var save_update = '<?php echo $_GET["cmd"]; ?>';
        var last_name = $("#last_name").val();
        var first_name = $("#first_name").val();
        var middle_name = $("#middle_name").val();
        var bday = $("#bday").val();
        var edate = $("#edate").val();
        var gender = $("#gender").w2field().get().id;
        var status = $("#cs").val();
        var position = $("#position").w2field().get().id;
        var c_address = $("#c_address").val();
        var p_address = $("#p_address").val();
        var contact = $("#contact").val();
        var store = $("#store").w2field().get().id;
        var emp_no = $("#emp_no").val();
        var atm = $("#atm").val();
        var tin = $("#tin").val();
        var sss = $("#sss").val();
        var love = $("#love").val();
        var phealth = $("#phealth").val();
        var remarks = $("#remarks").val();
        var ctax = $("#tin_compute").is(":checked");
        var csss = $("#sss_compute").is(":checked");
        var clove = $("#love_compute").is(":checked");
        var cph = $("#ph_compute").is(":checked");
        if(last_name !== "" && first_name !== "" && bday !== "" && gender !== "" && status !== "" && position !== "" && c_address !== "" && remarks !== ""  && emp_no !== ""){
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "save-data",
                    last_name : last_name,
                    first_name : first_name,
                    middle_name : middle_name,
                    bday : bday,
                    edate : edate,
                    gender : gender,
                    status : status,
                    position : position,
                    c_address : c_address,
                    p_address : p_address,
                    contact : contact,
                    store : store,
                    emp_no : emp_no,
                    atm : atm,
                    tin : tin,
                    sss : sss,
                    love : love,
                    phealth : phealth,
                    remarks : remarks,
                    save_update : save_update,
                    ctax : ctax,
                    csss : csss,
                    clove : clove,
                    cph : cph
                },
                dataType: "json",
                success: function (jObject){
                    if (jObject.status === "success"){
                        w2ui.form.clear();
                        close_form();
                    }else{
                        w2alert(jObject.message);
                    }
                },
                error: function () {
                    w2alert("Sorry, there was a problem in server connection or Session Expired!");
                }
            });
        }else{
            w2alert("Please supply all required data!");
        }
    }
    //side functions
    function close_form(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        closeMenu();
        destroy_grid();
        $.ajax({
            url: 'home',
            success: function(data){
                $('#grid').load('page/master');
                $('#append_data').remove();
                w2utils.unlock(div);
            }
        })
    }

</script>