<?php
require_once("includes/header.php");
?>
    <title>Add Organism</title>
  </head>
  <body>
    
<?php

if (isset($_POST['organism']))
{
	$organism = $_POST['organism'];
	$organism = str_replace(' ','_',$organism);
	
	if ($organism)
	{
		$query = "SELECT organism FROM organism WHERE organism=?;";
		$stmt = $con -> prepare($query);
		$stmt -> bind_param("s",$organism);
		$stmt -> execute();
		$stmt -> bind_result($count);
		$stmt -> fetch();
		$stmt -> close();
		if (!$count)
		{
			$query = "INSERT INTO organism VALUES ('',?);";
			$stmt = $con -> prepare($query);
			$stmt -> bind_param ("s",$organism);
			$stmt -> execute();
			$stmt -> close();
			echo "Organism successfully added: " . $organism;
		}
		else
		{
			echo "Duplicate organism detected.";
		}
	}
	else
	{
		echo "Please enter organism into the field.";
	}
}
elseif (isset($_POST['substance']))
{
	$substance = $_POST['substance'];
	$substance = str_replace(' ','_',$substance);
	
	if ($substance)
	{
		$query = "SELECT substance FROM substance WHERE substance=?;";
		$stmt = $con -> prepare($query);
		$stmt -> bind_param("s",$substance);
		$stmt -> execute();
		$stmt -> bind_result($count);
		$stmt -> fetch();
		$stmt -> close();
		if (!$count)
		{
			$query = "INSERT INTO substance VALUES ('',?);";
			$stmt = $con -> prepare($query);
			$stmt -> bind_param ("s",$substance);
			$stmt -> execute();
			$stmt -> close();
			echo "Substance successfully added: " . $substance;
		}
		else
		{
			echo "Duplicate substance detected.";
		}
	}
	else
	{
		echo "Please enter substance into the field.";
	}
}

?>

<?php require_once("includes/footer.php");?>