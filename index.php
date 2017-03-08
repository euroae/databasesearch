<?php

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
  
  function BuildResultsTable($data)
  {
    $output .= "<br><table style='border-collapse: collapse; width: 300px; margin-left: auto; margin-right: auto; border-top-right-radius: 3em;'><tr class='RoundedTop RedRow' ><td>First Name</td><td>Last Name</td><td>Institution</td><td>Department</td><td>Phone</td><td>E-Mail</td><td>Webpage</td></tr>";
    foreach($data as $row) {
      $output .= "<tr><td>" . $row["firstName"]. "</td>";
      $output .= "<td>" . $row["lastName"]. "</td>";
      $output .= "<td>" . $row["institution"]. "</td>";
      $output .= "<td>" . $row["department"]. "</td>";
      $output .= "<td>" . $row["phone"]. "</td>";
      $output .= "<td>" . $row["email"]. "</td>";
      $output .= "<td>" . $row["webpage"]. "</td></tr>";
    }
    
    $output .= "</table>";
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
		<link rel="stylesheet" href="styles/css/style.css">
	</head>
	<body>
		<form method="post" name="search">
		<table style="border-collapse: collapse; width: 300px; margin-left: auto; margin-right: auto; border-top-right-radius: 3em;">
      <tr >
        <td class="RoundedTop RedRow" style=""  colspan="2">Sample</td>
      </tr> 
      <tr class="WhiteRow" style='border-left: #000 thin solid; border-right: #000 thin solid;'>
        <td class="Centered">Search:</td>
				<td><input type="text" name="searchTerm"></td>
      </tr>
      <tr class="WhiteRow" style='border-left: #000 thin solid; border-right: #000 thin solid;'>
        <td class="Centered" colspan="2"><input type="submit" name="submit" value="Search"></td>
      </tr>
      
      </tr>
      <tr><td class="Bottom RedRow" colspan="2"></td></tr>
    </table>
		</form>

		<?php
		$searchTerms = "";
		if ($_POST)
		{
                  $searchTerms = $_POST["searchTerm"];
		}
		echo SearchFor($searchTerms);
		?>
	</body>
</html>
