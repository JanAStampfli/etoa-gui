<?PHP
	//////////////////////////////////////////////////
	//		 	 ____    __           ______       			//
	//			/\  _`\ /\ \__       /\  _  \      			//
	//			\ \ \L\_\ \ ,_\   ___\ \ \L\ \     			//
	//			 \ \  _\L\ \ \/  / __`\ \  __ \    			//
	//			  \ \ \L\ \ \ \_/\ \L\ \ \ \/\ \   			//
	//	  		 \ \____/\ \__\ \____/\ \_\ \_\  			//
	//			    \/___/  \/__/\/___/  \/_/\/_/  	 		//
	//																					 		//
	//////////////////////////////////////////////////
	// The Andromeda-Project-Browsergame				 		//
	// Ein Massive-Multiplayer-Online-Spiel			 		//
	// Programmiert von Nicolas Perrenoud				 		//
	// als Maturaarbeit '04 am Gymnasium Oberaargau	//
	// www.etoa.ch | mail@etoa.ch								 		//
	//////////////////////////////////////////////////
	//
	// $Author$
	// $Date$
	// $Rev$
	//

 	// Datenänderung übernehmen
  if (isset($_POST['data_submit']) && checker_verify())
  {
  	$cu->properties->spyShipId = $_POST['spyship_id'];
  	$cu->properties->spyShipCount = $_POST['spyship_count'];
  	$cu->properties->analyzeShipId = $_POST['analyzeship_id'];
  	$cu->properties->analyzeShipCount = $_POST['analyzeship_count'];
  	$cu->properties->startUpChat = $_POST['startup_chat'];
	$cu->properties->showCellreports = $_POST['show_cellreports'];
	
	if (strlen($_POST['chat_color'])==3 || strlen($_POST['chat_color'])==6)
  		$cu->properties->chatColor = $_POST['chat_color'];
	else
  		$cu->properties->chatColor = "FFF";
    success_msg("Benutzer-Daten wurden ge&auml;ndert!");
  }
			

  echo "<form action=\"?page=$page&mode=game\" method=\"post\" enctype=\"multipart/form-data\">";
  $cstr = checker_init();
  tableStart("Spieloptionen");

  echo "<tr>
  	<th><b>Anzahl Spionagesonden für Direktscan:</b></th>
    <td>
    	<input type=\"text\" name=\"spyship_count\" maxlength=\"5\" size=\"5\" value=\"".$cu->properties->spyShipCount."\">
    </td>
  </tr>";
            
  echo "<tr><th>Typ des Spionageschiffs für Direktscan:</th>
  <td>";
	$sres = dbquery("
	SELECT 
    ship_id, 
    ship_name
	FROM 
		ships 
	WHERE 
		ship_buildable='1'
		AND (
		ship_actions LIKE '%,spy'
		OR ship_actions LIKE 'spy,%'
		OR ship_actions LIKE '%,spy,%'
		OR ship_actions LIKE 'spy'
		)
	ORDER BY 
		ship_name ASC");
  if (mysql_num_rows($sres)>0)
  {
  	echo '<select name="spyship_id"><option value="0">(keines)</option>';
  	while ($sarr=mysql_fetch_array($sres))
  	{
  		echo '<option value="'.$sarr['ship_id'].'"';
  		if ($cu->properties->spyShipId == $sarr['ship_id'])
  		 echo ' selected="selected"';
  		echo '>'.$sarr['ship_name'].'</option>';
  	}
  }
  else
  {
  	echo "Momentan steht kein Schiff zur Auswahl!";
  }
  echo "</td></tr>";
  
  echo "<tr>
  	<th><b>Anzahl Analyzatoren für Quickanalyse:</b></th>
    <td>
    	<input type=\"text\" name=\"analyzeship_count\" maxlength=\"5\" size=\"5\" value=\"".$cu->properties->analyzeShipCount."\">
    </td>
  </tr>";
  
  echo "<tr><th>Typ des Analyzators für Quickanalyse:</th>
  <td>";
	$sres = dbquery("
	SELECT 
    ship_id, 
    ship_name
	FROM 
		ships 
	WHERE 
		ship_buildable='1'
		AND (
		ship_actions LIKE '%,analyze'
		OR ship_actions LIKE 'analyze,%'
		OR ship_actions LIKE '%,analyze,%'
		OR ship_actions LIKE 'analyze'
		)
	ORDER BY 
		ship_name ASC");
  if (mysql_num_rows($sres)>0)
  {
  	echo '<select name="analyzeship_id"><option value="0">(keines)</option>';
  	while ($sarr=mysql_fetch_array($sres))
  	{
  		echo '<option value="'.$sarr['ship_id'].'"';
  		if ($cu->properties->analyzeShipId == $sarr['ship_id'])
  		 echo ' selected="selected"';
  		echo '>'.$sarr['ship_name'].'</option>';
  	}
  }
  else
  {
  	echo "Momentan steht kein Schiff zur Auswahl!";
  }
  echo "</td></tr>";
	//Berichte im Sonnensystem (Aktiviert/Deaktiviert)
  echo "<tr>
    			<th>Berichte im Sonnensystem:</th>
    			<td>
              <input type=\"radio\" name=\"show_cellreports\" value=\"1\" ";
              if ($cu->properties->showCellreports==1) echo " checked=\"checked\"";
              echo "/> Aktiviert &nbsp; 
          
              <input type=\"radio\" name=\"show_cellreports\" value=\"0\" ";
              if ($cu->properties->showCellreports==0) echo " checked=\"checked\"";
    					echo "/> Deaktiviert
    		</td>
  		</tr>";
	//Notizbox (Aktiviert/Deaktiviert)
  echo "<tr>
    			<th>Chat beim Login öffnen:</th>
    			<td>
              <input type=\"radio\" name=\"startup_chat\" value=\"1\" ";
              if ($cu->properties->startUpChat==1) echo " checked=\"checked\"";
              echo "/> Aktiviert &nbsp; 
          
              <input type=\"radio\" name=\"startup_chat\" value=\"0\" ";
              if ($cu->properties->startUpChat==0) echo " checked=\"checked\"";
    					echo "/> Deaktiviert
    		</td>
  		</tr>";  
// Chat font color
echo "<tr>
  			<th>Chat Schriftfarbe:</th>
  			<td>
            #<input type=\"text\"
					id=\"chat_color\"
					name=\"chat_color\"
					size=\"6\"
					maxsize=\"6\"
					value=\"".$cu->properties->chatColor."\"
					onkeyup=\"addFontColor(this.id,'chatPreview')\"
					onchange=\"addFontColor(this.id,'chatPreview')\"/>&nbsp;
			<div id=\"chatPreview\" style=\"color:#".$cu->properties->chatColor.";\">&lt;".$cu." | ".date("H:i",time())."&gt; Chatvorschau </div>
  		</td>
		</tr>";
      		

  tableEnd();
  echo "<input type=\"submit\" name=\"data_submit\" value=\"&Uuml;bernehmen\"/>";
  echo "</form><br/><br/>";
?>