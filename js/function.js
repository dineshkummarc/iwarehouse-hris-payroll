
 /*password checker start*/
function checkStrength(password) {
    var strength = 0;
    if (password.length < 6) {
        $('#passwordstrength').removeClass();
        $('#passwordstrength').addClass('SHORT');
        return "SHORT";
    }
    if (password.length > 7) {
        strength += 1;
    }
    if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) {
        strength += 1;
    }

    if (password.match(/([a-zA-Z])/) && password.match(/([0-9])/)) {
        strength += 1;
    }

    if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) {
        strength += 1;
    }

    if (password.match(/(.*[!,%,&,@,#,$,^,*,?,_,~].*[!,%,&,@,#,$,^,*,?,_,~])/)) {
        strength += 1;
    }

    if (strength < 2) {
        $('#passwordstrength').removeClass();
        $('#passwordstrength').addClass('WEAK');
        return "WEAK";
    }
    else if (strength === 2) {
        $('#passwordstrength').removeClass();
        $('#passwordstrength').addClass('GOOD');
        return "GOOD";
    } else {
        $('#passwordstrength').removeClass();
        $('#passwordstrength').addClass('STRONG');
        return "STRONG";
    }
}

function passStrenghth() {
    $('#passwordstrength').html(checkStrength($('#password').val()));
}

function checkMatch() {
    var matched;
    $("#passwordmatch").removeClass();
    if ($('#password').val() === $('#password1').val()) {
        $("#passwordmatch").html("MATCH");
        $("#passwordmatch").addClass('STRONG');
        matched = true;
    } else {
        $("#passwordmatch").html("DOES NOT MATCH");
        $("#passwordmatch").addClass('SHORT');
        matched = false;
    }
    return matched;
}

/*password checker end*/

//login
$(document).ready(function(){
    
    $('#login_form').on('submit', function(event){
        if($('#username').val() == '' && $('#password').val() == ''){
            $('#username').focus();
            $('#password').focus();
        }else{
            event.preventDefault();
            if($.isNumeric($('#username').val())){
                $.ajax({
                    url: 'page/login',
                    type: 'post',
                    data: { 
                        cmd: 'check_account_id',
                        uid : $('#username').val()
                    },
                    beforeSend: function(){
                        $('#login_text').text('Validating');
                        $('#login_btn').prop('disabled', true);
                        $('#username').prop('disabled', true);
                        $('#password').prop('disabled', true);
                    },
                    success: function (data) {
                        if (data == "make_user"){
                            $('#login_form').addClass('w3-hide');
                            $('#reg_form').removeClass('w3-hide');
                        }else{
                            $('#err_login').html(data);
                        }
                        setTimeout(function(){
                            $('#err_login').text('');
                        },3000);
                        $('#login_text').text('CONTINUE');
                        $('#login_btn').prop('disabled', false);
                        $('#username').prop('disabled', false);
                        $('#password').prop('disabled', false);
                    }
                });
            }else{
                $.ajax({
                    url: 'page/login',
                    type: 'post',
                    data: { 
                        cmd: 'login',
                        uname : $('#username').val(),
                        pword : $('#password').val()
                    },
                    beforeSend: function(){
                        $('#login_text').text('Validating');
                        $('#login_btn').prop('disabled', true);
                        $('#username').prop('disabled', true);
                        $('#password').prop('disabled', true);
                    },
                    success: function (data) {
                        if (data == "success"){
                            window.open('./home');
                        }else{
                            $('#err_login').html(data);
                        }
                        setTimeout(function(){
                            $('#err_login').text('');
                        },3000);
                        $('#login_text').text('CONTINUE');
                        $('#login_btn').prop('disabled', false);
                        $('#username').prop('disabled', false);
                        $('#password').prop('disabled', false);
                    }
                });
            }
        }
    });


    //reg
    $('#reg_form').on('submit',function(event){
        if($('#password').val() === $('#cfn_password').val()){
            event.preventDefault();
            $.ajax({
                url: 'page/login',
                type: 'post',
                data: { 
                    cmd: 'register_user',
                    uid : $('#username').val(),
                    uname: $('#make_uid').val(),
                    pword : $('#cfn_password').val()
                },
                beforeSend: function(){
                    $('#reg_text').text('Registering');
                    $('#reg_btn').prop('disabled', true);
                    $('#username').prop('disabled', true);
                    $('#make_uid').prop('disabled', true);
                    $('#password').prop('disabled', true);
                    $('#cfn_password').prop('disabled', true);
                },
                success: function (data) {
                    if (data == "success"){
                        alert("Registration Success! You can now login!");
                        setTimeout(function(){
                            $('#err_login').text('');
                        },3000);
                        $('#reg_text').text('SUBMIT');
                        $('#reg_btn').prop('disabled', false);
                        $('#username').prop('disabled', false);
                        $('#make_uid').prop('disabled', false);
                        $('#password').prop('disabled', false);
                        $('#cfn_password').prop('disabled', false);
                        $('#login_form').removeClass('w3-hide');
                        $('#reg_form').addClass('w3-hide');
                    }else{
                        alert('Password does not match!');
                    }
                }
            });
        }else{
            alert('Password does not match!');
        }
    });
});