<?php
require_once("includes/header.php");
?>

    <title>Generated Report</title>
    <div class="container.fluid">
<?php
// Initializes all counting variables for the five different hit types
$primaryhit = 0;
$secondaryhit = 0;
$tertiaryhit = 0;
$independenthit = 0;
$inversehit = 0;

//$csvfile is initialized as an array to pull in all lines of the report for exporting to a CSV file.
$csvfile[0] = array("Compound ID","SMILES","Plate","Coordinates","Hit Type","Pos Ctrl (+)","Pos Ctrl (-)","Neg Ctrl (+)","Neg Ctrl (-)","Normalized (+)","Normalized (-)","Notes (+)","Notes (-)");

$organismid = $_POST['organism'];
$substanceid = $_POST['substance'];
$sort = $_POST['sort'];
$csvbool = $_POST['csv'];
$structurebool = $_POST['structure'];

// Receives POST data from checkboxes detailing which hit types user wants displayed in report
$hitarray=array();
$selectedhits=array("primary","secondary","tertiary","indy","inverse");
foreach ($selectedhits as $var)
{
	if(isset($_POST[$var]))
	{
		$var = $_POST[$var];
		array_push($hitarray,$var);
	}
}

/* 
 * Using the numeric identifiers from above, program selects the correct organism
 * and substance names from the tables, and dynamically generates the proper
 * table name. Functions are in includes/functions.php and conduct MySQL queries.
 *
 * Function tablenameGen then generates dynamic table names based upon provided
 * organism and substance data.
 */
 
$organism = getOrganism($organismid,$con);
echo $organism . "<br />";

$substance = getSubstance($substanceid,$con);
echo $substance . "<br /><br />";

$tablearr = tablenameGen($organism,$substance);

$tableplates = $tablearr[0];
$tablesamples = $tablearr[1];
$tablecontrols = $tablearr[2];

/* 
 * This section checks the total number of plates within the queried table. 
 * Each plate pair has internal counters in the database, indicating if both
 * the positive and negative plates have been added. The while/if loop checks
 * to see if both values are equal to 1, indicating both plates have been added.
 * Depending on the result, plates numbers are shunted into different arrays
 * for final reporting on what plates were included in the output.
 */
 
$both_plates=array();
$no_pos_plate=array();
$no_neg_plate=array();
$no_plates=array();
$query = "SELECT plate, p_added, n_added FROM $tableplates;";
$stmt = $con -> prepare($query);
$stmt -> execute();
$stmt -> bind_result($plateid,$p_added,$n_added);
while ($stmt -> fetch())
{
	if ($p_added == 1 && $n_added == 1)
	{
		array_push($both_plates,$plateid);
	}
	elseif ($p_added == 1 && $n_added == 0)
	{
		array_push($no_neg_plate,$plateid);
	}
	elseif ($p_added == 0 && $n_added == 1)
	{
		array_push($no_pos_plate,$plateid);
	}
	else
	{
		array_push($no_plates,$plateid);
	}
}
sort($both_plates);
sort($no_pos_plate);
sort($no_neg_plate);

$stmt -> close();

/* 
 * This section then reports all plates listed as complete pairs from the above test.
 * If they are, it adds it to the total plate analyzed section; if not, it shunts it to its
 * appropriate array to count.
 */

echo "<div class=\"well\">Total compounds analysed: " . (80*count($both_plates)) . "<br />";
foreach ($both_plates as $v)
{
	if ($v == $both_plates[0])
	{
		echo $v;
	}
	else
	{
		echo ", " . $v;
	}
}
echo "<br /><br />";
echo "Entries without substance-positive plate: " . count($no_pos_plate) . "<br />";
foreach ($no_pos_plate as $v)
{
	if ($v == $no_pos_plate[0])
	{
		echo $v;
	}
	else
	{
		echo ", " . $v;
	}
}
echo "Entries without substance-negative plate: " . count($no_neg_plate) . "<br />";
foreach ($no_neg_plate as $v)
{
	if ($v == $no_neg_plate[0])
	{
		echo $v;
	}
	else
	{
		echo ", " . $v;
	}
}
echo "
    </div>";

$query = "CREATE TEMPORARY TABLE IF NOT EXISTS `report_do` (rid BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, compound_id BIGINT, smiles TEXT, plate_id BIGINT, coordinates VARCHAR(255), hit_type INT, p_pos DECIMAL(12,3), n_pos DECIMAL(12,3), p_neg DECIMAL(12,3), n_neg DECIMAL(12,3), p_normd DECIMAL(12,3), n_normd DECIMAL(12,3), p_notes TEXT, n_notes TEXT);";
$stmt = $con->prepare($query);
$stmt->execute();
$stmt->store_result();
$stmt->close();

/* Columns in `report_do` :
 *
 * rid - Primary key.
 * compound_id - Unique compound identifier.
 * smiles - SMILES code of compound for chemoinformatic analysis.
 * plate_id - Plate ID the compound is located on.
 * coordinates - Specific well coordinates of the compound.
 * hit_type - The calculated hit type.
 * p_pos - Positive control for the substance+ plate the compound is on.
 * n_pos - Negative control for the substance+ plate the compound is on.
 * p_neg - Positive control for the substance- plate the compound is on.
 * n_neg - Negative control for the substance- plate the compound is on.
 * p_normd - Normalized growth value of the compound on the substance+ plate.
 * n_normd - Normalized growth value of the compound on the substance- plate.
 * p_notes - Any notes input upon data entry regarding the substance+ plate.
 * n_notes - Any notes input upon data entry regarding the substance- plate.
 * 
 * The below SELECT statement pulls all sample-specific data from the sample table.
 */

$query = "SELECT compound_id, plate, coordinates, p_reading, n_reading, p_corrandnorm, n_corrandnorm FROM $tablesamples;";
$stmt = $con->prepare($query);
$stmt->bind_result($compound_id, $plate, $coord, $p_reading, $n_reading, $p_normd, $n_normd);
$stmt->execute();
$stmt->store_result();

/*
 * This while loop puts the retrieved normalized values through an algorithm to determine hit type, which is
 * then checked against the array given by the user on the input page. The if statement immediately after the 
 * while loop determines if both plates are present in the array developed above, and only analyses the data
 * if so.
 *
 * As is, the algorithm (the function hitType() ) checks for 10 percentile cutoffs (primary hits below 10%, secondary below 20%, tertiary
 * below 30%, with all substance- values at least 30 percentage points above their substance+ counterparts),
 * though this can be readily changed depending on the specific assay in question.
 */
 
while ($stmt->fetch())
{
	if (in_array($plate,$both_plates))
	{	
		$hit_type=hitType($p_normd,$n_normd);

		/*
		* AT THIS POINT:
		* --
		* $compound_id -> Compound ID
		* $plate -> Plate ID
		* $coord -> Coordinates
		* $hit_type -> 1 if primary, 2 if secondary, 3 if tertiary, 4 if independent, 5 if inverse, 0 if no hit
		* $p_pos -> Pos Ctrl(+)
		* $n_pos -> Pos Ctrl(-)
		* $p_neg -> Neg Ctrl(+)
		* $n_neg -> Neg Ctrl(-)
		* $p_normd -> Norm'd(+)
		* $n_normd -> Norm'd(-)
		* 
		* This if statement checks if the hit type was specified by the user to be displayed
		* and, if so, receives its plate-wide values from the plate table.
		* 
		*/
		if(in_array($hit_type,$hitarray))
		{
			$temp_query = "SELECT p_pos, n_pos, p_neg, n_neg, p_notes, n_notes FROM $tableplates WHERE plate=?;";
			$temp_stmt = $con->prepare($temp_query);
			$temp_stmt->bind_param("s",$plate);
			$temp_stmt->execute();
			$temp_stmt->bind_result($p_pos, $n_pos, $p_neg, $n_neg, $p_notes, $n_notes);
			$temp_stmt->fetch();
			$temp_stmt->close();
			
			$temp_query = "SELECT smiles FROM $master_compound_table WHERE compoundid=?;";
			$temp_stmt = $con->prepare($temp_query);
			$temp_stmt->bind_param("s",$compound_id);
			$temp_stmt->execute();
			$temp_stmt->bind_result($smiles);
			$temp_stmt->fetch();
			$temp_stmt->close();
			
			/* 
			 * This section checks if the user asked for images of hit compounds. If yes, then
			 * the SMILES are passed to the OpenBabel program on the server, where a SVG image
			 * is generated and placed in the /database/svgimages/ folder. Later on, if this option
			 * was specified, images are inserted into the final report. The first time a report is
			 * viewed, the process will take longer, as SVGs must be generated for each compound. 
			 * However, the program checks for pre-existing images of compounds; if they exist, the
			 * program does not redraw them, meaning subsequent queries will be faster.
			 */
			
			if($structurebool=='yes')
			{
				if(file_exists($_SERVER["DOCUMENT_ROOT"] . $document_path . '/svgimages/' . $compound_id . '.svg')==FALSE)
				{
					exec('obabel -:"' . $smiles . '" -O ' . $_SERVER["DOCUMENT_ROOT"] . $document_path . '/svgimages/' . $compound_id . '.svg');
				}
			}

		/*
		 * The combined values, from the initial SELECT query before the while loop and the second SELECT query
		 * just above, are then inserted into the temporary table.
		 */
			$temp_query = "INSERT INTO `report_do` VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
			$temp_stmt = $con->prepare($temp_query);
			$temp_stmt->bind_param("sssssssssssss", $compound_id, $smiles, $plate, $coord, $hit_type, $p_pos, $n_pos, $p_neg, $n_neg, $p_normd, $n_normd, $p_notes, $n_notes);
			$temp_stmt->execute();
			$temp_stmt->store_result();
			$temp_stmt->close();
		}
	}
}
$stmt->close();

/*
 * The below SELECT statement pulls all hits from the temporary table in the order specified by the user on the input page.
 */

$query = "SELECT * FROM `report_do` ORDER BY $sort, plate_id, hit_type, coordinates ASC;";
$stmt = $con->prepare($query);
$stmt->bind_result($rid, $compound_id, $smiles, $plate, $coord, $hit_type, $p_pos, $n_pos, $p_neg, $n_neg, $p_normd, $n_normd, $p_notes, $n_notes);
$stmt->execute();
$stmt->store_result();
?>
      
    <table class="table table-striped table-bordered table-hover table-condensed">
      <tr>
        <th>Compound ID</th>
<?php 
if($structurebool=='yes')
{
	echo "        <th>Structure</th>
";
} 
?>
        <th>SMILES</th>
        <th>Plate ID</th>
        <th>Coordinates</th>
        <th>Hit Type</th>
        <th>Pos Ctrl (+)</th>
        <th>Pos Ctrl (-)</th>
        <th>Neg Ctrl (+)</th>
        <th>Neg Ctrl (-)</th>
        <th>Norm'd (+)</th>
        <th>Norm'd (-)</th>
        <th>Notes (+)</th>
        <th>Notes (-)</th>
      </tr>
<?php

while($stmt->fetch())
{
	switch($hit_type)
	{
		case 1:
			$hit_type = "Primary";
			$primaryhit++;
			break;
		case 2:
			$hit_type = "Secondary";
			$secondaryhit++;
			break;
		case 3:
			$hit_type = "Tertiary";
			$tertiaryhit++;
			break;
		case 4:
			$hit_type = "Independent";
			$independenthit++;
			break;
		case 5:
			$hit_type = "Inverse";
			$inversehit++;
			break;
	}

	echo "
      <tr>
        <td>$compound_id</td>
        ";
	if($structurebool=='yes'){echo "<td><img src=\"$document_path/svgimages/$compound_id.svg\" width=\"200\" height=\"150\"/></td>";}
	echo "
        <td>$smiles</td>
		<td>$plate</td>
        <td>$coord</td>
        <td>$hit_type</td>
        <td>$p_pos</td>
        <td>$n_pos</td>
        <td>$p_neg</td>
        <td>$n_neg</td>
        <td><b>$p_normd</b></td>
        <td><b>$n_normd</b></td>
        <td>$p_notes</td>
        <td>$n_notes</td>
      </tr>
";
	/* 
	 * This section forms a temporary array using all the listed values, and then pushes that array
	 * into a multidimensional array ($csvfile). $csvfile will be serialized and urlencoded to pass
	 * it to the CSV creation page, if the user chooses to export a CSV file.
	 */
	$temparr=array();
	array_push($temparr, $compound_id, $smiles, $plate, $coord, $hit_type, $p_pos, $n_pos, $p_neg, $n_neg, $p_normd, $n_normd, $p_notes, $n_notes);
	$csvfile[] = $temparr;

}
echo "    </table><br />\n";
echo "    <br />\nPrimary hits: " . $primaryhit . ", sub+ under 10% and sub- more than 30% above." . "<br />\n";
echo "    Secondary hits: " . $secondaryhit . ", sub+ under 20% and sub- more than 30% above." . "<br />\n";
echo "    Tertiary hits: " . $tertiaryhit . ", sub+ under 30% and sub- more than 30% above." . "<br />\n";
echo "    Independent hits: " . $independenthit . ", both under 30%." . "<br />\n";
echo "    Inverse hits: " . $inversehit . ", sub- under 30% and sub+ more than 30% above." . "<br />\n";

/*
 * These functions take the multidimensional array of $csvfile and pack it up, first as a serialized
 * value, and then as a URL encoded value, in order to then post it to the csv-download.php page.
 *
 * IMPORTANT --- IMPORTANT
 * Right now, ~500 hits returns a URL encoded value of ~194kb. When screens include many more compounds,
 * this value will skyrocket quickly; the program right now can likely only handle a maximum of 5k hits.
 * If we need to export more than 5k hits, the server will need configuration settings changed (as it is
 * likely at a default maximum of 2mb for POST values). Alternatively, a more efficient way of creating
 * a CSV file will have to be found at that point.
 */
 
$csv_ser = serialize($csvfile);
$csv_urlen = urlencode($csv_ser);
?>
    <form action="includes/csv-download.php" method="post">
<?php 
echo "      <input type=\"hidden\" name=\"array\" value=\"$csv_urlen\">
      <input type=\"hidden\" name=\"table\" value=\"$organism_$substance\">"
?>

      <input class="btn btn-default btn-file" type="submit" value="Generate Excel CSV File">
    </form>

<?php
$stmt->close();
$con->close();
require_once("includes/footer.php");?>