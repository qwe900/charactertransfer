<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>FusionGen CMS WoW Addon Template</title>
  <style>
    /* Add your custom CSS styles here */
    body {
      font-family: Arial, sans-serif;
    }
    
    .container {
      width: 100%;
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
    }
    
    th, td {
      padding: 10px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    
    .upload-button {
      position: absolute;
      top: 10px;
      right: 10px;
    }
    
    .option-buttons {
      white-space: nowrap;
    }
    
    .option-buttons button {
      margin-right: 5px;
      padding: 5px 10px;
      font-size: 12px;
    }
    
    @media (max-width: 600px) {
      table {
        font-size: 14px;
      }
      
      .upload-button {
        position: static;
        margin-top: 10px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>{$lang.character_status}</h1>

    {if isset($message) && $message}
      <div class="alert alert-info">{$message}</div>
    {/if}

    {if !empty($transferdata)}
      <form method="post" action="{$url}charactertransfer/admin">
        <input type="hidden" name="{$csrf_name}" value="{$csrf_hash}" />
        <table>
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
              <th>{$lang.options}</th>
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
                <td class="option-buttons">
                  <a href="{$url}charactertransfer/admin/view/{$row.id}" target="_blank">
                    <button type="button">{$lang.review}</button>
                  </a>
                  <button type="submit" name="option" value="deny_{$row.id}">{$lang.deny}</button>
                  <button type="submit" name="option" value="approve_{$row.id}">{$lang.approve}</button>
                  <button type="submit" name="option" value="delete_{$row.id}">{$lang.delete}</button>
                </td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      </form>
    {else}
      <p>{$lang.no_chardump_admin}</p>
    {/if}
  </div>
</body>
</html>
