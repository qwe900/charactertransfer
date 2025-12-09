<div class="container">

  <h1>{lang("installing_chardump_addon", "charactertransfer")}</h1>
  </br>
  </br>
  <ol>
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

  <h1>{lang("character_status", "charactertransfer")}</h1>
  </br>

  {if !empty($transferdata)}

  <table class="table table-dark table-striped">
    <thead class="table-dark ">
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
  {else}
  <p>{lang("no_chardump_uploaded", "charactertransfer")}</p>
  {/if}
  <br>
  <form action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="{$csrf_name}" value="{$csrf_hash}" />

  <table class="table">
    <thead>
      <tr>
        <th>{lang("select_file_upload", "charactertransfer")}:</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><input type="file" name="fileToUpload" id="fileToUpload" accept=".lua"></td>
        <td><input type="submit" value="{lang("upload", "charactertransfer")}" name="submit"></td>
      </tr>
    </tbody>
  </table>
  </form>
</div>