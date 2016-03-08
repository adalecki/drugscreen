<?php
require_once("includes/header.php");
?>
    <title>Z Factor Calculation</title>
  </head>
  <body>

<?php
$organismid = $_POST['organism'];
$substanceid = $_POST['substance'];

/*
 * Using the numeric identifiers from above, program selects the proper organism
 * and substance names from the tables, and dynamically generates the proper
 * table name. 
 */

$organism = getOrganism($organismid,$con);
echo $organism . "<br />";
$substance = getSubstance($substanceid,$con);
echo $substance . "<br /><br />";
$tablearr = tablenameGen($organism,$substance);

/*
 * In $tablearr, [0] is the plates table, [1] is the samples table, [2] is the controls table.
 *
 * All plates in the specified table are queried for presence of both the positive and negative plates.
 * Depending on each plate number's status, it is shunted into the appropriate array for later.
 */
 
$both_plates=array();
$no_pos_plate=array();
$no_neg_plate=array();
$no_plates=array();
$query = "SELECT plate, p_added, n_added FROM $tablearr[0];";
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
$stmt -> close();
sort($both_plates);
sort($no_pos_plate);
sort($no_neg_plate);

/*
 * Z Factors are a measure of statistical effect size, and used to determine how reliably
 * a screen can correctly distinguish a "hit" from the assay background. Z Factors are 
 * calculated from all sample values, while Z' Factors are instead only calculated from 
 * internal controls on each plate. Thus, Z' Factors will always be higher than Z Factors,
 * especially if a given screen has a high number of hits on each plate.
 *
 * Here, the code determines if the user selected to calculate either a Z Factor, or a Z' Factor
 * on the Report page. Depending on the selection, one of two algorithms is implemented. Only 
 * values from the substance positive (p_reading) plates are used in the calculation; if desired,
 * it is simple enough to calculate individual Z Factors for both substance positive and 
 * substance negative plates.
 */
 
if (isset($_POST['z_factor']))
{
	echo "    <h1>Z Factors</h1>
		<table class=\"table table-striped table-bordered table-hover table-condensed\">
		<tr>
			<th>Plate ID</th>
			<th>Z Factor</th>
			<th>Sample Ave</th>
			<th>Sample STDEV</th>
			<th>Control Ave</th>
			<th>Control STDEV</th>
		</tr>";

/*
 * Pulls the raw reads from samples in the table, then stores means and stdevs in the overall array.
 */
 
	foreach ($both_plates as $val)
	{
		$samplevals=array();
		$query = "SELECT p_reading, p_corrandnorm, n_corrandnorm FROM $tablearr[1] WHERE plate = $val;";
		$stmt = $con->prepare($query);
		$stmt->bind_result($value,$p_normd,$n_normd);
		$stmt->execute();
		$stmt->store_result();
		while($stmt->fetch())
		{
			$hit_type = hitType($p_normd,$n_normd);
			array_push($samplevals, $value);
		}
		$samplemean = (array_sum($samplevals)/count($samplevals));
		$samplestdev = stDev($samplevals);
		
		//Pulls the raw reads from negative controls in the table, then stores means and stdevs in the overall array
		$controlvals=array();
		$query = "SELECT value FROM $tablearr[2] WHERE plate = ? AND type = 'neg' AND substance = 'yes';";
		$stmt = $con->prepare($query);
		$stmt->bind_param("s",$val);
		$stmt->execute();
		$stmt->bind_result($value);
		$stmt->execute();
		$stmt->store_result();
		while($stmt->fetch())
		{
			array_push($controlvals, $value);
		}
		$controlmean = (array_sum($controlvals)/count($controlvals));
		$controlstdev = stdev($controlvals);
		
		//Calculates individual Z factors for each plate based upon values calculated above
		$zfactor = (1 - ((3*$samplestdev + 3*$controlstdev)/abs($samplemean-$controlmean)));
		
		echo "
			<tr>
				<td>$val</td>
				<td>" . round($zfactor,4) . "</td>
				<td>" . round($samplemean,4) . "</td>
				<td>" . round($samplestdev,4) . "</td>
				<td>" . round($controlmean,4) . "</td>
				<td>" . round($controlstdev,4) . "</td>
			</tr>";

/*
 * $allsampstdev - Array of all sample stdevs, keyed by plate number
 * $allsampmean - Array of all sample means, keyed by plate number
 * $allctrlstdev - Array of all negative control stdevs, keyed by plate number
 * $allctrlmean - Array of all negative control means, keyed by plate number
 * $platezarr - Array of all individual Z factors, keyed by plate number
 */

		$allctrlmean[$val] = $controlmean;
		$allctrlstdev[$val] = $controlstdev;
		$allsampmean[$val] = $samplemean;
		$allsampstdev[$val] = $samplestdev;
		$platezarr[$val] = $zfactor;
		foreach ($allctrlmean as $key => $item)
		{
			if ($item > (array_sum($allctrlmean)/count($allctrlmean))*2)
			{
				unset($platezarr[$key]);
			}
		}
	}
}

elseif (isset($_POST['z_prime']))
{
    echo "<h1>Z' Factors</h1>
    <table class=\"table table-striped table-bordered table-hover table-condensed\">
      <tr>
        <th>Plate ID</th>
        <th>Z Factor</th>
        <th>Pos Ave</th>
        <th>Pos STDEV</th>
        <th>Neg Ave</th>
        <th>Neg STDEV</th>
      </tr>";

	foreach ($both_plates as $val){
		//Pulls the raw reads from negative controls in the table, then stores means and stdevs in the overall array
		$positivevals=array();
		$negativevals=array();
		$query = "SELECT value, type FROM $tablearr[2] WHERE plate = ? AND type IN ('pos','neg') AND substance = 'yes';";
		$stmt = $con->prepare($query);
		$stmt->bind_param("s",$val);
		$stmt->execute();
		$stmt->bind_result($value,$type);
		$stmt->execute();
		$stmt->store_result();
		while($stmt->fetch())
		{
			if ($type == 'pos')
			{
				array_push($positivevals,$value);
			}
			elseif ($type == 'neg')
			{
				array_push($negativevals, $value);
			}
		}
		$posmean = (array_sum($positivevals)/count($positivevals));
		$negmean = (array_sum($negativevals)/count($negativevals));
		$posstdev = stdev($positivevals);
		$negstdev = stdev($negativevals);
	
		//Calculates individual Z factors for each plate based upon values calculated above
		$zfactor = (1 - ((3*$posstdev + 3*$negstdev)/abs($posmean-$negmean)));
		
		echo "
			<tr>
				<td>$val</td>
				<td>" . round($zfactor,4) . "</td>
				<td>" . round($posmean,4) . "</td>
				<td>" . round($posstdev,4) . "</td>
				<td>" . round($negmean,4) . "</td>
				<td>" . round($negstdev,4) . "</td>
			</tr>";

/*
 * $allposmean - Array of all positive control means, keyed by plate number
 * $allposstdev - Array of all positive control stdevs, keyed by plate number
 * $allnegmean - Array of all negative control means, keyed by plate number
 * $allnegstdev - Array of all negative control stdevs, keyed by plate number
 * $platezarr - Array of all individual Z' factors, keyed by plate number
 */

		$allposmean[$val] = $posmean;
		$allposstdev[$val] = $posstdev;
		$allnegmean[$val] = $negmean;
		$allnegstdev[$val] = $negstdev;
		$platezarr[$val] = $zfactor;
		foreach ($allnegmean as $key => $item)
		{
			if ($item > (array_sum($allnegmean)/count($allnegmean))*2)
			{
				unset($platezarr[$key]);
			}
		}
	}
}

$stmt->close();
$con->close();
echo "</table><br />\n";

echo "Overall Z Factor: " . array_sum($platezarr)/count($platezarr);

echo "<br /><br />";

?>
  
<?php
require_once("includes/footer.php");
?>