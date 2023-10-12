<div class="w3-container w3-margin-bottom">
    <div>
        <label class="w3-small">Deduction Label:</label>
    </div>
    <div>
        <input type="hidden" name="ded_id" id="ded_id" value="" />
        <input type="text" name="ded_label" id="ded_label" value="" required style="width: 100%;" autocomplete="off"/>
    </div>
    <div class="w3-padding-top">
        <label class="w3-small">Deduction Name:</label>
    </div>
    <div>
        <input type="text" name="ded_name" id="ded_name" value="" required style="width: 100%;" autocomplete="off"/>
    </div>
    <div class="w3-padding-top">
        <label class="w3-small">Deduction Option:</label>
    </div>
    <div>
        <input name="ded_option" type="list" maxlength="100" id="ded_option" style="width: 100%;">
    </div>
    <div class="w3-padding-top">
        <label class="w3-small">Schedule:</label>
    </div>
    <div>
    <?php echo str_repeat("&nbsp;", 14) ?><input type="checkbox" name="mid" id="mid" value="1">&nbsp;<label>MID</label>
    </div>
    <div>
    <?php echo str_repeat("&nbsp;", 14) ?><input type="checkbox" name="end" id="end" value="2">&nbsp;<label>END</label>
    </div>
</div>
<div class="w2ui-buttons w3-padding w3-silver">
    <div class="w3-center">
        <button class="w3-round-medium w3-hover-silver w3-border w3-small w3-padding-left w3-padding-right w3-hover-black" style="padding-top: 7px; padding-bottom: 7px; cursor: pointer;" name="save" id="save" onclick="save_ded()">SAVE DEDUCTION</button>
    </div>
</div>
<script>
    $("#ded_name, #ded_label").w2field("text");
    var ded_opt = [{id :0, text: 'Others'}, {id: 1, text: 'Invoice'}];
    $('#ded_option').w2field('list', { items: ded_opt });
</script>
