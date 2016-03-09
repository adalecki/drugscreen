<?php
require_once("includes/header.php");
?>

  <title>Report Generator</title>

<?php

/* 
 * These two MySQL queries pull all the currently existing
 * organisms and substances from the database and populate
 * the dropdown menus. An array is generated with the
 * organism ID (oid) as the index and the organism string
 * as the value. Same concept applied for $subarr.
 */

$subarr = listSubstance($con);
$orgarr = listOrganism($con);
$con -> close();
?>
  <div class="container">
    <div class="row">
    <div class="col-md-4">
      <h2>Overall Screening Report</h2>
  Please enter desired report information:
      <form action="report_do.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
          <label for="organism">Organism? </label> 
          <select class="form-control" id="organism" name="organism">
            <option value="null"></option>

<?php
foreach ($orgarr as $oid => $organism)
{
    echo "<option value='$oid'>$organism</option>";
}
?>
          </select>
          <label for="substance">Substance? </label> 
          <select class="form-control" id="substance" name="substance">
            <option value="null"></option>
<?php 
foreach ($subarr as $sid => $substance)
{
    echo "<option value='$sid'>$substance</option>";
}
?>
          </select>
          <label for="sort">Sort by: </label>
          <select class="form-control" id="sort" name="sort">
            <option value="hit_type">Hit Type</option>
            <option value="plate_id">Plate</option>
            <option value="compound_id">Compound ID</option>
          </select><br />
          Hits to be displayed: <br>
            <div class="checkbox-inline"><label><input type="checkbox" name="primary" value=1 checked>Primary</label></div>
            <div class="checkbox-inline"><label><input type="checkbox" name="secondary" value=2 checked>Secondary</label></div>
            <div class="checkbox-inline"><label><input type="checkbox" name="tertiary" value=3 checked>Tertiary</label></div>
            <div class="checkbox-inline"><label><input type="checkbox" name="indy" value=4 checked>Independent</label></div>
            <div class="checkbox-inline"><label><input type="checkbox" name="inverse" value=5 checked>Inverse</label></div><br><br>
          Display Structures? <br>
            <div class="radio-inline"><input type="radio" name="structure" value="yes">Yes</label></div>
            <div class="radio-inline"><input type="radio" name="structure" value="no" checked>No</label></div><br />
          <input class="btn btn-default btn-file" type="submit" value="Submit"></span>
        </div>
      </form>
    </div>
    
    <div class="row">
    <div class="col-md-4">
      <h2>Structure Query</h2>
      <a href="query_instructions.php" target="_blank">Search Instructions</a><br>
      <form action="structure_query.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
          <label for="searchtype">Type of Search? </label><br>
            <div class="radio-inline"><input type="radio" name="searchtype" value="whole">Whole Library</label></div><br />
            <div class="radio-inline"><input type="radio" name="searchtype" value="screened" checked>Screened Library</label></div><br />
            <div class="radio-inline"><input type="radio" name="searchtype" value="hits">Hits in Screen</label></div><br />
          <label for="motif">Structural Motif? </label>
            <input class="form-control" type="text" name="motif" id="motif" value="c12ccccc1cccc2">
          <label for="organism">Organism? </label> 
            <select class="form-control" id="organism" name="organism">
              <option value="null"></option>
<?php
foreach ($orgarr as $oid => $organism)
{
    echo "<option value='$oid'>$organism</option>";
}
?>
            </select>
            <label for="substance">Substance? </label> 
            <select class="form-control" id="substance" name="substance">
              <option value="null"></option>
<?php 
foreach ($subarr as $sid => $substance)
{
    echo "<option value='$sid'>$substance</option>";
}
?>
            </select>
          <input class="btn btn-default btn-file" type="submit" name="one_motif" value="Substructure Query">
        </div>
      </form>
    </div>
  
    <div class="row">
    <div class="col-md-4">
      <h2>Z Factor Calculations</h2>
      <form action="z_factor.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
          <label for="organism">Organism? </label> 
          <select class="form-control" id="organism" name="organism">
            <option value="null"></option>

<?php
foreach ($orgarr as $oid => $organism)
{
    echo "<option value='$oid'>$organism</option>";
}
?>
          </select>
          <label for="substance">Substance? </label> 
          <select class="form-control" id="substance" name="substance">
            <option value="null"></option>

<?php
foreach ($subarr as $sid => $substance)
{
    echo "<option value='$sid'>$substance</option>";
}
?>
          </select>
          <input class="btn btn-default btn-file" type="submit" name="z_factor" value="Z Factor">
          <input class="btn btn-default btn-file" type="submit" name="z_prime" value="Z' Factor">
        </div>
      </form>
    </div>
    </div>
<?php require_once("includes/footer.php");?>
