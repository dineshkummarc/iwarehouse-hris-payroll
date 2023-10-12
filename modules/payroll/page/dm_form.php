<div class="w3-container w3-margin-bottom">
    <div style="margin-top: -30px;" class="w3-orange w3-text-white w3-padding-small">
        <span class="w3-medium" style="font-weight: bolder;" id="cm_name"></span>
    </div>
    <div class="w3-padding-top w3-padding-small">
        <span class="w3-medium" style="font-weight: bolder;" id="avail_amount"></span>
    </div>
    <div class="w3-padding-top">
        <label class="w3-small">DEDUCTED AMOUNT/CM AMOUNT:</label>
    </div>
    <div>
        <input type="hidden" name="emp_no" id="emp_no" value="" />
        <input type="hidden" name="ded_no" id="ded_no" value="" />
        <input class="w3-input w3-border w3-small w3-input-focus w3-round-medium" type="text" name="cm_amount" id="cm_amount" autocomplete="off" placeholder="Enter CM amount.."/>
    </div>
    <div class="w3-padding-top">
        <label class="w3-small">Remarks:</label>
    </div>
    <div>
    <textarea type="text" class="w3-input w3-border w3-small w3-input-focus w3-round-medium" name="remarks" id="remarks" style="width: 100%; height: 50px; resize: none;" autocomplete="off" placeholder="Provid Remarks"></textarea>
    </div>
</div>
<div class="w2ui-buttons w3-padding w3-silver">
    <div class="w3-center">
        <button class="w3-round-medium w3-hover-silver w3-border w3-small w3-padding-left w3-padding-right w3-hover-black" style="padding-top: 7px; padding-bottom: 7px; cursor: pointer;" name="cm_ded" id="cm_ded" onclick="cm_ded()">CM DEDUCTION</button>
    </div>
</div>
<script>
    $("#cm_amount, #remarks").w2field("text");
</script>
