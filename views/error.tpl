{if $msg}
    <center style="padding:10px;">{$msg}</center>
{else}
    <center style="padding:10px;">{lang("donate_error_text", "donate")}</center>
{/if}

<!-- Add a back button -->
<center style="padding:10px;">
    <button onclick="goBack()">Go Back</button>
</center>

<!-- JavaScript function to navigate back -->
<script>
    function goBack() {
        window.history.back();
    }
</script>