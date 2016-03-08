<?php
require_once("includes/header.php");
?>
    <title>Structure Query Instructions</title>
  </head>
  <body>Please see <a href="http://www.daylight.com/dayhtml/doc/theory/theory.smarts.html" target="_blank">this page</a> for an exhaustive description of SMARTS queries.<br><br>
  Quick examples:<br>
  <li>Uppercase letter: Aliphatic atom</li>
  <li>Lowercase letter: Aromatic atom</li>
  <li>Brackets ([]): Wildcard, either aliphatic or aromatic atom</li>
  <li>[#6]: Wildcard carbon atom</li>
  <li>[Ca]: Calcium ion</li>
  <li>(CCC): Parentheses represent side chains</li>
  <br><br>
  Example query for the NNSN motif:<br>
  [#7][#6][#7][#6](=S)[#7]<br>
  (Wildcard nitrogen, wildcard carbon, wildcard nitrogen, wildcard carbon with double bonded sulfur attached, wildcard nitrogen)

<?php
require_once("includes/footer.php");
?>
