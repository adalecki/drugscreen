<?php
require_once("includes/header.php");
?>

    <title>Upload Files</title>
    <div class="container">
      <h4>Plate Entry</h4>
      <form action="upload_do.php" method="post" enctype="multipart/form-data">
        <span class="btn btn-default btn-file">Substance Positive Plates<input type="file" name="pos_file[]" id="pos_file" multiple></span>
        <span class="btn btn-default btn-file">Substance Negative Plates<input type="file" name="neg_file[]" id="neg_file" multiple></span>
        <br><br>
        <div class="form-group">   
          <label for="organism">Organism? </label> 
          <select id="organism" name="organism">
            <option value="null"></option>

<?php
/*
 * These functions are retrieved from the includes/functions.php file in the header.
 */
$subarr = listSubstance($con);
$orgarr = listOrganism($con);

foreach ($orgarr as $oid => $organism)
{
    echo "<option value='$oid'>$organism</option>";
}
?>
          </select>
          <br /><label for="substance">Substance? </label> 
          <select id="substance" name="substance">
            <option value="null"></option>

<?php

foreach ($subarr as $sid => $substance)
{
    echo "<option value='$sid'>$substance</option>";
}
$con -> close();
?>
          </select>
          <br><label for="expected_blank">Expected Blank? </label>
          <input type="text" name="expected_blank" id="expected_blank" value="1900">
        </div>
<?php
/* 
 * "input_table.php" is a large HTML file, as it codes for all 96 wells individually.
 * It is stored on a separate page for clarity of code, as well as modularity; simply
 * by editing the input_table.php page one could expand input to, say, a 384 well plate.
 */
require ("includes/input_table.php");
?>
      <br />Please enter any desired notes about this set of plates below: <br />
      <textarea name="notes" cols="50" rows="5"></textarea><br />
      <input type="submit" name="submit" value="Submit">
      </form>
    </div>
<?php require_once("includes/footer.php"); ?>