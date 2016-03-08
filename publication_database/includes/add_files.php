<?php 
$blank = 0;
$blankcounter = 0;
$positive = 0;
$positivecounter = 0;
$negative = 0;
$negativecounter = 0;

/* 
 * $var is an individual text file, uploaded from the upload.php page. Its layout must be as 
 * a comma separated value (CSV) file, with the plate coordinates as the first value of
 * each line, and the raw read data as the second value.
 *
 * The code checks each line in the file to determine type by comparing coordinates to the information entered
 * in the upload matrix, and acts accordingly. To determine average values, the raw reads are cumulatively added, then
 * divided by a counter that is increased upon every detected value of that type.
 * Blank: Checks if the reading is less than the expected blank value that was entered, to automatically exclude contaminated wells.
 * Positive: Establishes a cumulative value to determine average, and enters all values into an array.
 * Negative: Establishes a cumulative value to determine average, and enters all values into an array. Note that it has extended
 * functionality built in to exclude negatives if they are above a given value, but it is currently commented out and not implemented.
 */

while ($array = fgetcsv($var))
{
	$coord = $array[0];
	$reading = $array[1];
	$type = $_POST[$coord];
	if($type == 'blank')
	{
		if ($reading < $expected_blank)
		{
			$blank += $reading;
			++$blankcounter;
			$blank_arr[$coord] = $reading;
		}
		else
		{
			echo "Well " . $coord . " out of range; " . $reading . " above expected blank of " . $expected_blank . " .<br />";
		}
	}
	elseif ($type == 'pos')
	{
		$positive += $reading;
		++$positivecounter;
		$pos_arr[$coord] = $reading;
	}
	elseif ($type == 'neg')
	{
		$negative += $reading;
		++$negativecounter;
		$neg_arr[$coord] = $reading;
		/*if ($reading < $expected_neg)
		{
			$negative += $reading;
			++$negativecounter;
		}
		else
		{
			echo "Well " . $coord . " out of range; " . $reading . " above expected negative of " . $expected_neg . " .<br />";
		}*/

	}
	elseif ($type == 'exclude')
	{
		echo "Well " . $coord . " excluded from database. <br />";
	}
}

$blankavg = ($blank / $blankcounter);
$positiveavg = ($positive / $positivecounter); // Establishing averages of above values.
$negativeavg = ($negative / $negativecounter);
$added=1;

/*
 * Based upon the individual scenario, these if statements determine whether to insert a new
 * line into the given tables, or update already present lines.
 */

if (!$p_added && !$n_added && $presence == "yes")
{
	$query = "INSERT INTO `$tableplates` VALUES('',?,?,'',?,'',?,'',?,'',?,'');";
	$stmt = $con->prepare($query);
	$stmt -> bind_param("ssssss",$plate,$blankavg,$positiveavg,$negativeavg,$added,$notes);
	$stmt -> execute();
	$stmt -> close();
}
elseif (!$p_added && !$n_added && $presence == "no")
{
	$query = "INSERT INTO `$tableplates` VALUES('',?,'',?,'',?,'',?,'',?,'',?);";
	$stmt = $con->prepare($query);
	$stmt -> bind_param("ssssss",$plate,$blankavg,$positiveavg,$negativeavg,$added,$notes);
	$stmt -> execute();
	$stmt -> close();
}
elseif ($presence == "yes")
{
	$query = "UPDATE `$tableplates` SET p_blank=?, p_pos=?, p_neg=?, p_added=?, p_notes=? WHERE plate=?;";
	$stmt = $con->prepare($query);
	$stmt->bind_param("ssssss", $blankavg, $positiveavg, $negativeavg, $added, $notes, $plate);
	$stmt->execute();
	$stmt->close();
}
elseif ($presence == "no")
{
	$query = "UPDATE `$tableplates` SET n_blank=?, n_pos=?, n_neg=?, n_added=?, n_notes=? WHERE plate=?;";
	$stmt = $con->prepare($query);
	$stmt->bind_param("ssssss", $blankavg, $positiveavg, $negativeavg, $added, $notes, $plate);
	$stmt->execute();
	$stmt->close();
}

/*
 * This conducts a second loop through the file; the first calculated and inserted average values, while this loop
 * specifically inserts the individual sample values. 
 *
 * Initially, if the coordinate type is determined as "sample," a query is conducted to obtain the specific compound
 * ID as defined by the '$master_compound_table' table, defined in includes/db.php, using coordinates and plate number as identifiers. Then, presence
 * is determined using the 'p_added' and 'n_added' columns to select the proper places to insert the new values
 * into the $tablesamples table (as dynamically defined in the upload_do.php page). Finally, all non-sample values
 * are inserted into the $tablecontrols table.
 */

rewind($var);
while ($array = fgetcsv($var))
{
	$coord = $array[0];
	$reading = $array[1];
	$type = $_POST[$coord];
	$corrandnorm = (($reading - $blankavg)/($positiveavg - $blankavg) * 100);
	if  ($type == 'sample')
	{
		$query = "SELECT compoundid FROM $master_compound_table WHERE coordinates=? AND plate=?;"; 
		$stmt = $con -> prepare($query);
		$stmt -> bind_param("ss",$coord,$plate);
		$stmt -> execute();
		$stmt -> bind_result($compound_id);
		$stmt -> fetch();
		$stmt -> close();
		
		if (!$p_added && !$n_added && $presence == "yes")
		{
			$query = "INSERT INTO `$tablesamples` VALUES('',?,?,?,?,'',?,'');";
			$stmt = $con->prepare($query);
			$stmt -> bind_param("sssss",$compound_id,$plate,$coord,$reading,$corrandnorm);
			$stmt -> execute();
			$stmt -> close();
		}
		elseif (!$p_added && !$n_added && $presence == "no")
		{
			$query = "INSERT INTO `$tablesamples` VALUES('',?,?,?,'',?,'',?);";
			$stmt = $con->prepare($query);
			$stmt -> bind_param("sssss",$compound_id,$plate,$coord,$reading,$corrandnorm);
			$stmt -> execute();
			$stmt -> close();
		}
		elseif ($presence == "yes")
		{
			$query = "UPDATE `$tablesamples` SET p_reading=?, p_corrandnorm=? WHERE compound_id=?;";
			$stmt = $con->prepare($query);
			$stmt->bind_param("sss", $reading, $corrandnorm, $compound_id);
			$stmt->execute();
			$stmt->close();
		}
		elseif ($presence == "no")
		{
			$query = "UPDATE `$tablesamples` SET n_reading=?, n_corrandnorm=? WHERE compound_id=?;";
			$stmt = $con->prepare($query);
			$stmt->bind_param("sss", $reading, $corrandnorm, $compound_id);
			$stmt->execute();
			$stmt->close();
		}
	}
	elseif (isset($type))
	{
		$query = "INSERT INTO `$tablecontrols` VALUES('',?,?,?,?);";
		$stmt = $con->prepare($query);
		$stmt -> bind_param("ssss",$plate,$presence,$type,$reading);
		$stmt -> execute();
		$stmt -> close();
	}
}
fclose($var);
?>