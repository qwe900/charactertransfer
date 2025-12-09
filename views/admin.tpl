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
    <h1>Character Status</h1>
    {if !empty($transferdata)}
      <form method="post" action="">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>AccountID</th>
              <th>Character</th>
              <th>Race</th>
              <th>Gender</th>
              <th>Class</th>
              <th>Server</th>
              <th>Status</th>
              <th>Options</th>
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
  <a href="admin/view/{$row.id}" target="_blank">
    <button type="button">Review</button>
  </a>
  <button type="submit" name="option" value="deny_{$row.id}">Deny</button>
  <button type="submit" name="option" value="approve_{$row.id}">Approve</button>
  <button type="submit" name="option" value="delete_{$row.id}">Delete</button>
</td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      </form>
    {else}
      <p>No CharacterDump has been uploaded yet. No Transfer data available.</p>
    {/if}
  </div>
</body>
</html>
