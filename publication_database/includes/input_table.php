<table class="table table-condensed">
<?php
$rowarr=array("A","B","C","D","E","F","G","H");
$colarr=array("1","2","3","4","5","6","7","8","9","10","11","12");
echo "
  <tr>
    <th></th>";
foreach($colarr as $col)
{
	echo "<th>$col</th>";
}
echo "
  </tr>";	
foreach($rowarr as $row)
{
	echo " 
	  <tr>
      <th>$row</th>";
	foreach($colarr as $col)
	{
		if($col=="1")
		{
			echo "
			<td><select id=\"$row$col\" name=\"$row$col\">
			<option value=\"blank\" selected>Blank</option>
			<option value=\"pos\">Positive</option>
			<option value=\"neg\">Negative</option>
			<option value=\"sample\">Sample</option>
			<option value=\"exclude\">Exclude</option>
			</select></td>";
		}
		elseif($col=="12" && ($row=="A" || $row=="B" || $row=="C" || $row=="D"))
		{
			echo "
			<td><select id=\"$row$col\" name=\"$row$col\">
			<option value=\"blank\">Blank</option>
			<option value=\"pos\" selected>Positive</option>
			<option value=\"neg\">Negative</option>
			<option value=\"sample\">Sample</option>
			<option value=\"exclude\">Exclude</option>
			</select></td>";
		}
		elseif($col=="12" && ($row=="E" || $row=="F" || $row=="G" || $row=="H"))
		{
			echo "
			<td><select id=\"$row$col\" name=\"$row$col\">
			<option value=\"blank\">Blank</option>
			<option value=\"pos\">Positive</option>
			<option value=\"neg\" selected>Negative</option>
			<option value=\"sample\">Sample</option>
			<option value=\"exclude\">Exclude</option>
			</select></td>";
		}
		else
		{
			echo "
			<td><select id=\"$row$col\" name=\"$row$col\">
			<option value=\"blank\">Blank</option>
			<option value=\"pos\">Positive</option>
			<option value=\"neg\">Negative</option>
			<option value=\"sample\" selected>Sample</option>
			<option value=\"exclude\">Exclude</option>
			</select></td>";
		}
	}
		
	echo "
      </tr>";
}
?>
</table>