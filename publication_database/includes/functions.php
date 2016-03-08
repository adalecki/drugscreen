<?php

/*
 * listOrganism() used in:
 * report.php
 * upload.php
 */
 function listOrganism($con)
{
	$query = "SELECT * FROM `organism`;";
	$stmt = $con->prepare($query);
	$stmt->execute();
	$stmt->bind_result($oid, $organism);
	while($stmt->fetch())
	{
		$orgarr[$oid]=$organism;
	}
	$stmt -> close();
	return $orgarr;
}

/*
 * listSubstance() used in:
 * report.php
 * upload.php
 */
function listSubstance($con)
{
	$query = "SELECT * FROM `substance`;";
	$stmt = $con->prepare($query);
	$stmt->execute();
	$stmt->bind_result($sid, $substance);
	while($stmt->fetch())
	{
		$subarr[$sid]=$substance;
	}
	$stmt -> close();
	return $subarr;
}

/*
 * getOrganism() used in:
 * report_do.php
 * structure_query.php
 * z_factor.php
 */
function getOrganism($organismid,$con)
{
	$query = "SELECT organism FROM organism WHERE oid=?;";
	$stmt = $con -> prepare($query);
	$stmt -> bind_param("s",$organismid);
	$stmt -> execute();
	$stmt -> bind_result($organism);
	$stmt -> fetch();
	$stmt -> close();
	return $organism;
}

/*
 * getSubstance() used in:
 * report_do.php
 * structure_query.php
 * z_factor.php
 */
function getSubstance($substanceid,$con)
{
	$query = "SELECT substance FROM substance WHERE sid=?;";
	$stmt = $con -> prepare($query);
	$stmt -> bind_param("s",$substanceid);
	$stmt -> execute();
	$stmt -> bind_result($substance);
	$stmt -> fetch();
	$stmt -> close();
	return $substance;
}

/*
 * tablenameGen() used in:
 * report_do.php
 * structure_query.php
 * upload_do
 * z_factor.php
 */
function tablenameGen($organism,$substance)
{
	$table = $organism . "_" . $substance;
	$tableplates = $table . "_" . "plates";
	$tablesamples = $table . "_" . "samples";
	$tablecontrols = $table . "_" . "controls";
	return array($tableplates,$tablesamples,$tablecontrols);
}

/*
 * stDev() used in:
 * z_factor.php
 */
function stDev(array $a)
{
	$n = count($a);
	if ($n === 0) {
		trigger_error("The array has zero elements", E_USER_WARNING);
		return false;
	}
	if ($sample && $n === 1) {
		trigger_error("The array has only 1 element", E_USER_WARNING);
		return false;
	}
	$mean = array_sum($a) / $n;
	$carry = 0.0;
	foreach ($a as $val) {
		$d = ((double) $val) - $mean;
		$carry += $d * $d;
	};

	return sqrt($carry / ($n-1));
}

/*
 * hitType() used in:
 * report_do.php
 * structure_query.php
 * z_factor.php
 */
function hitType($p_normd,$n_normd)
{
	if ($p_normd <= 10 && $n_normd >= ($p_normd + 30))
	{
		$hit_type = 1;
	}
	elseif ($p_normd <= 20 && $p_normd > 10 && $n_normd >= ($p_normd + 30))
	{
		$hit_type = 2;
	}
	elseif ($p_normd <= 30 && $p_normd > 20 && $n_normd >= ($p_normd + 30))
	{
		$hit_type = 3;
	}
	elseif (($p_normd <= 30) && ($n_normd <= 30))
	{
		$hit_type = 4;
	}
	elseif ($n_normd <= 30 && $p_normd >= ($n_normd + 40))
	{
		$hit_type = 5;
	}
	else
	{
		$hit_type = 0;
	}
	return $hit_type;
}

/*
 * outputCSV() used in:
 * csv-download.php
 */
function outputCSV($data)
{
    $output = fopen("php://output", "w");
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
	exit($data);
}

?>