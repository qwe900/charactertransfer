<div class="container">

  <div class="d-flex align-items-center justify-content-between">
    <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#howToCollapse" aria-expanded="false" aria-controls="howToCollapse">
      How To {lang("installing_chardump_addon", "charactertransfer")}
    </button>
  </div>
  <div class="collapse mt-3" id="howToCollapse">
    <div class="card card-body">
      <ol class="mb-0">
        <li>{lang("download_chardump_zip", "charactertransfer")}</li>
        <li>{lang("extract_zip_contents", "charactertransfer")}</li>
        <li>{lang("locate_wow_folder", "charactertransfer")}</li>
        <li>{lang("open_interface_folder", "charactertransfer")}</li>
        <li>{lang("create_addons_folder", "charactertransfer")}</li>
        <li>{lang("copy_chardump_folder", "charactertransfer")}</li>
        <li>{lang("restart_wow", "charactertransfer")}</li>
        <li>{lang("character_selection_screen", "charactertransfer")}</li>
        <li>{lang("check_chardump_checkbox", "charactertransfer")}</li>
        <li>{lang("login_open_bank", "charactertransfer")}</li>
        <li>{lang("logout_store_file", "charactertransfer")}</li>
        <li>{lang("choose_file_upload", "charactertransfer")}</li>
      </ol>
    </div>
  </div>

  <h2 class="h4 mt-4">{lang("character_status", "charactertransfer")}</h2>

  {if !empty($transferdata)}

  <div class="table-responsive">
    <table class="table table-dark table-striped align-middle mb-4">
      <thead>
        <tr>
          <th>{lang("character", "charactertransfer")}</th>
          <th>{lang("race", "charactertransfer")}</th>
          <th>{lang("gender", "charactertransfer")}</th>
          <th>{lang("class", "charactertransfer")}</th>
          <th>{lang("server", "charactertransfer")}</th>
          <th>{lang("status", "charactertransfer")}</th>
        </tr>
      </thead>
      <tbody>
        {foreach $transferdata as $row}
        <tr>
          <td>{$row.charactername}</td>
          <td>{$row.race}</td>
          <td>{$row.gender}</td>
          <td>{$row.class}</td>
          <td>{$row.realm}</td>
          <td>{$row.status}</td>
        </tr>
        {/foreach}
      </tbody>
    </table>
  </div>
  {else}
  <p>{lang("no_chardump_uploaded", "charactertransfer")}</p>
  {/if}
  <div class="card mt-3 mb-4">
    <div class="card-body">
      <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="{$csrf_name}" value="{$csrf_hash}" />
        <div class="mb-3">
          <label for="fileToUpload" class="form-label">{lang("select_file_upload", "charactertransfer")}</label>
          <input class="form-control" type="file" name="fileToUpload" id="fileToUpload" accept=".lua">
        </div>
        <button type="submit" class="btn btn-primary" name="submit" value="{lang("upload", "charactertransfer")}">{lang("upload", "charactertransfer")}</button>
      </form>
    </div>
  </div>
</div>