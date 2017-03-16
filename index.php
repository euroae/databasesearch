<?php

  /**
   * opens connection to database
   * 
   * @return \PDO
   */
  function openConnection() 
  {
    // db.inc includes the database connection info on a file located in folder not accessible through http
    include '/db.inc';
    $charset = 'utf8';

    $dsn = "mysql:host=$dbhost;dbname=$jdbname;charset=$charset";
    $opt = [
	PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $juser, $jpass, $opt);

    return $pdo;
  }
  
  /**
   * Get the names of all the columns we wish to print onto table.
   * Modify the prepare statement to leave out any columns you don't want printed
   * 
   * @return array    array of column names.
   */
  function getColumnNames() {
    try {
      // open sql connection
      $pdo = openConnection();
      
      //prepare and execute statement
      $stmt = $pdo->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema=? AND table_name=? AND NOT (column_name like "%index%" OR column_name like "%id");');
      $stmt->bindValue(1, "JapaneseData");
      $stmt->bindValue(2, "prof");
      
      //execute and collect output
      $stmt->execute();
      return $stmt->fetchAll();
      
    } catch(PDOException $e) {
      echo "Error: " . $e->getMessage(); 
    }
  }
  
  /**
   * A brute force search method. Takes the search terms as input, splits them into each individual word
   * and then compares every word with every column in the prof table and returns
   * any rows that match in part. 
   * 
   * @param string $searchTerms   the word/phrase to search the table
   * @return string               html table with the results of the sql query
   */
  function searchFor($searchTerms) {
    try {
      // open sql connection
      $pdo = openConnection();
      
      // Split the input into it's seperate terms
      $split = explode(" ", $searchTerms);

      // Create an array with bindings for each values and search field
      foreach($split as $addBind) {
        $pieces[] = '(`first_name` LIKE concat(\'%\', ?, \'%\') OR `last_name` LIKE concat(\'%\', ?, \'%\') OR `institution` LIKE concat(\'%\', ?, \'%\') OR`department` LIKE concat(\'%\', ?, \'%\') OR `phone` LIKE concat(\'%\', ?, \'%\') OR `e-mail` LIKE concat(\'%\', ?, \'%\') OR `webpage` LIKE concat(\'%\', ?, \'%\'))';
      }
      
      //prepare statement
      $stmt = $pdo->prepare('Select * FROM prof WHERE' . implode(' OR ', $pieces));
      
      // bind parameters
      $x = 1;   // independant count to ensure each place holder gets bounded to a value in order without needing math
      for ($z = 0; $z < count($split); $z++) {
        // repeat bind once per each 7 search fields and increment x each time
        for ($y = 0; $y < 7; $y++) {
          $stmt->bindParam($x, $split[$z], PDO::PARAM_STR);
          $x++;
        }
      }
      
      //execute and collect output
      $stmt->execute();
      $results = $stmt->fetchAll();
    
      //return table view if there are results, else just return no results message
      return (count($results) > 0) ? buildResultsTable($results) : "0 Results";
    } catch(PDOException $e) {
      echo "Error: " . $e->getMessage(); 
    }
  }
  
  /**
   * 
   * @param array $data   the raw results from the database
   * @return string       the table with results formated in it
   */
  function buildResultsTable($data)
  {
    $columns = getColumnNames();
    $columnCount = count($columns);
    
    // create header rows
    $output .= "<table><thead><tr class='RedRow'>";
    
    // dynamically create header based on table
    for($x =0; $x < $columnCount; $x++) {
      $text = ucwords(str_replace("_", " ", $columns[$x]["column_name"]));
      $output .= "<th>$text</th>";
    }
    $output .= "</tr></thead><tbody>";
    
    // dynamically print based on table
    foreach($data as $row) {
      $output .= "<tr>";
      for($x =0; $x < $columnCount; $x++) {
	$text = $row[$columns[$x]["column_name"]];
	$output .= "<td>$text</td>";
      }
      $output .= "</tr>";
    }
    
    $output .= "</tbody></table>";
    
    return $output;
  }
?>
<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
  <head>
    <meta charset="UTF-8">
    <title></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
    <link rel="stylesheet" href="styles/styles.css">
    <link rel='stylesheet' href='/common/css/bootstrap/bootstrap.css'></link>
    <link rel='stylesheet' href='/common/css/jquery/jquery-ui.css'></link>
    
    <script src='/common/js/jquery/jquery-3.1.1.js'></script>
    <script src="/common/js/bootstrap/bootstrap.js"></script>
  </head>
  <body>
    <span class="text-center">
      <form class="form-inline" role="search" method="post" name="search">
        <div class="form-group">
          <label for="searchTerm">Search For:</label>
          <input type="text" class="form-control" placeholder="Search" name="searchTerm">
          <button type="submit" class="btn btn-default">Submit</button>
        </div>
      </form>
    </span>
    <div style="white-space: nowrap; overflow-x: auto;">
      <?php
        $searchTerms = "";
        if ($_POST)
        {
          $searchTerms = $_POST["searchTerm"];
        }
        echo searchFor($searchTerms);
      ?>
    </div>
  </body>
</html>
