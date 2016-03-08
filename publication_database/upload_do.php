<?php
require_once("includes/header.php");
?>

    <title>Upload Files</title>
    <div class="container">

<?php
/*
 * This page receives the files and information from upload.php, and displays the relevant information to the user to ensure
 * proper uploading of all files.
 */

/* This section pulls all the posted values from the upload.php page, sets them as variables, and echos them to the HTML page for error checking */ 
$organismid = $_POST['organism'];
$substanceid = $_POST['substance'];
$expected_blank = $_POST['expected_blank'];
$notes = $_POST['notes'];

/* As the previous lists of organisms and substances were based on ID rather than name, these two queries
   use the ID to select the actual name from the list.  */

$organism = getOrganism($organismid,$con);
echo $organism . "<br />";
$substance = getSubstance($substanceid,$con);
echo $substance . "<br />";

/* These commands concatenate the retrieved organism and substance names to generate the table name within the database. */

$tablearr = tablenameGen($organism,$substance);
$tableplates = $tablearr[0];
$tablesamples = $tablearr[1];
$tablecontrols = $tablearr[2];

if($organism=="null" || $substance == "null" || $presence == "null")
{
	echo "Error, please select organism, substance, and presence.";
}
else
{
/* Creates three tables: 
 * $tableplates stores all relevant information about the entire plate (plate #, blank/pos/neg average values for both + and - plates, and notes);
 * $tablesamples stores specific reading data about both + and - plates;
 * $tablecontrols stores all control values from plates for ease of correction, normalization, and calculation
 */

$query = "CREATE TABLE IF NOT EXISTS `$tableplates` ( pid BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, plate BIGINT UNSIGNED, p_blank DECIMAL(12,3), n_blank DECIMAL(12,3), p_pos DECIMAL(12,3), n_pos DECIMAL(12,3), p_neg DECIMAL(12,3), n_neg DECIMAL(12,3), p_added TINYINT(1), n_added TINYINT(1), p_notes TEXT, n_notes TEXT);";
$stmt = $con->prepare($query);
$stmt->execute();
$stmt->close();

/*
 * pid - Primary Key
 * plate
 * p_blank - Average blank value on positive plate
 * n_blank - Average blank value on negative plate
 * p_pos - Average positive value on positive plate
 * n_pos - Average positive value on negative plate
 * p_neg - Average negative value on positive plate
 * n_neg - Average negative value on negative plate
 * p_added - Boolean tracking if positive plates have been added
 * n_added - Boolean tracking if negative plates have been added
 * p_notes - Comments submitted by user for each positive plate or set of positive plates
 * n_notes - Comments submitted by user for each negative plate or set of negative plates
 */

$query = "CREATE TABLE IF NOT EXISTS `$tablesamples` ( sid BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, compound_id BIGINT UNSIGNED, plate BIGINT UNSIGNED, coordinates VARCHAR(5), p_reading DECIMAL(12,3), n_reading DECIMAL(12,3), p_corrandnorm DECIMAL(12,3), n_corrandnorm DECIMAL(12,3));";
$stmt = $con->prepare($query);
$stmt->execute();
$stmt->close();

/*
 * sid - Primary Key
 * compound_id - Compound ID retrieved from master list
 * plate
 * coordinates
 * p_reading - Positive plate raw reading
 * n_reading - Negative plate raw reading
 * p_corrandnorm - Positive plate corrected and normalized value
 * n_corrandnorm - Negative plate corrected and normalized value
 */

$query = "CREATE TABLE IF NOT EXISTS `$tablecontrols` ( cid BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, plate BIGINT UNSIGNED, substance VARCHAR(3), type VARCHAR(10), value DECIMAL(12,3));";
$stmt = $con->prepare($query);
$stmt->execute();
$stmt->close();

/*
 * cid - Primary Key
 * plate
 * sub - Whether or not substance is present in the given plate
 * type - Type of control (Blank, Positive, Negative, or Excluded Value)
 * value - Raw read value entered
 */

/*
 * The following two foreach statements pull in the plates entered in both
 * the positive and negative substance selections, one at a time.
 * Selects variables from the plate table to determine if both pos and neg
 * plates have been added; existence of variable will be checked later on.
 *
 * Each foreach uses the includes/add_files.php page to process the text files
 * properly.
 */
 
foreach($_FILES['pos_file']['tmp_name'] as $key => $tmp_name)
{
	$presence = "yes";
	$file_name = $_FILES['pos_file']['name'][$key];
	$file_size = $_FILES['pos_file']['size'][$key];
	$file_tmp = $_FILES['pos_file']['tmp_name'][$key];
	$file_type = $_FILES['pos_file']['type'][$key];  
	$file_error = $_FILES['pos_file']['error'][$key];

	$allowedExts = array("txt", "csv");
	$temp = explode(".", $file_name);
	$extension = end($temp);
	$plate = intval($file_name);
	$query = "SELECT p_added, n_added FROM $tableplates WHERE plate=?;";
	$stmt = $con->prepare($query);
	$stmt -> bind_param("s",$plate);
	$stmt -> execute();
	$stmt -> bind_result($p_added, $n_added);
	$stmt -> fetch();
	$stmt -> close();
	
	if ($file_tmp)
	{
		if ((($file_type == "text/plain")
		|| ($file_type == "application/vnd.ms-excel"))
		&& ($file_size < 20000)
		&& in_array($extension, $allowedExts)) 
		{
			if ($file_error > 0)
			{
				echo "Error: " . $file_error . "<br>";
			}
			elseif ($p_added && $n_added)
			{
				echo "Error: Plate #" . $plate . " already has both positive and negative plates in " . $tableplates . " .<br />";
			}
			elseif ($p_added && $presence == "yes")
			{
				echo "Error: Plate #" . $plate . " already has positive plate in table.<br />";
			}
			elseif ($n_added && $presence == "no")
			{
				echo "Error: Plate #" . $plate . " already has negative plate in table.<br />";
			}
			else 
			{
				echo "Upload: " . $file_name . "<br>";
				echo "Type: " . $file_type . "<br>";
				echo "Size: " . ($file_size / 1024) . " kB<br><br>";
				$var = fopen($file_tmp,"r"); 
				require ("includes/add_files.php");
			}
		}
		else 
		{
			echo "Invalid file: " . $file_name . "<br />";
		}
	}
}
foreach($_FILES['neg_file']['tmp_name'] as $key => $tmp_name)
{
	$presence = "no";
	$file_name = $_FILES['neg_file']['name'][$key];
	$file_size = $_FILES['neg_file']['size'][$key];
	$file_tmp = $_FILES['neg_file']['tmp_name'][$key];
	$file_type = $_FILES['neg_file']['type'][$key];  
	$file_error = $_FILES['neg_file']['error'][$key];

	$allowedExts = array("txt", "csv");
	$temp = explode(".", $file_name);
	$extension = end($temp);
	$plate = intval($file_name);

	$query = "SELECT p_added, n_added FROM $tableplates WHERE plate=?;";
	$stmt = $con->prepare($query);
	$stmt -> bind_param("s",$plate);
	$stmt -> execute();
	$stmt -> bind_result($p_added, $n_added);
	$stmt -> fetch();
	$stmt -> close();
	
	if ($file_tmp)
	{
		if ((($file_type == "text/plain")
		|| ($file_type == "application/vnd.ms-excel"))
		&& ($file_size < 20000)
		&& in_array($extension, $allowedExts)) 
		{
			if ($file_error > 0)
			{
				echo "Error: " . $file_error . "<br>";
			}
			elseif ($p_added && $n_added)
			{
				echo "Error: Plate #" . $plate . " already has both positive and negative plates in " . $tableplates . " .<br />";
			}
			elseif ($p_added && $presence == "yes")
			{
				echo "Error: Plate #" . $plate . " already has positive plate in table.<br />";
			}
			elseif ($n_added && $presence == "no")
			{
				echo "Error: Plate #" . $plate . " already has negative plate in table.<br />";
			}
			else 
			{
				echo "Upload: " . $file_name . "<br>";
				echo "Type: " . $file_type . "<br>";
				echo "Size: " . ($file_size / 1024) . " kB<br><br>";
				$var = fopen($file_tmp,"r"); 
				require ("includes/add_files.php");
			}
		}
		else 
		{
			echo "Invalid file: " . $file_name . "<br />";
		}
	}
}
$con -> close();
}
require_once("includes/footer.php");
?>