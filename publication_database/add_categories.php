<?php
require_once("includes/header.php");
?>
    <title>Add Organism or Substance</title>
  </head>
  <body>
    <div class="container">
      <div class="row">
        <div class="col-md-4">
          Please enter organism or substance to add: 
          <form action="add_categories_do.php" method="post" enctype="multipart/form-data">
            <label for="organism">Organism: </label>
            <input id="organism" class="form-control" type="text" name="organism" />
            <input type="submit" name="submit" value="Submit">
          </form>
            <br />
          <form action="add_categories_do.php" method="post" enctype="multipart/form-data">
            <label for="substance">Substance: </label>
            <input id="substance" class="form-control" type="text" name="substance" />
            <input type="submit" name="submit" value="Submit">      
          </form>
        </div>
      </div>
    </div>

<?php
require_once("includes/footer.php");
?>