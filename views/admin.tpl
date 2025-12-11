<div class="container">
  <h2 class="h4 mb-3 text-light">{$lang.character_status}</h2>

  {if isset($message) && $message}
    <div class="alert alert-info">{$message}</div>
  {/if}

  {if !empty($transferdata)}
    <form method="post" action="{$url}charactertransfer/admin">
      <input type="hidden" name="{$csrf_name}" value="{$csrf_hash}" />
      <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>{$lang.id}</th>
              <th>{$lang.account_id}</th>
              <th>{$lang.character}</th>
              <th>{$lang.race}</th>
              <th>{$lang.gender}</th>
              <th>{$lang.class}</th>
              <th>{$lang.server}</th>
              <th>{$lang.status}</th>
              <th class="text-nowrap">{$lang.options}</th>
            </tr>
          </thead>
          <tbody>
            {foreach $transferdata as $row}
              <tr>
                <td>{$row.id}</td>
                <td>{$row.userid}</td>
                <td>{$row.charactername}</td>
                <td>{$row.race}</td>
                <td>{$row.gender}</td>
                <td>{$row.class}</td>
                <td>{$row.realm}</td>
                <td>{$row.status}</td>
                <td class="text-nowrap">
                  <a href="{$url}charactertransfer/admin/view/{$row.id}" target="_blank" class="btn btn-sm btn-outline-primary me-1">{$lang.review}</a>
                  <button type="submit" class="btn btn-sm btn-danger me-1" name="option" value="deny_{$row.id}">{$lang.deny}</button>
                  <button type="submit" class="btn btn-sm btn-success me-1" name="option" value="approve_{$row.id}">{$lang.approve}</button>
                  <button type="submit" class="btn btn-sm btn-outline-danger" name="option" value="delete_{$row.id}">{$lang.delete}</button>
                </td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      </div>
    </form>
  {else}
    <div class="alert alert-warning mb-0">{$lang.no_chardump_admin}</div>
  {/if}
</div>
