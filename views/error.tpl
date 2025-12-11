<div class="container py-3">
  {if $msg}
    <div class="alert alert-danger" role="alert">{$msg}</div>
  {else}
    <div class="alert alert-danger" role="alert">{lang("donate_error_text", "donate")}</div>
  {/if}

  <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">{lang("back", "charactertransfer")|default:'Go Back'}</button>
</div>