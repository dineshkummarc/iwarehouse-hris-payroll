<div class="w3-container w3-margin-bottom">
    <div style="margin-top: -30px;" class="w3-orange w3-text-white w3-padding-small">
        <span class="w3-medium" style="font-weight: bolder;" id="ded_name"></span>
        <span class="w3-medium" style="font-weight: bolder;" id="cbal"></span>
    </div>
    <div class="w3-padding-top">
        <label class="w3-small">ADD Deduction Balance:</label>
    </div>
    <div>
        <input type="hidden" name="emp_no" id="emp_no" value="" />
        <input type="hidden" name="ded_no" id="ded_no" value="" />
        <input type="hidden" name="bal1" id="bal1" value=""/>
        <input type="int" name="bal" id="bal" value="" required style="width: 100%;" autocomplete="off"/>
    </div>
    <div class="w3-padding-top">
        <label class="w3-small">Deduction Amount:</label>
    </div>
    <div>
        <input type="int" name="amount" id="amount" value="" required style="width: 100%;" autocomplete="off"/>
    </div>
</div>
<div class="w2ui-buttons w3-padding w3-silver">
    <div class="w3-center">
        <button class="w3-round-medium w3-hover-silver w3-border w3-small w3-padding-left w3-padding-right w3-hover-black" style="padding-top: 7px; padding-bottom: 7px; cursor: pointer;" name="save" id="save" onclick="add_ded()">SAVE DEDUCTION</button>
    </div>
</div>
<script>
    $("#bal, #amount").w2field("text");
</script>
