
<script type="text/javascript">
var parent, levels;

$(function () {    
    $('#grid').w2grid({ 
        name: 'grid', 
        show: { 
            toolbar: true,
            footer: true,
            toolbarAdd: true,
            toolbarDelete: true,
            lineNumbers: true
        },
        columns: [],
        onAdd: function (event) {
            new_program();
        },
        onDelete: function (event) {
            event.preventDefault();
            if(w2ui.grid.getSelection().length > 0){
                w2confirm('Delete this Config?', function (btn){
                    if(btn === 'No'){
                        w2ui['grid'].refresh();
                    }else{
                        delete_config(w2ui.grid.getSelection()[0]);
                    }
                });
            }
        },
        records: []
    });    
});

$(document).ready(function() {
    get_default();
});

function get_default(){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
        url: src,
        type: "post",
        data: {
            cmd: "default"
        },
        dataType: "json",
        success: function (jObject){
            if (jObject.status === "success") {
                w2ui['grid'].columns = jObject.columns;
                w2ui['grid'].clear();
                w2ui['grid'].refresh();
                w2ui['grid'].add(jObject.records);
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

function new_program() {
    if (!w2ui.form) {
        $().w2form({
            name: 'form',
            style: 'border: 0px; background-color: transparent;',
            formURL: "./page/prog_form.html",
            fields: [
                { field: 'prog_id', type: 'text', required: false },
                { field: 'menu_name', type: 'text', required: true },
                { field: 'prog_name', type: 'text', required: true },
                { field: 'enable', type: 'checkbox', required: true },
                { field: 'plevel', type: 'list', required: true, options: {items: levels} },
                { field: 'parent', type: 'list', required: true, options: {items: parent} },
                { field: 'functions', type: 'text', required: true },
                { field: 'seq', type: 'text', required: true }
            ],
            actions: {
                "save" : function () {
                    save_prog();
                },
                "reset" : function () { this.clear(); }
            }
        });
    }
    $().w2popup('open', {
        title   : 'New Program',
        body    : '<div id="form" style="width: 100%; height: 100%;"></div>',
        style   : 'padding: 15px 0px 0px 0px',
        width   : 700,
        height  : 450, 
        showMax : true,
        onToggle: function (event) {
            $(w2ui.form.box).hide();
            event.onComplete = function () {
                $(w2ui.form.box).show();
                w2ui.form.resize();
            }
        },
        onOpen: function (event) {
            event.onComplete = function () {
                get_parent();
                $('#w2ui-popup #form').w2render('form');
            }
        }
    });
}


function save_prog(){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    console.log(w2ui.form.record);
    $.ajax({
        url: src,
        type: "post",
        data: {
            cmd: "save-config",
            record: w2ui.form.record
        },
        dataType: "json",
        success: function (jObject){
            w2popup.close();
            w2utils.unlock(div);
            if (jObject.status === "success"){
                get_default();
                w2ui.form.clear();
            }else{
                w2alert(jObject.data.message);
            }
        },
        error: function () {
            w2alert("Sorry, There was a problem in server connection or Session Expired!");
            w2utils.unlock(div);
        }
    });
}

function get_parent(){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
        url: src,
        type: "post",
        data: {
            cmd: "get-parent"
        },
        success: function (data){
            if (data !== ""){
                var _return = jQuery.parseJSON(data);
                if(_return.status === "success"){
                    parent = _return.parent;
                    levels = _return.level;
                    $('input#parent').w2field("list", { items: parent });
                    $('input#plevel').w2field("list", { items: levels });
                    w2utils.unlock(div);
                }else{
                    w2alert(_return.message);
                    w2utils.unlock(div);
                }
            }
        },
        error: function (){
            w2alert("Sorry, there was a problem in server connection!");
        }
    });
}

function delete_config(recid){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
        url: src,
        type: "post",
        data: {
            cmd: "del-config",
            recid: recid
        },
        success: function (data){
            if (data !== ""){
                var _return = jQuery.parseJSON(data);
                if(_return.status === "success"){
                    get_default();
                }else{
                    w2alert(_return.message);
                    w2utils.unlock(div);
                }
            }
        },
        error: function (){
            w2alert("Sorry, there was a problem in server connection!");
        }
    });
}
</script>