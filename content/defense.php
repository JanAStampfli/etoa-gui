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
	// www.nicu.ch | mail@nicu.ch								 		//
	// als Maturaarbeit '04 am Gymnasium Oberaargau	//
	//////////////////////////////////////////////////
	//
	// 	File: defense.php
	// 	Created: 01.12.2004
	// 	Last edited: 13.03.2009
	// 	Last edited by: glaubinix <glaubinix@etoa.ch>
	//	
	/**
	* Builds planetar defense
	*
	* @author MrCage <mrcage@etoa.ch>
	* @copyright Copyright (c) 2004-2009 by EtoA Gaming, www.etoa.net
	*/	

	//Definition für "Info" Link
	define("ITEMS_TBL","defense");
	define("REQ_TBL","def_requirements");
	define("REQ_ITEM_FLD","obj_id");
	define("ITEM_ID_FLD","def_id");
	define("ITEM_NAME_FLD","def_name");
	define("RACE_TO_ADD"," AND (def_race_id=0 OR def_race_id='".$cu->raceId."')");
	define("ITEM_SHOW_FLD","def_show");
	define("ITEM_ORDER_FLD","def_order");
	define("NO_ITEMS_MSG","In dieser Kategorie gibt es keine Verteidigungsanlagen!");
	define("HELP_URL","?page=help&site=defense");

	// BEGIN SKRIPT //

	echo "<form action=\"?page=$page\" method=\"post\">";

	//Tabulator var setzten (für das fortbewegen des cursors im forumular)
	$tabulator = 1;

	// Schiffswerft Level und Arbeiter laden
	$werft_res = dbquery("
						 SELECT
						 	buildlist_current_level,
							buildlist_people_working,
							buildlist_deactivated
						FROM
							buildlist
						WHERE
							buildlist_entity_id='".$cp->id."'
							AND buildlist_building_id='".FACTORY_ID."'
							AND buildlist_current_level>='1'
							AND buildlist_user_id='".$cu->id."'");
	
	// Prüfen ob Werft gebaut ist
	if (mysql_num_rows($werft_res)>0)
	{
		$werft_arr = mysql_fetch_assoc($werft_res);
		define('CURRENT_FACTORY_LEVEL',$werft_arr['buildlist_current_level']);
		
		// Titel
		echo "<h1>Waffenfabrik (Stufe ".CURRENT_FACTORY_LEVEL.") des Planeten ".$cp->name."</h1>";

		// Ressourcen anzeigen
		$cp->resBox($cu->properties->smallResBox);

		// Prüfen ob dieses Gebäude deaktiviert wurde
		if ($werft_arr['buildlist_deactivated']>time())
		{
			iBoxStart("Geb&auml;ude nicht bereit");
			echo "Diese Waffenfabrik ist bis ".date("d.m.Y H:i",$werft_arr['buildlist_deactivated'])." deaktiviert.";
			iBoxEnd();
		}
		// Werft anzeigen
		else
		{
			/****************************
			*  Sortiereingaben speichern *
			****************************/
			if(count($_POST)>0 && isset($_POST['sort_submit']))
			{
				$cu->properties->itemOrderShip = $_POST['sort_value'];
				$cu->properties->itemOrderWay = $_POST['sort_way'];
			}
			
			
			//
			// Läd alle benötigten Daten in PHP-Arrays
			//
			
			// Vorausetzungen laden
			$res = dbquery("
						SELECT 
							* 
						FROM 
							def_requirements;");
			
			while ($arr = mysql_fetch_assoc($res))
			{
				//Gebäude Vorausetzungen
				if ($arr['req_building_id']>0) 
				{
					$req[$arr['obj_id']]['b'][$arr['req_building_id']]=$arr['req_level'];
				}
				
				//Technologie Voraussetzungen
				if ($arr['req_tech_id']>0) 
				{
					$req[$arr['obj_id']]['t'][$arr['req_tech_id']]=$arr['req_level'];
				}
			}


			//Technologien laden und Gentechlevel definieren
			$gen_tech_level = 0;
			$res = dbquery("
						SELECT 
							techlist_tech_id,
							techlist_current_level
						FROM 
							techlist 
						WHERE 
							techlist_user_id='".$cu->id."';");
			
			while ($arr = mysql_fetch_assoc($res))
			{
				$techlist[$arr['techlist_tech_id']]=$arr['techlist_current_level'];
				
				if($arr['techlist_tech_id']==GEN_TECH_ID && $arr['techlist_current_level']>0)
				{
					$gen_tech_level = $arr['techlist_current_level'];
				}
			}
			
			//Gebäude laden
			$res = dbquery("
						SELECT 
							buildlist_building_id,
							buildlist_current_level
						FROM 
							buildlist
						WHERE
							buildlist_entity_id='".$cp->id."';");
			
			while ($arr = mysql_fetch_assoc($res))
			{
				$buildlist[$arr['buildlist_building_id']]=$arr['buildlist_current_level'];
			}
			echo $building_fields;
			
			// Gebaute Verteidigung laden
			$res = dbquery("
						SELECT
							deflist_def_id,
							deflist_entity_id,
							deflist_count
						FROM
							deflist
						WHERE
							deflist_user_id='".$cu->id."';");
			
			while ($arr = mysql_fetch_assoc($res))
			{
				$deflist[$arr['deflist_def_id']][$arr['deflist_entity_id']]=$arr['deflist_count'];
			}
			
			// Bauliste vom aktuellen Planeten laden (wird nach "Abbrechen" nochmals geladen)
			$res = dbquery("
						SELECT
							queue_id,
							queue_def_id,
							queue_cnt,
							queue_starttime,
							queue_endtime,
							queue_objtime
						FROM
							def_queue
						WHERE
							queue_entity_id='".$cp->id."'
							AND queue_endtime>'".$time."'
						ORDER BY
							queue_starttime ASC;");
			
			while ($arr = mysql_fetch_assoc($res))
			{
				$queue[$arr['queue_id']] = $arr;
			}
			
			// Alle Verteidigung laden
			//Verteidigungsordnunr des Users beachten
			$order="def_".$cu->properties->itemOrderDef." ".$cu->properties->itemOrderWay."";
			$res = dbquery("
						SELECT
							def_id,
							def_name,
							def_shortcomment,
							def_costs_metal,
							def_costs_crystal,
							def_costs_plastic,
							def_costs_fuel,
							def_costs_food,
							def_fields,
							def_show,
							def_buildable,
							def_structure,
							def_shield,
							def_weapon,
							def_race_id,
							def_max_count,
							cat_name,
							cat_id
						FROM
							defense
							INNER JOIN
								def_cat
							ON
								def_cat_id=cat_id
						WHERE
							def_buildable='1'
							AND def_show='1'
							AND (def_race_id='0' OR def_race_id='".$cu->raceId."')
						ORDER BY
							cat_order,
							".$order.";");
			while ($arr = mysql_fetch_assoc($res))
			{
				$cat[$arr['cat_id']] = $arr['cat_name'];
				$arr['def_costs_metal'] *= $cu->specialist->costsDefense;
				$arr['def_costs_crystal'] *= $cu->specialist->costsDefense;
				$arr['def_costs_plastic'] *= $cu->specialist->costsDefense;
				$arr['def_costs_fuel'] *= $cu->specialist->costsDefense;
				$arr['def_costs_food'] *= $cu->specialist->costsDefense;
				$defs[$arr['def_id']] = $arr;
			}
			
			// Bauliste vom allen Planeten laden und nach Verteidigung zusammenfassen
			$queue_field = 0;
			$res = dbquery("
						SELECT
							queue_id,
							queue_def_id,
							SUM(queue_cnt) AS cnt
						FROM
							def_queue
						WHERE
							queue_user_id='".$cu->id."'
							AND queue_endtime>'".$time."'
						GROUP BY
							queue_def_id;");
			while ($arr = mysql_fetch_assoc($res))
			{
				$queue_total[$arr['queue_def_id']] = $arr['cnt'];
				$queue_fields += $arr['cnt'] * $defs[$arr['queue_def_id']]['def_fields'];
			}
			
			//Berechnet freie Felder 
			$fields_available = $cp->fields+$cp->fields_extra-$cp->fields_used - $queue_fields;
			
			// level zählen welches die Waffenfabrik über dem angegeben level ist und faktor berechnen
			$need_bonus_level = CURRENT_FACTORY_LEVEL - $cfg->p1('build_time_boni_waffenfabrik');
			if($need_bonus_level <= 0)
			{
				$time_boni_factor=1;
			}
			else
			{
				$time_boni_factor=1-($need_bonus_level*($cfg->get('build_time_boni_waffenfabrik')/100));
			}
			$people_working = $werft_arr['buildlist_people_working'];
	
			// Faktor der zurückerstatteten Ressourcen bei einem Abbruch des Auftrags berechnen
			if (CURRENT_FACTORY_LEVEL>=DEFQUEUE_CANCEL_MIN_LEVEL)
			{
				$cancel_res_factor = min(DEFQUEUE_CANCEL_END,DEFQUEUE_CANCEL_START+((CURRENT_FACTORY_LEVEL-DEFQUEUE_CANCEL_MIN_LEVEL)*DEFQUEUE_CANCEL_FACTOR));
			}
			else
			{
				$cancel_res_factor=0;
			}

			// Infos anzeigen
			echo "<div>";
			//echo '<div><div style="float:left;width:450px;text-align:left;font-size:9pt;">';											
			tableStart("Fabrik-Infos");
			echo "<tr><td class=\"tbldata\">";
			echo "<b>Eingestellte Arbeiter:</b> ".nf($people_working)."<br/>
			<b>Bauzeitverringerung:</b> ";
			if ($need_bonus_level>=0)
			{
				echo get_percent_string($time_boni_factor)." durch Stufe ".CURRENT_FACTORY_LEVEL."<br/>";
			}
			else
			{
				echo "Stufe ".$cfg->p1('build_time_boni_waffenfabrik')." erforderlich!<br/>";
			}
			if ($cu->specialist->defenseTime!=1) {
				echo "<b>Bauzeitverringerung durch ".$cu->specialist->name.":</b> ".get_percent_string($cu->specialist->defenseTime)."<br/>";
			}
			if ($cancel_res_factor>0)
			{
				echo "<b>Ressourcenrückgabe bei Abbruch:</b> ".($cancel_res_factor*100)."% (ohne ".RES_FOOD.", ".(DEFQUEUE_CANCEL_END*100)."% maximal)";
				$cancelable = true;
			}
			else
			{
				echo "<b>Abbruchmöglichkeit:</b> Stufe ".DEFQUEUE_CANCEL_MIN_LEVEL." erforderlich!";
				$cancelable = false;
			} 
			if ($cu->specialist->costsDefense!=1)
			{
				echo "<br/><br/><b>Kostenreduktion durch ".$cu->specialist->name.":</b> ".get_percent_string($cu->specialist->costsDefense);
			}
			echo "</td></tr>";   	

			
			
	/*************
	* Sortierbox *
	*************/
		
			//Legt Sortierwerte in einem Array fest
			$values = array(
							"order"=>"Vorgabe",
							"name"=>"Name",
							"points"=>"Kosten",
							"fields"=>"Felder",
							"weapon"=>"Waffen",
							"structure"=>"Struktur",
							"shield"=>"Schild",
							"costs_metal"=>"Titan",
							"costs_crystal"=>"Silizium",
							"costs_plastic"=>"PVC",
							"costs_fuel"=>"Tritium"
							);
			
			echo "<tr>
					<td class=\"tbldata\" style=\"text-align:center;\">
						<select name=\"sort_value\">";
						foreach ($values as $value => $name)
						{		
							echo "<option value=\"".$value."\"";
							if($cu->properties->itemOrderShip==$value)
							{
								echo " selected=\"selected\"";
							}
							echo ">".$name."</option>";							
						}																																																							
						echo "</select>
						
						<select name=\"sort_way\">";
						
							//Aufsteigend
							echo "<option value=\"ASC\"";
							if($cu->properties->itemOrderWay=='ASC') echo " selected=\"selected\"";
							echo ">Aufsteigend</option>";
							
							//Absteigend
							echo "<option value=\"DESC\"";
							if($cu->properties->itemOrderWay=='DESC') echo " selected=\"selected\"";
							echo ">Absteigend</option>";	
																		
						echo "</select>						
						
						<input type=\"submit\" class=\"button\" name=\"sort_submit\" value=\"Sortieren\"/>
					</td>
				</tr>";
			tableEnd();
			
			echo '<br style="clear:both;" /></div>';
			echo "</form>";
			
			echo "<form action=\"?page=".$page."\" method=\"post\">";
		
		
	/****************************
	*  Schiffe in Auftrag geben *
	****************************/
		
			if(count($_POST)>0 && isset($_POST['submit']) && checker_verify())
			{
				tableStart();
				echo "<tr><th>Ergebnisse des Bauauftrags</th></tr>";
				
				//Log variablen setzten
				$log_defs="";
				$total_duration=0;
				$total_metal=0;
				$total_crystal=0;
				$total_plastic=0;
				$total_fuel=0;
				$total_food=0;
					
				// Endzeit bereits laufender Aufträge laden
				$end_time=time();
				if(isset($queue))
				{
					// Speichert die letzte Endzeit, da das Array $queue nach queue_starttime (und somit auch endtime) sortiert ist
					foreach ($queue as $data)
					{
						$end_time = $data['queue_endtime'];
					}
				}


				//
				// Bauaufträge speichern
				//
				$counter=0;
				foreach ($_POST['build_count'] as $def_id => $build_cnt)
				{
					$build_cnt=nf_back($build_cnt);

					if ($build_cnt>0)
					{
			     		// Zählt die Anzahl Verteidigugn dieses Typs im ganzen Account...
			     		$def_count = 0;
			     		// ... auf den Planeten
			     		if(isset($deflist[$def_id][$cp->id()]))
			     		{
			      			$def_count += $deflist[$def_id][$cp->id];
			    		}
					
						// ... in der Bauliste
						if(isset($queue[$def_id]))
						{
							$def_count += $queue[$def_id];
						}
						
						//Anzahl überprüfen, ob diese die maximalzahl übersteigt, gegebenenfalls ändern
						if ($build_cnt + $def_count > $defs[$def_id]['def_max_count'] && $defs[$def_id]['def_max_count']!=0)
						{
							$build_cnt=max(0,$defs[$def_id]['def_max_count']-$def_count);
						}
					
						//Wenn der User nicht genug freie Felder hat, die Anzahl Anlagen drosseln
						if ($defs[$def_id]['def_fields']>0 && $fields_available - $defs[$def_id]['def_fields'] * $build_cnt < 0)
						{
							$build_cnt=floor($fields_available/$defs[$def_id]['def_fields']);
						}

						// TODO: Überprüfen
						//Wenn der User nicht genug Ress hat, die Anzahl Schiffe drosseln
						//Titan
						if ($defs[$def_id]['def_costs_metal']>0)
						{
							$bf['metal']=$cp->resMetal/$defs[$def_id]['def_costs_metal'];
						}
						else
						{
							$bc['metal']=0;
						}
						//Silizium
						if ($defs[$def_id]['def_costs_crystal']>0)
						{
							$bf['crystal']=$cp->resCrystal/$defs[$def_id]['def_costs_crystal'];
						}
						else
						{
							$bc['crystal']=0;
						}
						//PVC
						if ($defs[$def_id]['def_costs_plastic']>0) 
						{
							$bf['plastic']=$cp->resPlastic/$defs[$def_id]['def_costs_plastic']; 
						}
						else 
						{
							$bc['plastic']=0;
						}
						//Tritium
						if ($defs[$def_id]['def_costs_fuel']>0) 
						{
							$bf['fuel']=$cp->resFuel/$defs[$def_id]['def_costs_fuel']; 
						}
						else 
						{
							$bc['fuel']=0;
						}
						//Nahrung
						if ($_POST['additional_food_costs']>0 || $defs[$def_id]['def_costs_food']>0)
						{
							 $bf['food']=$cp->resFood/($_POST['additional_food_costs']+$defs[$def_id]['def_costs_food']); 
						}
						else 
						{
							$bc['food']=0;
						}
	
						//Anzahl Drosseln
						if ($build_cnt>floor(min($bf)))
						{
							$build_cnt=floor(min($bf));
						}
						
						//Check for Rene-Bug
						$additional_food_costs = $people_working*$cfg->value('people_food_require');
						if ($additional_food_costs!=$_POST['additional_food_costs'] || $_POST['additional_food_costs']<0) {
							$build_cnt=0;
						}
					
						//Anzahl muss grösser als 0 sein
						if ($build_cnt>0)
						{
							//Errechne Kosten pro auftrag schiffe
							$bc['metal']=$defs[$def_id]['def_costs_metal']*$build_cnt;
							$bc['crystal']=$defs[$def_id]['def_costs_crystal']*$build_cnt;
							$bc['plastic']=$defs[$def_id]['def_costs_plastic']*$build_cnt;
							$bc['fuel']=$defs[$def_id]['def_costs_fuel']*$build_cnt;
							$bc['food']=($_POST['additional_food_costs']+$defs[$def_id]['def_costs_food'])*$build_cnt;
	
							//Berechnete Ress provisorisch abziehen
							$cp->resMetal-=$bc['metal'];
							$cp->resCrystal-=$bc['crystal'];
							$cp->resPlastic-=$bc['plastic'];
							$cp->resFuel-=$bc['fuel'];
							$cp->resFood-=$bc['food'];

							// Bauzeit pro Schiff berechnen
							$btime = ($defs[$def_id]['def_costs_metal'] 
								+ $defs[$def_id]['def_costs_crystal'] 
								+ $defs[$def_id]['def_costs_plastic'] 
								+ $defs[$def_id]['def_costs_fuel'] 
								+ $defs[$def_id]['def_costs_food']) 
								/ GLOBAL_TIME * DEF_BUILD_TIME 
								* $time_boni_factor
								* $cu->specialist->defenseTime;

							// TODO: Überprüfen
							//Rechnet zeit wenn arbeiter eingeteilt sind
							$btime_min=$btime*(0.1-(GEN_TECH_LEVEL/100));
							if ($btime_min<DEFENSE_MIN_BUILD_TIME) $btime_min=DEFENSE_MIN_BUILD_TIME;
							$btime=$btime-$people_working*$cfg->value('people_work_done');
							if ($btime<$btime_min) $btime=$btime_min;
							$obj_time=ceil($btime);
	
							// Gesamte Bauzeit berechnen
							$duration=$build_cnt*$obj_time;
	
							// Setzt Starzeit des Auftrages, direkt nach dem letzten Auftrag
							$start_time = $end_time;
							$end_time = $start_time + $duration;

							// Auftrag speichern
							dbquery("
								INSERT INTO
								def_queue
									(queue_user_id,
									queue_def_id,
									queue_entity_id,
									queue_cnt,
									queue_starttime,
									queue_endtime,
									queue_objtime)
								VALUES
									('".$cu->id."',
									'".$def_id."',
									'".$cp->id."',
									'".$build_cnt."',
									'".$start_time."',
									'".$end_time."',
									'".$obj_time."');");
								$deflist_id = mysql_insert_id();


							// Queue Array aktualisieren
							$queue[$deflist_id]['queue_id'] = $deflist_id;
							$queue[$deflist_id]['queue_def_id'] = $def_id;
							$queue[$deflist_id]['queue_cnt'] = $build_cnt;
							$queue[$deflist_id]['queue_starttime'] = $start_time;
							$queue[$deflist_id]['queue_endtime'] = $end_time;
							$queue[$deflist_id]['queue_objtime'] = $obj_time;
						
							
							echo "<tr><td>".nf($build_cnt)." ".$defs[$def_id]['def_name']." in Auftrag gegeben!</td></tr>";
							
							//Rohstoffe summieren, diese werden nach der Schleife abgezogen
							$total_metal+=$bc['metal'];
							$total_crystal+=$bc['crystal'];
							$total_plastic+=$bc['plastic'];
							$total_fuel+=$bc['fuel'];
							$total_food+=$bc['food'];
							
							//Felder subtrahieren
							$fields_available -= $build_cnt * $defs[$def_id]['def_fields'];
						
						
							//Daten für Log speichern
							$log_defs.="<b>".$defs[$def_id]['def_name']."</b>: ".nf($build_cnt)." (".tf($duration).")<br>";
							$total_duration+=$duration;
						}
						else
						{
							echo "<tr><td>".$defs[$def_id]['def_name'].": Zu wenig Rohstoffe für diese Anzahl!</td></tr>";
						}
						$counter++;
					}							
				}
				
				// Die Roshtoffe der $c-variablen wieder beigeben, da sie sonst doppelt abgezogen werden
				$cp->resMetal+=$total_metal;
				$cp->resCrystal+=$total_crystal;
				$cp->resPlastic+=$total_plastic;
				$cp->resFuel+=$total_fuel;
				$cp->resFood+=$total_food;				
				
				//Rohstoffe vom Planeten abziehen und aktualisieren
				$cp->changeRes(-$total_metal,-$total_crystal,-$total_plastic,-$total_fuel,-$total_food);
												
				//Log schreiben
				$log_text = "
				<b>Verteidigungsauftrag Bauen</b><br><br>
				<b>User:</b> [USER_ID=".$cu->id.";USER_NICK=".$cu->nick."]<br>
				<b>Planeten:</b> [PLANET_ID=".$cp->id().";PLANET_NAME=".$cp->name."]<br>
				<b>Dauer des gesamten Auftrages:</b> ".tf($total_duration)."<br>
				<b>Ende des gesamten Auftrages:</b> ".date("Y-m-d H:i:s",$end_time)."<br>
				<b>Waffenfabrik Level:</b> ".CURRENT_FACTORY_LEVEL."<br>
				<b>Eingesetzte Bewohner:</b> ".nf($people_working)."<br>
				<b>Gen-Tech Level:</b> ".GEN_TECH_LEVEL."<br>
				<b>Eingesetzter Spezialist:</b> ".$cu->specialist->name."<br><br>
				<b>Kosten</b><br>
				<b>".RES_METAL.":</b> ".nf($total_metal)."<br>
				<b>".RES_CRYSTAL.":</b> ".nf($total_crystal)."<br>
				<b>".RES_PLASTIC.":</b> ".nf($total_plastic)."<br>
				<b>".RES_FUEL.":</b> ".nf($total_fuel)."<br>
				<b>".RES_FOOD.":</b> ".nf($total_food)."<br><br>
				<b>Rohstoffe auf dem Planeten</b><br><br>
				<b>".RES_METAL.":</b> ".nf($cp->resMetal)."<br>
				<b>".RES_CRYSTAL.":</b> ".nf($cp->resCrystal)."<br>
				<b>".RES_PLASTIC.":</b> ".nf($cp->resPlastic)."<br>
				<b>".RES_FUEL.":</b> ".nf($cp->resFuel)."<br>
				<b>".RES_FOOD.":</b> ".nf($cp->resFood)."<br><br>
				<b>Anlagen</b><br>
				".$log_defs."
				";
				
				//Log Speichern
				add_log_game_def($log_text,$cu->id,$cu->allianceId,$cp->id(),1,time());						
				
				if ($counter==0)
				{
					echo "<tr><td>Keine Verteidigung gew&auml;hlt!</td></tr>";
				}
				tableEnd();
			}

			
			checker_init();

	/*********************
	* Auftrag abbrechen  *
	*********************/
			if (isset($_GET['cancel']) && $_GET['cancel']>0 && $cancelable)
			{	
				$id = intval($_GET['cancel']);
				if (isset($queue[$id]))
				{
					
					//Zu erhaltende Rohstoffe errechnen
					$obj_cnt = min(ceil(($queue[$id]['queue_endtime']-max($time,$queue[$id]['queue_starttime']))/$queue[$id]['queue_objtime']),$queue[$id]['queue_cnt']);
					echo "Breche den Bau von ".$obj_cnt." ".$defs[$queue[$id]['queue_def_id']]['def_name']." ab...<br/>";
									
					$ret['metal']=$defs[$queue[$id]['queue_def_id']]['def_costs_metal']*$obj_cnt*$cancel_res_factor;
					$ret['crystal']=$defs[$queue[$id]['queue_def_id']]['def_costs_crystal']*$obj_cnt*$cancel_res_factor;
					$ret['plastic']=$defs[$queue[$id]['queue_def_id']]['def_costs_plastic']*$obj_cnt*$cancel_res_factor;
					$ret['fuel']=$defs[$queue[$id]['queue_def_id']]['def_costs_fuel']*$obj_cnt*$cancel_res_factor;
					$ret['food']=$defs[$queue[$id]['queue_def_id']]['def_costs_food']*$obj_cnt*$cancel_res_factor;
					

					// Daten für Log speichern
					$def_name = $defs[$queue[$id]['queue_def_id']]['def_name'];
					$queue_count = $queue[$id]['queue_cnt'];
					$queue_objtime = $queue[$id]['queue_objtime'];
					$start_time = $queue[$id]['queue_starttime'];
					$end_time = $queue[$id]['queue_endtime'];
					
					//Felder addieren
					$fields_available += $queue_count * $defs[$queue[$id]['queue_def_id']]['def_fields'];
					
										
					//Auftrag löschen
					dbquery("
						DELETE FROM
						 def_queue
						WHERE
							queue_id='".$id."';");
						
					// Nachkommende Aufträge werden Zeitlich nach vorne verschoben
					$tres=dbquery("
								SELECT
									queue_id,
									queue_def_id,
									queue_cnt,
									queue_starttime,
									queue_endtime,
									queue_objtime
								FROM
									def_queue
								WHERE
									queue_starttime>='".$end_time."'
									AND queue_entity_id='".$cp->id."'
								ORDER BY
									queue_starttime ASC
								;");
					
					if (mysql_num_rows($tres)>0)
					{						
						$new_starttime=max($start_time,time());
						while ($tarr=mysql_fetch_assoc($tres))
						{
							$new_endtime = $new_starttime + $tarr['queue_endtime'] - $tarr['queue_starttime'];
							dbquery("
								UPDATE
									def_queue
								SET
									queue_starttime='".$new_starttime."',
									queue_endtime='".$new_endtime."'
								WHERE
									queue_id='".$tarr['queue_id']."'
								");
							
							// Aktualisiert das Queue-Array
							$queue[$tarr['queue_id']]['queue_starttime'] = $new_starttime;
							$queue[$tarr['queue_id']]['queue_endtime'] = $new_endtime;
							 
							$new_starttime=$new_endtime;
						}
					}
					
					// Auftrag aus Array löschen
					$queue[$id] = NULL;
					
					//Rohstoffe dem Planeten gutschreiben und aktualisieren
					$cp->changeRes($ret['metal'],$ret['crystal'],$ret['plastic'],$ret['fuel'],$ret['food']);						
						
					echo "Der Auftrag wurde abgebrochen!<br/><br/>";
						
					//Log schreiben
					$log_text = "
					<b>Verteidigungsauftrag Abbruch</b><br><br>
					<b>User:</b> [USER_ID=".$cu->id.";USER_NICK=".$cu->nick."]<br>
					<b>Planeten:</b> [PLANET_ID=".$cp->id().";PLANET_NAME=".$cp->name."]<br>
					<b>Anlage:</b> ".$qarr['def_name']."<br>
					<b>Anzahl:</b> ".nf($qarr['queue_cnt'])."<br>
					<b>Auftragsdauer:</b> ".tf($qarr['queue_objtime']*$qarr['queue_cnt'])."<br><br>
					<b>Erhaltene Rohstoffe</b><br>
					<b>Faktor:</b> ".$cancel_res_factor."<br>
					<b>".RES_METAL.":</b> ".nf($ret['metal'])."<br>
					<b>".RES_CRYSTAL.":</b> ".nf($ret['crystal'])."<br>
					<b>".RES_PLASTIC.":</b> ".nf($ret['plastic'])."<br>
					<b>".RES_FUEL.":</b> ".nf($ret['fuel'])."<br>
					<b>".RES_FOOD.":</b> ".nf($ret['food'])."<br><br>
					<b>Rohstoffe auf dem Planeten</b><br><br>
					<b>".RES_METAL.":</b> ".nf($cp->resMetal)."<br>
					<b>".RES_CRYSTAL.":</b> ".nf($cp->resCrystal)."<br>
					<b>".RES_PLASTIC.":</b> ".nf($cp->resPlastic)."<br>
					<b>".RES_FUEL.":</b> ".nf($cp->resFuel)."<br>
					<b>".RES_FOOD.":</b> ".nf($cp->resFood)."<br>
					";
					
					//Log Speichern
					add_log_game_def($log_text,$cu->id,$cu->allianceId,$cp->id(),0,time());
				}
			}


	/*********************************
	* Liste der Bauaufträge anzeigen *
	*********************************/
			if(isset($queue))
			{
				tableStart("Bauliste");
				$first=true;
				$absolut_starttime=0;
				foreach ($queue as $data)
				{
					// Listet nur Die Datensätze aus, die auch eine Verteidiguns ID beinhalten, da ev. der Datensatz mit NULL gleichgesetzt wurde
					if(isset($data['queue_def_id']))
					{
						if ($first)
						{
							$obj_t_remaining = ((($data['queue_endtime']-$time) / $data['queue_objtime'])-floor(($data['queue_endtime']-$time) / $data['queue_objtime']))*$data['queue_objtime'];
							if ($obj_t_remaining==0)
							{
								$obj_t_remaining = $data['queue_objtime'];
							}
							$obj_time = $data['queue_objtime'];
	
							$absolute_starttime=$data['queue_starttime'];
	
							$obj_t_passed = $data['queue_objtime']-$obj_t_remaining;
							echo "<tr>
									<th colspan=\"2\">Aktuell</th>
									<th style=\"width:150px;\">Start</th>
									<th style=\"width:150px;\">Ende</th>
									<th style=\"width:80px;\" colspan=\"2\">Verbleibend</th>
								</tr>";
							echo "<tr>";
							echo "<td class=\"tbldata\" colspan=\"2\">".$ships[$data['queue_def_id']]['def_name']."</td>";
							echo "<td class=\"tbldata\">".df(time()-$obj_t_passed,1)."</td>";
							echo "<td class=\"tbldata\">".df(time()+$obj_t_remaining,1)."</td>";
							echo "<td class=\"tbldata\" colspan=\"2\">".tf($obj_t_remaining)."</td>
							</tr>";
							echo "<tr>
									<th style=\"width:40px;\">Anzahl</th>
									<th>Bauauftrag</th>
									<th style=\"width:150px;\">Start</th>
									<th style=\"width:150px;\">Ende</th>
									<th style=\"width:150px;\">Verbleibend</th>
									<th style=\"width:80px;\">Aktionen</th>
								</tr>";
							$first=false; 
						}
	
						echo "<tr>";
						echo "<td class=\"tbldata\" id=\"objcount\">".$data['queue_cnt']."</td>";
						echo "<td class=\"tbldata\">".$defs[$data['queue_def_id']]['def_name']."</td>";
						echo "<td class=\"tbldata\">".df($absolute_starttime,1)."</td>";
						echo "<td class=\"tbldata\">".df($absolute_starttime+$data['queue_endtime']-$data['queue_starttime'],1)."</td>";
						echo "<td class=\"tbldata\">".tf($data['queue_endtime']-time(),1)."</td>";
						echo "<td class=\"tbldata\" id=\"cancel\">";
						if ($cancelable)
						{
							echo "<a href=\"?page=$page&amp;cancel=".$data['queue_id']."\" onclick=\"return confirm('Soll dieser Auftrag wirklich abgebrochen werden?');\">Abbrechen</a>";
						}
						else
						{
							echo "-";
						}
						echo "</td>
						</tr>";
	
						//Setzt die Startzeit des nächsten Schiffes, auf die Endzeit des jetztigen Schiffes
						$absolute_starttime=$data['queue_endtime'];
					}
				}
				tableEnd();
			 	echo "<br/><br/>";

			}



	/***********************
	* Verteidigung auflisten    *
	***********************/

			$cnt = 0;
			if (isset($cat))
			{
				foreach ($cat as $cat_id => $cat_name)
				{
					tableStart($cat_name);
					$ccnt = 0;
					
					// Auflistung der Verteidigung (auch diese, die noch nicht gebaut wurden) 
					if (isset($defs))
					{
						//Einfache Ansicht
						if ($cu->properties->itemShow!='full')
						{
							echo '<tr>
											<th colspan="2" class="tbltitle">Anlage</th>
											<th class="tbltitle">Zeit</th>
											<th class="tbltitle">'.RES_METAL.'</th>
											<th class="tbltitle">'.RES_CRYSTAL.'</th>
											<th class="tbltitle">'.RES_PLASTIC.'</th>
											<th class="tbltitle">'.RES_FUEL.'</th>
											<th class="tbltitle">'.RES_FOOD.'</th>
											<th class="tbltitle">Anzahl</th>
										</tr>';
						}
						
						foreach ($defs as $data)
						{
							// Prüfen ob Schiff gebaut werden kann
							$build_def = 1;
							// Gebäude prüfen
							if (isset($req[$data['def_id']]['b']) && count($req[$data['def_id']]['b'])>0)
							{
								foreach ($req[$data['def_id']]['b'] as $id=>$level)
								{
									if (!isset($buildlist[$id]) || $buildlist[$id]<$level)
									{
										$build_def = 0;
									}
								}
							}
							// Technologien prüfen
							if (isset($req[$data['def_id']]['t']) && count($req[$data['def_id']]['t'])>0)
							{
								foreach ($req[$data['def_id']]['t'] as $id=>$level)
								{
									if (!isset($techlist[$id]) || $techlist[$id]<$level)
									{
										$build_def = 0;
									}
								}
							}
							
    			    		// Schiffdatensatz zeigen wenn die Voraussetzungen erfüllt sind und das Schiff in diese Kategorie gehört
							if ($build_def==1 && $data['cat_id']==$cat_id)
							{
								// Zählt die Anzahl Schiffe dieses Typs im ganzen Account...
								$def_count = 0;
								// ... auf den Planeten
								if(isset($deflist[$data['def_id']][$cp->id]))
								{
									$def_count += $deflist[$data['def_id']][$cp->id];
								}
								// ... in der Bauliste
								if(isset($queue[$data['def_id']]))
								{
									$def_count += $queue[$data['def_id']];
								}
								  
								// Bauzeit berechnen
								$btime = ($data['def_costs_metal']+$data['def_costs_crystal']+$data['def_costs_plastic']+$data['def_costs_fuel']+$data['def_costs_food']) / GLOBAL_TIME * DEF_BUILD_TIME * $time_boni_factor * $cu->specialist->defenseTime;
						 		$btime_min=$btime*(0.1-($gen_tech_level/100));
    			      			
								//Mindest Bauzeit
								if ($btime_min<DEFENSE_MIN_BUILD_TIME) 
								{
									$btime_min=DEFENSE_MIN_BUILD_TIME;
								}
								  
								$btime=ceil($btime-$people_working*$cfg->value('people_work_done'));
								if ($btime<$btime_min) 
								{
									$btime=$btime_min;
								}

								//Nahrungskosten berechnen
								$food_costs = $people_working*$cfg->value('people_food_require');
								
								//Nahrungskosten versteckt übermitteln
								echo "<input type=\"hidden\" name=\"additional_food_costs\" value=\"".$food_costs."\" />";
								$food_costs += $data['def_costs_food'];
								
								
								//Errechnet wie viele Verteidigung von diesem Typ maximal Gebaut werden können mit den aktuellen Rohstoffen
								
								//Felder
								if($$defs[$def_id]['def_fields']>0)
								{
									$build_cnt_fields=floor($fields_available/$$defs[$def_id]['def_fields']);
								}
								else
								{
									$build_cnt_fields=99999999999;
								}
								
								//Titan
								if($data['def_costs_metal']>0)
								{
									$build_cnt_metal=floor($cp->resMetal/$data['def_costs_metal']);
								}
								else
								{
									$build_cnt_metal=99999999999;
								}

								//Silizium
								if($data['def_costs_crystal']>0)
								{
									$build_cnt_crystal=floor($cp->resCrystal/$data['def_costs_crystal']);
								}
								else
								{
									$build_cnt_crystal=99999999999;
								}
						
								//PVC
								if($data['def_costs_plastic']>0)
								{
									$build_cnt_plastic=floor($cp->resPlastic/$data['def_costs_plastic']);
								}
								else
								{
									$build_cnt_plastic=99999999999;
								}
								
								//Tritium
								if($data['def_costs_fuel']>0)
								{
									$build_cnt_fuel=floor($cp->resFuel/$data['def_costs_fuel']);
								}
								else
								{
									$build_cnt_fuel=99999999999;
								}

								//Nahrung
								if($food_costs>0)
								{
									$build_cnt_food=floor($cp->resFood/$food_costs);
								}
								else
								{
									$build_cnt_food=99999999999;
								}

								//Begrente Anzahl baubar
								if($data['def_max_count']!=0)
								{
									$max_cnt=$data['def_max_count']-$def_count;
								}
								else
								{
									$max_cnt=99999999999;
								}

								//Effetiv max. baubare Verteidigung in Betrachtung der Rohstoffe und des Baumaximums
								$def_max_build=min($build_cnt_metal,$build_cnt_crystal,$build_cnt_plastic,$build_cnt_fuel,$build_cnt_food,$max_cnt,$build_cnt_fields);

								//Tippbox Nachricht generieren
								//X Schiffe baubar
								if($def_max_build>0)
								{
									$tm_cnt="Es k&ouml;nnen maximal ".nf($def_max_build)." Anlagen gebaut werden.";
								}
								//Zuwenig Rohstoffe. Wartezeit errechnen
								elseif($def_max_build==0)
								{
										//Wartezeit Titan
									if ($cp->prodMetal>0)
									{
										$bwait['metal']=ceil(($data['def_costs_metal']-$cp->resMetal)/$cp->prodMetal*3600);
									}
									else
									{
										$bwait['metal']=0;
									}
									
									//Wartezeit Silizium
									if ($cp->prodCrystal>0)
									{
										$bwait['crystal']=ceil(($data['def_costs_crystal']-$cp->resCrystal)/$cp->prodCrystal*3600);
									}
									else
									{ 
										$bwait['crystal']=0;
									}
									
									//Wartezeit PVC
									if ($cp->prodPlastic>0)
									{
										$bwait['plastic']=ceil(($data['def_costs_plastic']-$cp->resPlastic)/$cp->prodPlastic*3600);
									}
									else
									{ 
										$bwait['plastic']=0;
									}
									
									//Wartezeit Tritium
									if ($cp->prodFuel>0)
									{
										$bwait['fuel']=ceil(($data['def_costs_fuel']-$cp->resFuel)/$cp->prodFuel*3600);
									}
									else
									{ 
										$bwait['fuel']=0;
									}
									
									//Wartezeit Nahrung
									if ($cp->prodFood>0)
									{
										$bwait['food']=ceil(($food_costs-$cp->resFood)/$cp->prodFood*3600);
									}
									else
									{ 
										$bwait['food']=0;
									}
    			    		
									//Maximale Wartezeit ermitteln
									$bwmax=max($bwait['metal'],$bwait['crystal'],$bwait['plastic'],$bwait['fuel'],$bwait['food']);
									
									$tm_cnt="Rohstoffe verf&uuml;gbar in ".tf($bwmax)."";
								}
								else
								{
									$tm_cnt="";
								}

								//Stellt Rohstoff Rot dar, wenn es von diesem zu wenig auf dem Planeten hat
								//Titan
								if($data['def_costs_metal']>$cp->resMetal)
								{
									$ress_style_metal="style=\"color:red;\"";
								}
								else
								{
									$ress_style_metal="";
								}
								
								//Silizium
								if($data['def_costs_crystal']>$cp->resCrystal)
								{
									$ress_style_crystal="style=\"color:red;\"";
								}
								else
								{
									$ress_style_crystal="";
								}
								
								//PVC
								if($data['def_costs_plastic']>$cp->resPlastic)
								{
									$ress_style_plastic="style=\"color:red;\"";
								}
								else
								{
									$ress_style_plastic="";
								}
								
								//Tritium
								if($data['def_costs_fuel']>$cp->resFuel)
								{
									$ress_style_fuel="style=\"color:red;\"";
								}
								else
								{
									$ress_style_fuel="";
								}
								
								//Nahrung
								if($food_costs>$cp->resFood)
								{
									$ress_style_food="style=\"color:red;\"";
								}
								else
								{
									$ress_style_food="";
								}

								// Speichert die Anzahl gebauter Schiffe in eine Variable
								if(isset($deflist[$data['def_id']][$cp->id]))
								{
									$deflist_count = $deflist[$data['def_id']][$cp->id];
								}
								else
								{
									$deflist_count = 0;
								}
								
								// Volle Ansicht
 			   			      	if($cu->properties->itemShow=='full')
    					      	{
    					      		if ($ccnt>0)
    					      		{
    			      					echo "<tr>
    			      							<td colspan=\"5\" style=\"height:5px;\"></td>
    			      					</tr>";
    			      				}
    			     				$s_img = IMAGE_PATH."/".IMAGE_DEF_DIR."/def".$data['def_id']."_middle.".IMAGE_EXT;
    			      	  
    			      	  			echo "<tr>
    			      	  					<th colspan=\"5\" height=\"20\">".$data['def_name']."</th>
    			      	  				</tr>
    			      	  				<tr>
    			      	  					<td class=\"tbldata\" width=\"120\" height=\"120\" rowspan=\"3\">
												<a href=\"".HELP_URL."&amp;id=".$data[ITEM_ID_FLD]."\" title=\"Info zu dieser Anlage anzeigen\">
				    			      	  		<img src=\"".$s_img."\" width=\"120\" height=\"120\" border=\"0\" /></a>
    			      	  					</td>
    			      	  					<td class=\"tbldata\" colspan=\"4\" valign=\"top\">".$data['def_shortcomment']."</td>
    			      	  				</tr>
    			      	  				<tr>
    			      	  					<th class=\"tbltitle\"  height=\"30\">Vorhanden:</th>
				    			      		<td class=\"tbldata\">".nf($deflist_count)."</td>
											<th class=\"tbltitle\">Felder pro Einheit:</th>
			    			      	  		<td class=\"tbldata\">".nf($data['def_fields'])."</td>
				    			      	</tr>
				    			      	<tr>
				    			      		<th class=\"tbltitle\" height=\"30\">Bauzeit</th>
    			      	  					<td class=\"tbldata\">".tf($btime)."</td>";
									
									//Maximale Anzahl erreicht
									if ($def_count>=$data['def_max_count'] && $data['def_max_count']!=0)
									{
										echo "<th class=\"tbltitle\" height=\"30\" colspan=\"2\"><i>Maximalanzahl erreicht</i></th>";
									}
									else
									{
										echo "<th class=\"tbltitle\" height=\"30\">In Aufrag geben:</th>
				    			      	      <td class=\"tbldata\"><input type=\"text\" value=\"0\" name=\"build_count[".$data['def_id']."]\" id=\"build_count_".$data['def_id']."\" size=\"4\" maxlength=\"9\" ".tm("",$tm_cnt)." tabindex=\"".$tabulator."\" onkeyup=\"FormatNumber(this.id,this.value, ".$def_max_build.", '', '');\"/> St&uuml;ck<br><a href=\"javascript:;\" onclick=\"document.getElementById('build_count_".$data['def_id']."').value=".$def_max_build.";\">max</a></td>";
									}
									
									echo "</tr>";
									echo "<tr>
				    			    	  	  <th height=\"20\" width=\"110\">".RES_METAL.":</th>
				    			    	  	  <th height=\"20\" width=\"97\">".RES_CRYSTAL.":</th>
				    			    	  	  <th height=\"20\" width=\"98\">".RES_PLASTIC.":</th>
				    			    	  	  <th height=\"20\" width=\"97\">".RES_FUEL.":</th>
				    			    	  	  <th height=\"20\" width=\"98\">".RES_FOOD."</th></tr>";
									echo "<tr>
    			      	  					<td class=\"tbldata\" height=\"20\" width=\"110\" ".$ress_style_metal.">
    			      	  						".nf($data['def_costs_metal'])."
    			      	  					</td>
											<td class=\"tbldata\" height=\"20\" width=\"25%\" ".$ress_style_crystal.">
												".nf($data['def_costs_crystal'])."
											</td>
											<td class=\"tbldata\" height=\"20\" width=\"25%\" ".$ress_style_plastic.">
												".nf($data['def_costs_plastic'])."
											</td>
											<td class=\"tbldata\" height=\"20\" width=\"25%\" ".$ress_style_fuel.">
												".nf($data['def_costs_fuel'])."
											</td>
											<td class=\"tbldata\" height=\"20\" width=\"25%\" ".$ress_style_food.">
												".nf($food_costs)."
											</td>
										</tr>";
    			      			}
								//Einfache Ansicht der Schiffsliste
								else
								{
									$s_img = IMAGE_PATH."/".IMAGE_DEF_DIR."/def".$data['def_id']."_small.".IMAGE_EXT;
  			      					
  			      					echo "<tr>
  			      							<td class=\"tbldata\">
				  			      				<a href=\"".HELP_URL."&amp;id=".$data[ITEM_ID_FLD]."\"><img src=\"".$s_img."\" width=\"40\" height=\"40\" border=\"0\" /></a>
											</td>
											<th width=\"30%\">
	  			      							<span style=\"font-weight:500\">".$data['def_name']."<br/>
	  			      							Gebaut:</span> ".nf($deflist_count)."
	  			      						</th>
	  			      						<td class=\"tbldata\" width=\"13%\">".tf($btime)."</td>
	  			      						<td class=\"tbldata\" width=\"10%\" ".$ress_style_metal.">".nf($data['def_costs_metal'])."</td>
	  			      						<td class=\"tbldata\" width=\"10%\" ".$ress_style_crystal.">".nf($data['def_costs_crystal'])."</td>
	  			      						<td class=\"tbldata\" width=\"10%\" ".$ress_style_plastic.">".nf($data['def_costs_plastic'])."</td>
	  			      						<td class=\"tbldata\" width=\"10%\" ".$ress_style_fuel.">".nf($data['def_costs_fuel'])."</td>
	  			      						<td class=\"tbldata\" width=\"10%\" ".$ress_style_food.">".nf($food_costs)."</td>";
											
									//Maximale Anzahl erreicht
				  			      	if ($def_count>=$data['def_max_count'] && $data['def_max_count']!=0)
				  			      	{
				  			      	    echo "<td class=\"tbldata\">Max</td></tr>";
				  			      	}
				  			      	else
				  			      	{
				  			      	    echo "<td class=\"tbldata\"><input type=\"text\" value=\"0\" id=\"build_count_".$data['def_id']."\" name=\"build_count[".$data['def_id']."]\" size=\"5\" maxlength=\"9\" ".tm("",$tm_cnt)." tabindex=\"".$tabulator."\" onkeyup=\"FormatNumber(this.id,this.value, ".$def_max_build.", '', '');\"/><br><a href=\"javascript:;\" onclick=\"document.getElementById('build_count_".$data['def_id']."').value=".$def_max_build.";\">max</a></td></tr>";
				  			      	}
								}
								
								$tabulator++;
								$cnt++;
								$ccnt++;
							}
						}
					
						// Es können keine Schiffe gebaut werden
						if ($ccnt==0)
						{
							echo "<tr>
										<td colspan=\"9\" height=\"30\" align=\"center\" class=\"tbldata\">
											Es k&ouml;nnen noch keine Anlagen gebaut werden!<br>
											Baue zuerst die ben&ouml;tigten Geb&auml;ude und erforsche die erforderlichen Technologien!
										</td>
									</tr><br>";
						}
					}
					// Es gibt noch keine Schiffe
					else
					{
						echo "<tr><td align=\"center\" colspan=\"3\" class=\"tbldata\">Es gibt noch keine Schiffe!</td></tr>";
					}
				
   					tableEnd();
   				
					//Lücke zwischen Kategorien
					echo "<br/>";
				}
   				// Baubutton anzeigen
				if ($cnt > 0)
				{
					echo "<input type=\"submit\" name=\"submit\" value=\"Bauauftr&auml;ge &uuml;bernehmen\"/><br/><br/>";
				}
			}
			else
			{
				echo "<br>Noch keine Kategorien definiert!<br>";
			}
		}
	}
	else
	{
		// Titel
		echo "<h1>Waffenfabrik des Planeten ".$cp->name."</h1>";		
		
		// Ressourcen anzeigen
		$cp->resBox($cu->properties->smallResBox);
		error_msg("Die Waffenfabrik wurde noch nicht gebaut!");


	}
	echo "</form>";

?>
