<?php

  /**
   * opens connection to database
   * 
   * @return \PDO
   */
  function OpenConnection() 
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
   * A brute force search method. Takes the search terms as input, splits them into each individual word
   * and then compares every word with every column in the prof table and returns
   * any rows that match in part. 
   * 
   * @param string $searchTerms   the word/phrase to search the table
   * @return string               html table with the results of the sql query
   */
  function SearchFor($searchTerms)
  {
    try {
      // open sql connection
      $pdo = OpenConnection();
      
      // Split the input into it's seperate terms
      $split = explode(" ", $searchTerms);

      // Create an array with bindings for each values and search field
      foreach($split as $addBind) {
        $pieces[] = '(`firstName` LIKE concat(\'%\', ?, \'%\') OR `lastName` LIKE concat(\'%\', ?, \'%\') OR `institution` LIKE concat(\'%\', ?, \'%\') OR`department` LIKE concat(\'%\', ?, \'%\') OR `phone` LIKE concat(\'%\', ?, \'%\') OR `email` LIKE concat(\'%\', ?, \'%\') OR `webpage` LIKE concat(\'%\', ?, \'%\'))';
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
      return (count($results) > 0) ? BuildResultsTable($results) : "0 Results";
    } catch(PDOException $e) {
      echo "Error: " . $e->getMessage(); 
    }
  }
  
  /**
   * 
   * @param array $data   the raw results from the database
   * @return string       the table with results formated in it
   */
  function BuildResultsTable($data)
  {
    $output .= "<table>"
            . "<thead><tr class='RedRow'><th>First Name</th><th>Last Name</th><th>Institution</th><th>Department</th><th>Phone</th><th>E-Mail</th><th>Webpage</th></tr></thead><tbody>";
    foreach($data as $row) {
      $output .= "<tr><td>" . $row["firstName"]. "</td>";
      $output .= "<td>" . $row["lastName"]. "</td>";
      $output .= "<td>" . $row["institution"]. "</td>";
      $output .= "<td>" . $row["department"]. "</td>";
      $output .= "<td>" . $row["phone"]. "</td>";
      $output .= "<td>" . $row["email"]. "</td>";
      $output .= "<td>" . $row["webpage"]. "</td></tr>";
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
        echo SearchFor($searchTerms);
      ?>
    </div>
  </body>
</html>
