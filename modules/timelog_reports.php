<?php
$program_code = 3;
require_once('../common/functions.php');

?>
<style type="text/css">
.w2ui-col-header, .w2ui-panel-title, .w2ui-grid-summary {text-align: center; font-weight: bolder; }
.w2ui-node-dots { display: none; }
</style>
<script>
const src = "page/attendance";
var config = {
  layout: {
    name: "layout",
    panels: [
      { type: 'top', size: 65, title: 'ATTENDANCE REPORT' },
      { type: 'main', overflow: 'hidden', resizable: true },
      { type: 'right', overflow: 'hidden', resizable: true, hidden: true, size: "40%" }
    ]
  },
  layout_attendee: {
    name: "layout_attendee",
    panels: [
      { type: 'top', size: 35 },
      { type: 'main', overflow: 'hidden', resizable: true }
    ]
  },
  toolbar: {
    name: 'toolbar',
    items: [
      { type: 'html', id: 'item1', html: '' },
      { type: 'break' },
      { type: 'button', id: 'plot', caption: 'PLOT ATTENDANCE'},
      { type: 'break'},
      { type: 'spacer'},
      { type: 'break'},
      { type: 'button', id: 'attendee', caption: 'ATTENDEE'}
    ],
    onClick: function (event){
      if(event.target === 'plot'){
        get_attendance();
      }else if(event.target === 'attendee'){
        get_attendee();
      }
    }
  },
  toolbar_attendee: {
    name: 'toolbar_attendee',
    items: [
      { type: 'html', id: 'item1', html: '' },
      { type: 'button', id: 'add', caption: 'ADD' },
      { type: 'spacer' },
      { type: 'break' },
      { type: 'button', id: 'remove', caption: 'REMOVE' },
      { type: 'break' },
      { type: 'button', id: 'close', caption: 'CLOSE' }
    ],
    onClick: function (event){
      if(event.target === 'add'){
        if($("#name").val() !== ""){
          add_attendee($("#name").val());
        }else{
          w2alert("Please select attendee to add!");
        }
      }else if(event.target === 'remove'){
        if(w2ui.grid_attendee.getSelection().length > 0){
          w2confirm('Are you sure to remove?', function (btn){
            if (btn === "Yes") {
              var record = w2ui.grid_attendee.get(w2ui.grid_attendee.getSelection()[0]);
              remove_it(record);
            }
          });
        }
      }
      if (event.target === 'close') {
        w2ui.layout.hide("right");
        w2ui.layout.unlock("top");
      }
    }
  },
  grid: {
    name: 'grid',
    show: {
      footer: true,
      toolbar: true,
      lineNumbers: true
    },
    multiSelect: true,
    columns: []
  },
  grid_attendee: {
    name: 'grid_attendee',
    show: {
      footer: true,
      toolbar: false,
      lineNumbers: true
    },
    multiSelect: false,
    columns: [
      { field: 'recid', caption: 'PIN NO', size: '100px' },
      { field: 'name', caption: 'NAME', size: '70%' }
    ]
  }
};


function remove_it(record) {
  w2ui.layout.lock("main");
  w2ui.layout.lock("right", "Please wait...", true);
  $.ajax({
    url: src,
    type: "post",
    data: {
      cmd: "remove-attendee",
      df: $("#datef").val(),
      dt: $("#datet").val(),
      record: record
    },
    success: function (data) {
      w2ui.layout.unlock("main");
      w2ui.layout.unlock("right");
      if(data !== ""){
        var _response = jQuery.parseJSON(data);
        if(_response.status === "success"){
          w2ui.grid_attendee.clear();
          w2ui.grid_attendee.add(_response.attendee);
          w2ui.grid.clear();
          w2ui.grid.add(_response.records);
        }else if(_response.status === "error"){
          w2alert(_response.message);
        }
      }
    },
    error: function () {
      w2ui.layout.unlock("main");
      w2ui.layout.unlock("right");
      alert("Sorry, there was a problem in server connection!");
    }
  });
}


function add_attendee(name) {
  w2ui.layout.lock("main");
  w2ui.layout.lock("right", "Please wait...", true);
  $.ajax({
    url: src,
    type: "post",
    data: {
      cmd: "add-attendee",
      df: $("#datef").val(),
      dt: $("#datet").val(),
      name: name
    },
    success: function (data) {
      w2ui.layout.unlock("main");
      w2ui.layout.unlock("right");
      if (data !== "") {
        var _response = jQuery.parseJSON(data);
        if (_response.status === "success") {
          w2ui.grid_attendee.clear();
          w2ui.grid_attendee.add(_response.attendee);
          $("#name").val("");
          w2ui.grid.clear();
          w2ui.grid.add(_response.records);
        } else if (_response.status === "error") {
          w2alert(_response.message);
        }
      }
    },
    error: function () {
      w2ui.layout.unlock("main");
      w2ui.layout.unlock("right");
      alert("Sorry, there was a problem in server connection!");
    }
  });
}

function get_attendee() {
  if (w2ui.layout_attendee) {
    w2ui.layout_attendee.destroy();
  }
  if (w2ui.toolbar_attendee) {
    w2ui.toolbar_attendee.destroy();
  }
  if (w2ui.grid_attendee) {
    w2ui.grid_attendee.destroy();
  }
  config.toolbar_attendee.items[0].html = '<div style="padding: 3px 10px;">ATTENDEE&nbsp;<input id="name" size="30" style="padding: 3px; border-radius: 2px; border: 1px solid silver"/></div>';
  w2ui.layout.content("right", $().w2layout(config.layout_attendee));
  w2ui.layout.lock("top");
  w2ui.layout.lock("main", "Please wait...", true);
  $.ajax({
    url: src,
    type: "post",
    data: {
      cmd: "attendee"
    },
    success: function (data) {
      w2ui.layout.unlock("top");
      w2ui.layout.unlock("main");
      if (data !== "") {
        var _response = jQuery.parseJSON(data);
        if (_response.status === "success") {
          config.grid_attendee.records = _response.records;
          w2ui.layout.content("main", $().w2grid(config.grid));
          setTimeout(function () {
            w2ui.layout_attendee.content("top", $().w2toolbar(config.toolbar_attendee));
            w2ui.layout_attendee.content("main", $().w2grid(config.grid_attendee));
            setTimeout(function () {
              $("#name").w2field('combo', {items: _response.attendee, minLength: 3, match: "contains"});
              w2ui.layout.lock("top");
              w2ui.layout.show("right");
            }, 200);
          }, 200);
        }else if (_response.status === "error") {
          w2alert(_response.message);
        }
      }
    },
    error: function () {
      w2ui.layout.unlock("main");
      w2ui.layout.unlock("top");
      alert("Sorry, there was a problem in server connection!");
    }
  });
}


function get_attendance() {
  w2ui.layout.lock("top");
  w2ui.layout.lock("main", "Please wait...", true);
  $.ajax({
    url: src,
    type: "post",
    data: {
      cmd: "plot",
      df: $("#datef").val(),
      dt: $("#datet").val()
    },
    success: function (data) {
      w2ui.layout.unlock("top");
      w2ui.layout.unlock("main");
      if (data !== "") {
        var _response = jQuery.parseJSON(data);
        if (_response.status === "success") {
          if (w2ui.grid) {
            w2ui.grid.destroy();
          }
          config.grid.columns = _response.columns;
          config.grid.records = _response.records;
          w2ui.layout.content("main", $().w2grid(config.grid));
        } else if (_response.status === "error") {
          w2alert(_response.message);
        }
      }
    },
    error: function () {
      w2ui.layout.unlock("main");
      w2ui.layout.unlock("top");
      alert("Sorry, there was a problem in server connection!");
    }
  });
}

$(document).ready(function () {
  set_ui();
});


function set_ui() {
  if (w2ui.layout) {
    w2ui.layout.destroy();
  }
  if (w2ui.toolbar) {
    w2ui.toolbar.destroy();
  }
  if (w2ui.grid) {
    w2ui.grid.destroy();
  }
  $("div.attendance").w2layout(config.layout);
  config.toolbar.items[0].html = '<div style="padding: 3px 10px;">PAYROLL DATE RANGE&nbsp;<input id="datef" class="date" size="10" style="padding: 3px; border-radius: 2px; border: 1px solid silver"/>&nbsp;&nbsp;&nbsp;<input id="datet" class="date" size="10" style="padding: 3px; border-radius: 2px; border: 1px solid silver"/></div>';
  setTimeout(function () {
    w2ui.layout.content("top", $().w2toolbar(config.toolbar));
    setTimeout(function () {
      $(":input.date").w2field("date");
    }, 200);
  }, 200);
}
</script>
<body>
  <div class="attendance" style="width: 100%; height: 600px;"></div>
</body>