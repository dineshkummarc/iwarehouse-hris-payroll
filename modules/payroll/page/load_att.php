<div class="w3-container w3-margin-bottom">
    <div>
        <label class="w3-small">Select CSV file:</label>
    </div>
    <div>
        <input type="file" name="att_file" id="att_file" style="width: 100%;" autocomplete="off" accept=".csv">
    </div>
</div>
<div class="w2ui-buttons w3-padding w3-silver">
    <div class="w3-center">
        <button class="w3-round-medium w3-hover-silver w3-border w3-small w3-padding-left w3-padding-right w3-hover-black" style="padding-top: 7px; padding-bottom: 7px; cursor: pointer;" name="upload_att" id="upload_att" onclick="upload_att()">UPLOAD ATTENDANCE</button>
    </div>
    
</div>
<script>
    $("#att_file").w2field("file");
</script>