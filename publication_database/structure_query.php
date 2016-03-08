<?php
require_once("includes/header.php");
?>

<title>Structure Query</title>
<div class="container">
<?php

$searchtype = $_POST['searchtype'];
$motif = $_POST['motif'];
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
echo $substance . "<br />";
echo "Motif: " . $motif . "<br><br>";

/*
 * In $tablearr, [0] is the plates table, [1] is the samples table, [2] is the controls table.
 */
 
$tablearr = tablenameGen($organism,$substance);
$idarr = array();

/*
 * This section retrieves the type of search desired (all compounds, compounds in a specific
 * screen, or only hits in a specific screen) and inserts all relevant compound IDs into an
 * array ($idarr).
 */

if($searchtype=="whole")
{
	$query = "SELECT compoundid FROM $master_compound_table;";
	$stmt = $con -> prepare($query);
	$stmt -> execute();
	$stmt -> bind_result($compoundid);
	while ($stmt -> fetch())
	{
		array_push($idarr, $compoundid);
	}
	$stmt -> close();
}
elseif($searchtype=="screened")
{
	$query = "SELECT compound_id FROM $tablearr[1];";
	$stmt = $con -> prepare($query);
	$stmt -> execute();
	$stmt -> bind_result($compoundid);
	while ($stmt -> fetch())
	{
		array_push($idarr, $compoundid);
	}
	$stmt -> close();
}
elseif($searchtype=="hits")
{
	$query = "SELECT compound_id, p_corrandnorm, n_corrandnorm FROM $tablearr[1];";
	$stmt = $con -> prepare($query);
	$stmt -> execute();
	$stmt -> bind_result($compoundid, $p_normd, $n_normd);
	while ($stmt -> fetch())
	{
		$hit_type=hitType($p_normd,$n_normd);
		if($hit_type==1 || $hit_type==2 || $hit_type==3)
		{
			array_push($idarr, $compoundid);
		}
	}
	$stmt -> close();
}
$compounds=0;
$motifcriteria=0;
$dir = $_SERVER["DOCUMENT_ROOT"] . $document_path . '/temp';

$tempfile = fopen($dir . "/search.smiles", "w");

/* 
 * Using the array of compound IDs generated above, the IDs are written to a SMILES file in the
 * database/temp folder, which will be used as the OpenBabel input file.
 */

$idimplode = implode(',',$idarr);
$query = "SELECT compoundid, smiles FROM $master_compound_table WHERE compoundid IN ($idimplode);";
$stmt = $con -> prepare($query);
$stmt -> execute();
$stmt -> bind_result($compoundid, $smiles);
$stmt -> store_result();
while ($stmt->fetch())
{
	fwrite($tempfile, $smiles . "	" . $compoundid . "\n");
	$counter++;
}
$stmt -> close();

/*
 * This executes the OpenBabel SMARTS search using the input file generated above.
 */

fseek($tempfile, 0);
exec('obabel ' . $dir . '/search.smiles -O ' . $dir . '/output.smiles -s "' . $motif . '"');
fclose($tempfile);

$outputfile = fopen($dir . "/output.smiles","r");

/*
 * Here, for each compound ID that will be displayed in the table, the code checks the image folder (database/svgimages/)
 * for the compound's SVG image. If the image is not already present, it executes an OpenBabel command to generate said
 * SVG file. Thus, while the first search may be somewhat slow as images are generated individually, every subsequent
 * search will be much faster (as images can be loaded without dynamic generation).
 */
echo "<table class=\"table table-bordered table-hover table-condensed\">
      <tr>
        <th>Compound ID</th>
		<th>SMILES</th>
		<th>Structure</th>
	  </tr>";
if ($outputfile)
{
	while (($line = fgets($outputfile)) !==false)
	{
		$motifcriteria++;
		list($smiles, $compoundid) = split("	",$line);
		$compoundid = 0 + $compoundid;
		if(file_exists($_SERVER["DOCUMENT_ROOT"] . $document_path . '/svgimages/' . $compoundid . '.svg')==FALSE)
		{
			exec('obabel -:"' . $smiles . '" -O ' . $_SERVER["DOCUMENT_ROOT"] . $document_path . '/svgimages/' . $compoundid . '.svg');
		}
		echo "<tr>
				<td>$compoundid</td>
				<td>$smiles</td>
				<td><img src=\"$document_path/svgimages/$compoundid.svg\" width=\"200\" height=\"150\"/></td>
			  </tr>";
		$temparr=array();
		array_push($temparr, $compoundid, $smiles);
		$csvfile[] = $temparr;
	}
	fclose($outputfile);
}
else
{
	echo "Error opening output file.";
}
echo "</table>";

echo "Compounds queried: " . $compounds . "<br>";
echo "Motifs found: " . $motifcriteria . "<br>";

$con->close();
$csv_ser = serialize($csvfile);
$csv_urlen = urlencode($csv_ser);
?>

<form action="csv-download.php" method="post">

<?php 
echo "
<input type=\"hidden\" name=\"array\" value=\"$csv_urlen\">
<input type=\"hidden\" name=\"table\" value=\"$tablearr[1]\">";
?>

<input type="submit" value="Generate Excel CSV File">
</div>
<?php require_once("includes/footer.php"); ?>