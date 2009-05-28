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
	
	/**
	* Manages the cryptocenter
	*
	* @author MrCage <mrcage@etoa.ch>
	* @copyright Copyright (c) 2004-2009 by EtoA Gaming, www.etoa.net
	*/	

	// BEGIN SKRIPT //

	echo "<form action=\"?page=$page\" method=\"post\">";
	
	// Gebäude Level und Arbeiter laden
 	//$bl = new BuildList($cp->id,$cu->id);
	//$cryptoCenterLevel = $bl->getLevel(BUILD_CRYPTO_ID);
	if ($cu->allianceId!=0)
	{
		$cryptoCenterLevel = $cu->alliance->getBuildingLevel("Kryptocenter");
	}
	else
	{
		$cryptoCenterLevel = 0;
	}

	// Allg. deaktivierung
  if ($cfg->get('crypto_enable') == 1)
  {
	  /**
	  *
	  * Abschnitt mit Crypto als Allianzgebäude
	  *
	  **/
	  
	  // Prüfen ob Gebäude gebaut ist
	  if ($cryptoCenterLevel > 0)
	  {
			// Titel
			echo "<h1>Allianzkryptocenter (Stufe ".$cryptoCenterLevel.") der Allianz ".$cu->alliance->name."</h1>";		
			$cp->resBox($cu->properties->smallResBox);
			
			// Calculate cooldown
			$cooldown = max($cfg->param2("cryptocenter"),$cfg->value("cryptocenter") - ($cfg->param1("cryptocenter")*($cryptoCenterLevel-1)));
			if ($cu->alliance->getCryptoCooldown()>time())
			{
				$status_text = "Bereit in <span id=\"cdcd\">".tf($cu->alliance->getCryptoCooldown()-time()."</span>");
				$cd_enabled=true;
			}
			else
			{
				$status_text = "Bereit";
				$cd_enabled=false;
			}
			
			// Scan
			if (isset($_POST['scan']) && checker_verify() && !$cd_enabled)
			{
				if ($cu->alliance->checkActionRightsNA("cryptominister"))
				{
					$sx = intval($_POST['sx']);
					$sy = intval($_POST['sy']);
					$cx = intval($_POST['cx']);
					$cy = intval($_POST['cy']);
					$pp = intval($_POST['p']);
					if ($sx>0 && $sy>0 && $cx>0 && $cy>0 && $pp>0)
					{
						if ($cp->resFuel >= CRYPTO_FUEL_COSTS_PER_SCAN)
						{
							if ($cu->alliance->resFuel >= CRYPTO_FUEL_COSTS_PER_SCAN)
							{
								$target = Entity::createFactoryByCoords($sx,$sy,$cx,$cy,$pp);
								if (true)
								{
									$dist = $cp->distance($target);
									if ($dist <= CRYPTO_RANGE_PER_LEVEL*$cryptoCenterLevel)
									{
										// Load oponent's jamming device count
										$jres = dbquery("
														SELECT
															deflist_count
														FROM
															deflist
														INNER JOIN
															defense
														ON deflist_def_id=def_id
															AND deflist_count>0
															AND def_jam=1
															AND deflist_entity_id=".$target->id().";");
										$op_jam = 0;
										if (mysql_num_rows($jres)>0)
										{
											$jarr=mysql_fetch_row($jres);
											$op_jam = $jarr[0];
										}
										
										// Load oponents computer and stealth technologies  
										$tres = dbquery("
														SELECT
															techlist_current_level
														FROM 
															techlist
														WHERE
															techlist_tech_id=".TARN_TECH_ID."
															AND techlist_user_id=".$target->ownerId()."");
										$op_stealth = $target->owner->alliance->getTechLevel("Tarntechnik")+$target->owner->specialist->tarnLevel;
										
										if (mysql_num_rows($tres)>0)
										{
											$jarr=mysql_fetch_row($tres);
											$op_stealth = $jarr[0];
										}
										
										$tres = dbquery("
														SELECT
															techlist_current_level
														FROM 
															techlist
														WHERE
															techlist_tech_id=".COMPUTER_TECH_ID."
															AND techlist_user_id=".$target->ownerId()."");
										$op_computer = 0;
										if (mysql_num_rows($tres)>0)
										{
											$jarr=mysql_fetch_row($tres);
											$op_computer = $jarr[0];
										}
										
										// Load own computer and spy technologies
										$tres = dbquery("
														SELECT
															techlist_current_level
														FROM 
															techlist
														WHERE
															techlist_tech_id=".SPY_TECH_ID."
															AND techlist_user_id=".$cu->id."");
										$self_spy = $cu->alliance->getTechLevel("Spionagetechnik") + $cu->specialist->spyLevel;
										
										if (mysql_num_rows($tres)>0)
										{
											$jarr=mysql_fetch_row($tres);
											$self_spy = $jarr[0];
										}
										$tres = dbquery("
														SELECT
															techlist_current_level
														FROM 
															techlist
														WHERE
															techlist_tech_id=".COMPUTER_TECH_ID."
															AND techlist_user_id=".$cu->id."");
										$self_computer = 0;
										if (mysql_num_rows($tres)>0)
										{
											$jarr=mysql_fetch_row($tres);
											$self_computer = $jarr[0];
										}
										
										// Calculate success chance
										$chance = ($cryptoCenterLevel-$op_jam) + (0.3*($self_spy - $op_stealth)) + mt_rand(0,2)-1;
										
										// Do the scan if chance >= 0
										if ($chance >= 0)
										{
											$decryptlevel = ($cryptoCenterLevel-$op_jam) + (0.75*($self_spy + $self_computer - $op_stealth - $op_computer)) + mt_rand(0,2)-1;  
											
											// Decrypt level
											// < 0 Only show that there are some fleets
											// 0 <= 10 Show that there are x fleets
											// 10 <= 15 Show that there are x fleets from y belonging to z, show hour
											// 15 <= 20 Also show ship types, show mninutes with +- 15 mins
											// 20 <= 25 Also show count of ships and time in minutes
											// 25 <= 30 Also show count of every ship and exact time
											// >30 Show action
											
											$out="[b]Flottenscan vom Planeten ".$target->name()."[/b] (".$sx."/".$sy." : ".$cx."/".$cy." : ".$pp.")\n\n";
											
											$out.="[b]Eintreffende Flotten[/b]\n\n";
											$fres = dbquery("
															SELECT
																id
															FROM
																fleet
															WHERE
																entity_to=".$target->id()."");
											if (mysql_num_rows($fres)>0)
											{
												if ($decryptlevel<0)
												{
													$out.="Es sind Flotten unterwegs\n"; 
												}
												else if ($decryptlevel<10)
												{
													$out.="Es sind ".mysql_num_rows($fres)." Flotten unterwegs\n"; 
												}
												else
												{
													while ($farr=mysql_fetch_row($fres))
													{
														$fd = new Fleet($farr[0]);
														$source = $fd->getSource();
														$owner = new User($fd->ownerId());
														
														$out.='[b]Herkunft:[/b] '.$source.', [b]Besitzer:[/b] '.$owner;
														$out.= "\n[b]Ankunft:[/b] ";
														
														if ($decryptlevel<=15)
														{
															$rand = mt_rand(0,30*60*2);
															$out.="Zwischen ".date("d.m.Y H:i",$fd->landTime() - $rand)." und ".date("d.m.Y H:i",$fd->landTime()+(2*30*60)-$rand)." Uhr";
														}
														elseif ($decryptlevel<=20)
														{
															$rand = mt_rand(2*7*60);
															$out.="Zwischen ".date("d.m.Y H:i",$fd->landTime()-$rand)." und ".date("d.m.Y H:i",$fd->landTime()+(2*7*60)-$rand)." Uhr";
														}
														elseif ($decryptlevel<=25)
														{
															$out.=date("d.m.Y H:i",$fd->landTime())." Uhr";                            
														}
														else
														{
															$out.=date("d.m.Y H:i:s",$fd->landTime())." Uhr";                            
														}
														
														if ($decryptlevel>30)
														{       
															$out.=", [b]Aktion:[/b] ".$fd->getAction()."\n";
														}
														else
															$out.="\n";
														
														if ($decryptlevel>=15)
														{
															$sres = dbquery("
																			SELECT
																				ship_name,
																				fs_ship_cnt
																			FROM
																				fleet_ships
																			INNER JOIN
																				ships
																			ON ship_id=fs_ship_id
																				AND fs_fleet_id=".$farr[0].";");
															if (mysql_num_rows($sres)>0)
															{
																$cntr=0;
																while ($sarr=mysql_fetch_array($sres))
																{
																	if ($decryptlevel <=25 )
																	{
																		$out.="".$sarr['ship_name']."\n";
																	}
																	else
																	{
																		$out.=$sarr['fs_ship_cnt']." ".$sarr['ship_name']."\n";
																	}
																	$cntr+=$sarr['fs_ship_cnt'];
																}
																if ($decryptlevel >20)
																{
																	$out.=$cntr." Schiffe total\n"; 
																}
															}
														}
														$out.="\n";
													}
												}
											}
											else
											{
												$out.="Keine eintreffenden Flotten gefunden!\n\n";
											}
											
											$out.="[b]Wegfliegende Flotten[/b]\n\n";
											$fres = dbquery("
															SELECT
																id
															FROM
																fleet
															WHERE
																entity_from=".$target->id()."
																AND entity_to<>".$target->id()."");
											if (mysql_num_rows($fres)>0)
											{
												if ($decryptlevel<0)
												{
													$out.="Es sind Flotten unterwegs\n"; 
												}
												else if ($decryptlevel<10)
												{
													$out.="Es sind ".mysql_num_rows($fres)." Flotten unterwegs\n"; 
												}
												else
												{
													while ($farr=mysql_fetch_row($fres))
													{
														$fd = new Fleet($farr[0]);
														$source = $fd->getTarget();
														$owner = new User($fd->ownerId());
														
														$out.='[b]Ziel:[/b] '.$source.', [b]Besitzer:[/b] '.$owner;
														$out.= "\n[b]Ankunft:[/b] ";
														
														if ($decryptlevel<=15)
														{
															$out.="Zwischen ".date("d.m.Y H:i",$fd->landTime()-(30*60))." und ".date("d.m.Y H:i",$fd->landTime()+(30*60))." Uhr";
														}
														elseif ($decryptlevel<=20)
														{
															$out.="Zwischen ".date("d.m.Y H:i",$fd->landTime()-(7*60))." und ".date("d.m.Y H:i",$fd->landTime()+(7*60))." Uhr";
														}                          
														elseif ($decryptlevel<=25)
														{
															$out.=date("d.m.Y H:i",$fd->landTime())." Uhr";
														}
														else
														{
															$out.=date("d.m.Y H:i:s",$fd->landTime())." Uhr";
														}
														
														if ($decryptlevel>30)
														{
															$out.=", [b]Aktion:[/b] ".$fd->getAction()."\n";
														}
														else
															$out.="\n";
														
														if ($decryptlevel>=15)
														{
															$cntr=0;
															foreach ($fd->getShips() as $sid => $sdat)
															{
																$sids = $fd->getShipIds();
																$cnt = $sids[$sid];
																if ($decryptlevel <=25 )
																{
																	$out.="".$sdat."\n";
																}
																else
																{
																	$out.=$cnt." ".$sdat."\n";
																}
																$cntr+=$cnt;
															}
															if ($decryptlevel >20)
															{
																$out.=$cntr." Schiffe total\n"; 
															}
														}
														$out.="\n";
													}
												}
											}
											else
											{
												$out.='Keine abfliegenden Flotten gefunden!';
											}
											
											$out.="\n\nEntschlüsselchance: $decryptlevel";
											
											// Subtract resources
											$cp->changeRes(0,0,0,-CRYPTO_FUEL_COSTS_PER_SCAN,0);
											$cu->alliance->changeRes(0,0,0,-CRYPTO_FUEL_COSTS_PER_SCAN,0);
											
											// Inform oponent
											if ($target->ownerId()>0)
											{
												send_msg($target->ownerId(),SHIP_SPY_MSG_CAT_ID,"Funkstörung","Eure Flottenkontrolle hat soeben eine kurzzeitige Störung des Kommunikationsnetzes festgestellt. Es kann sein das fremde Hacker in das Netz eingedrungen sind und Flottendaten geklaut haben.");
											}
											
											// Display result
											iBoxStart("Ergebnis der Analyse");
											echo text2html($out);
											iBoxEnd();
											
											// Add note to user's notepad if selected
											if (isset($_POST['scan_to_notes']))
											{
												$np = new Notepad($cu->id);
												$np->add("Flottenscan: ".$target,$out);
											}										                   
											
											// Mail result (deprecated)
											//send_msg($cu->id,SHIP_MISC_MSG_CAT_ID,"Kryptocenter-Bericht",$out);
											
											// Set cooldown
											$cd = time()+$cooldown;
											dbquery("
													UPDATE
														alliance_buildlist
													SET
														alliance_buildlist_cooldown=".$cd."
													WHERE
														alliance_buildlist_alliance_id='".$cu->allianceId."'
														AND alliance_buildlist_building_id='".ALLIANCE_BUILD_CRYPTO_ID."';");
											
											$cu->alliance->setCryptoCooldown($cd);
											
											$cu->alliance->addHistory("Der Spieler [b]".$cu."[/b] hat den Planeten ".$target->name()."[/b] (".$sx."/".$sy." : ".$cx."/".$cy." : ".$pp.") gescannt!");
											
											if ($cu->alliance->getCryptoCooldown()>time())
											{
												$status_text = "Bereit in <span id=\"cdcd\">".tf($cu->alliance->getCryptoCooldown()-time()."</span>");
												$cd_enabled=true;
											}
											else
											{
												$status_text = "Bereit";
												$cd_enabled=false;
											}
										}
										else
										{
											if ($op_jam>0 && $target->ownerId()>0)
											{
												send_msg($target->ownerId(),SHIP_SPY_MSG_CAT_ID,"Störsender erfolgreich","Eure Techniker haben festgestellt, dass von einem anderen Planeten eine Entschlüsselung eures Funkverkehrs versucht wurde. Daraufhin haben eure Störsender die Funknetze mit falschen Werten überlastet, so dass die gegnerische Analyse fehlschlug!");
											}
											error_msg("Die Analyse schlug leider fehl! Eure Empfangsgeräte haben zu viel Rauschen aufgenommen; anscheinend hat der Zielplanet ein aktives Störfeld oder die dortige Flottenkontrolle ist zu gut getarnt (Chance: ".$chance.")!");
										}
									}
									else
									{
										error_msg("Das Ziel ist zu weit entfernt (".ceil($dist)." AE, momentan sind ".CRYPTO_RANGE_PER_LEVEL*$cryptoCenterLevel." möglich, ".CRYPTO_RANGE_PER_LEVEL." pro Gebäudestufe)!");
									}									
								}
								else
								{
									error_msg("Am gewählten Ziel existiert kein Planet!");
								}
							}
							else
							{
								error_msg("Zuwenig Allianzrohstoffe ".RES_FUEL.", ".nf(CRYPTO_FUEL_COSTS_PER_SCAN)." benötigt, ".nf($cu->alliance->resFuel())." vorhanden!");
							}
						}
						else
						{
							error_msg("Zuwenig ".RES_FUEL.", ".nf(CRYPTO_FUEL_COSTS_PER_SCAN)." benötigt, ".nf($cp->resFuel)." vorhanden!");
						}
					}
					else
					{
						error_msg("Ungültige Koordinaten!");
					}
				}
				else
					error_msg("Du besitzt nicht die notwendigen Rechte!");
			}
			
			
			tableStart("Kryptocenter-Infos");
			echo "<tr><th>Aktuelle Reichweite:</th>
					<td>".(CRYPTO_RANGE_PER_LEVEL*$cryptoCenterLevel)." AE ~".floor(CRYPTO_RANGE_PER_LEVEL*$cryptoCenterLevel/$cfg->value('cell_length'))." Systeme (+".CRYPTO_RANGE_PER_LEVEL." pro Stufe) </td></tr>";
			echo "<tr><th>Kosten pro Scan:</th>
					<td>".nf(CRYPTO_FUEL_COSTS_PER_SCAN)." ".RES_FUEL." und ".nf(CRYPTO_FUEL_COSTS_PER_SCAN)." ".RES_FUEL." Allianzrohstoffe</td></tr>";
			echo "<tr><th>Abklingzeit:</th>
					<td>".tf($cooldown)." (-".tf($cfg->param1("cryptocenter"))." pro Stufe, minimal ".tf($cfg->param2("cryptocenter")).")</td></tr>";
			echo "<tr><th>Status:</th>
					<td>".$status_text."</td></tr>";
			tableEnd();
			
			if (!$cd_enabled)
			{				
				if (isset($_GET['target']) && $_GET['target']>0)
				{
					$ent = Entity::createFactoryById($_GET['target']);
					$coords = $ent->coordsArray();
				}
				elseif(isset($_POST['scan']))
				{
					$coords[0] = $sx;
					$coords[1] = $sy;
					$coords[2] = $cx;
					$coords[3] = $cy;
					$coords[4] = $pp;
				}
				else
				{
					$coords[0] = $cp->sx;
					$coords[1] = $cp->sy;
					$coords[2] = $cp->cx;
					$coords[3] = $cp->cy;
					$coords[4] = $cp->pos;
				}  		
				echo '<form action="?page='.$page.'" method="post">';		
				checker_init();
				iBoxStart("Ziel für Flottenanalyse wahlen:");
				
				//
				// Bookmarks laden
				//
				
				$bm = new BookmarkManager($cu->id);
				
				echo 'Koordinaten eingeben: 
						<input type="text" name="sx" id="sx" value="'.$coords[0].'" size="2" maxlength="2" /> / 
						<input type="text" name="sy" id="sy" value="'.$coords[1].'" size="2" maxlength="2" /> :
						<input type="text" name="cx" id="cx" value="'.$coords[2].'" size="2" maxlength="2" /> /
						<input type="text" name="cy" id="cy" value="'.$coords[3].'" size="2" maxlength="2" /> :
						<input type="text" name="p" id="p" value="'.$coords[4].'" size="2" maxlength="2" /><br/><br/>';
				
				// Bookmarkliste anzeigen
				echo "<i>oder</i> Favorit wählen: ".$bm->drawSelector("bookmarkselect","applyBookmark();")."<br/><br/>
						<input type=\"checkbox\" name=\"scan_to_notes\" value=\"1\" checked=\"checked\" /> Bericht zu meinem Notizblock hinzufügen";					
				echo $bm->drawSelectorJavaScript("bookmarkselect","applyBookmark");
				iBoxEnd();
				
				if ($cp->resFuel >= CRYPTO_FUEL_COSTS_PER_SCAN)
				{
					echo '<input type="submit" name="scan" value="Analyse für '.nf(CRYPTO_FUEL_COSTS_PER_SCAN).' '.RES_FUEL.' starten" />';
				}
				else
				{
					echo "Zuwenig Rohstoffe für eine Analyse vorhanden, ".nf(CRYPTO_FUEL_COSTS_PER_SCAN)." ".RES_FUEL." benötigt, ".nf($cp->resFuel)." vorhanden!";
				}
				echo '</form>';
			}
			else
			{
				echo "<b>Diese Funktion wurde vor kurzem benutzt! <br/>
					Du musst bis ".df($cu->alliance->getCryptoCooldown())." warten, um die Funktion wieder zu benutzen!</b>";
				
				countDown("cdcd",$cu->alliance->getCryptoCooldown());
			}
		}
		else
		{
			// Titel
			echo "<h1>Kryptocenter des Planeten ".$cp->name."</h1>";		
			$cp->resBox($cu->properties->smallResBox);

			error_msg("Das Kryptocenter wurde noch nicht gebaut!");
		}


	  /**
	  *
	  * Abschnitt mit Crypto als Usergebäude
	  *
	  **/
	  
	  /*// Prüfen ob Gebäude gebaut ist
	  if ($cryptoCenterLevel > 0)
	  {
			// Titel
			echo "<h1>Kryptocenter (Stufe ".$cryptoCenterLevel.") des Planeten ".$cp->name."</h1>";		
			$cp->resBox($cu->properties->smallResBox);
	
			// Todo: This is useless at the moment
			if (true) //$cp->prodPower >= $werft_arr['building_power_req'])
			{
				

				// Prüfen ob dieses Gebäude deaktiviert wurde
				if ($dt = $bl->getDeactivated(BUILD_CRYPTO_ID))
		 		{
					iBoxStart("Geb&auml;ude nicht bereit");
					echo "Dieses Gebäude ist noch bis <span id=\"decd\">".df($dt)."</span> deaktiviert.";
					iBoxEnd();
				}
				else
				{
					// Calculate cooldown
					$cooldown = max($cfg->param2("cryptocenter"),$cfg->value("cryptocenter") - ($cfg->param1("cryptocenter")*($cryptoCenterLevel-1)));
					if ($cd = $bl->getCooldown(BUILD_CRYPTO_ID))
					{
						$status_text = "Bereit in <span id=\"cdcd\">".tf($bl->getCooldown(BUILD_CRYPTO_ID)-time()."</span>");
						$cd_enabled=true;
					}
					else
					{
						$status_text = "Bereit";
						$cd_enabled=false;
					}		
	$cd_enabled = false;
	
					// Scan
					if (isset($_POST['scan']) && checker_verify() && !$cd_enabled)
					{
						
	
	
						$sx = intval($_POST['sx']);
						$sy = intval($_POST['sy']);
						$cx = intval($_POST['cx']);
						$cy = intval($_POST['cy']);
						$pp = intval($_POST['p']);
						if ($sx>0 && $sy>0 && $cx>0 && $cy>0 && $pp>0)
						{
							if ($cp->resFuel >= CRYPTO_FUEL_COSTS_PER_SCAN)
							{
								$target = Entity::createFactoryByCoords($sx,$sy,$cx,$cy,$pp);
								if (true)
								{
									$dist = $cp->distance($target);
									if ($dist <= CRYPTO_RANGE_PER_LEVEL*$cryptoCenterLevel)
									{							
	                  
	                  // Load oponent's jamming device count
	                  $jres = dbquery("
	                  SELECT
	                    deflist_count
	                   FROM
	                    deflist
	                   INNER JOIN
	                    defense
	                    ON deflist_def_id=def_id
	                    AND deflist_count>0
	                    AND def_jam=1
	                    AND deflist_entity_id=".$target->id().";                  
	                  ");
	                  $op_jam = 0;
	                  if (mysql_num_rows($jres)>0)
	                  {
	                    $jarr=mysql_fetch_row($jres);
	                    $op_jam = $jarr[0];
	                  }
	                  
	                  // Load oponents computer and stealth technologies  
	                  $tres = dbquery("
	                  SELECT
	                    techlist_current_level
	                  FROM 
	                    techlist
	                  WHERE
	                    techlist_tech_id=".TARN_TECH_ID."
	                    AND techlist_user_id=".$target->ownerId()."
	                  ");
	                  $op_stealth = 0;
	                  if (mysql_num_rows($tres)>0)
	                  {
	                    $jarr=mysql_fetch_row($tres);
	                    $op_stealth = $jarr[0];
	                  }
	                  $tres = dbquery("
	                  SELECT
	                    techlist_current_level
	                  FROM 
	                    techlist
	                  WHERE
	                    techlist_tech_id=".COMPUTER_TECH_ID."
	                    AND techlist_user_id=".$target->ownerId()."
	                  ");
	                  $op_computer = 0;
	                  if (mysql_num_rows($tres)>0)
	                  {
	                    $jarr=mysql_fetch_row($tres);
	                    $op_computer = $jarr[0];
	                  }
	                  
	                  // Load own computer and spy technologies
	                   $tres = dbquery("
	                  SELECT
	                    techlist_current_level
	                  FROM 
	                    techlist
	                  WHERE
	                    techlist_tech_id=".SPY_TECH_ID."
	                    AND techlist_user_id=".$cu->id."
	                  ");
	                  $self_spy = 0;
	                  if (mysql_num_rows($tres)>0)
	                  {
	                    $jarr=mysql_fetch_row($tres);
	                    $self_spy = $jarr[0];
	                  }
	                  $tres = dbquery("
	                  SELECT
	                    techlist_current_level
	                  FROM 
	                    techlist
	                  WHERE
	                    techlist_tech_id=".COMPUTER_TECH_ID."
	                    AND techlist_user_id=".$cu->id."
	                  ");
	                  $self_computer = 0;
	                  if (mysql_num_rows($tres)>0)
	                  {
	                    $jarr=mysql_fetch_row($tres);
	                    $self_computer = $jarr[0];
	                  }                 
	                         
	                  // Calculate success chance
	                  $chance = ($cryptoCenterLevel-$op_jam) + (0.3*($self_spy - $op_stealth)) + mt_rand(0,2)-1;
	                  
	                  // Do the scan if chance >= 0
	                  if ($chance >= 0)
	                  {         
	                    $decryptlevel = ($cryptoCenterLevel-$op_jam) + (0.75*($self_spy + $self_computer - $op_stealth - $op_computer)) + mt_rand(0,2)-1;  
	                    
	                    // Decrypt level
	                    // < 0 Only show that there are some fleets
	                    // 0 <= 10 Show that there are x fleets
	                    // 10 <= 15 Show that there are x fleets from y belonging to z, show hour
	                    // 15 <= 20 Also show ship types, show mninutes with +- 15 mins
	                    // 20 <= 25 Also show count of ships and time in minutes
	                    // 25 <= 30 Also show count of every ship and exact time
	                    // >30 Show action
	                    
	                    
										  $out="[b]Flottenscan vom Planeten ".$target->name()."[/b] (".$sx."/".$sy." : ".$cx."/".$cy." : ".$pp.")\n\n";
	
										  $out.="[b]Eintreffende Flotten[/b]\n\n";
										  $fres = dbquery("
											SELECT
												id
											FROM
												fleet
											WHERE
												entity_to=".$target->id()."
										  ");
										  if (mysql_num_rows($fres)>0)
										  {
	                      if ($decryptlevel<0)
	                      {
	                        $out.="Es sind Flotten unterwegs\n"; 
	                      }
	                      else if ($decryptlevel<10)
	                      {
	                        $out.="Es sind ".mysql_num_rows($fres)." Flotten unterwegs\n"; 
	                      }
	                      else                      
	                      {
											    while ($farr=mysql_fetch_row($fres))
											    {
											    	$fd = new Fleet($farr[0]);
														$source = $fd->getSource();
											    	$owner = new User($fd->ownerId());
											    	
												    $out.='[b]Herkunft:[/b] '.$source.', [b]Besitzer:[/b] '.$owner;
												    $out.= "\n[b]Ankunft:[/b] ";
	                          
	                          if ($decryptlevel<=15)
	                          {
	                            $out.="Zwischen ".date("d.m.Y H:i",$fd->landTime() - (30*60))." und ".date("d.m.Y H:i",$fd->landTime()+(30*60))." Uhr";
	                          }
	                          elseif ($decryptlevel<=20)
	                          {
	                            $out.="Zwischen ".date("d.m.Y H:i",$fd->landTime()-(7*60))." und ".date("d.m.Y H:i",$fd->landTime()+(7*60))." Uhr";                            
	                          }                          
	                          elseif ($decryptlevel<=25)
	                          {
	                            $out.=date("d.m.Y H:i",$fd->landTime())." Uhr";                            
	                          }   
	                          else
	                          {
	                            $out.=date("d.m.Y H:i:s",$fd->landTime())." Uhr";                            
	                          }                            
	                          if ($decryptlevel>30)
	                          {                        
	                            $out.=", [b]Aktion:[/b] ".$fd->getAction()."\n";
	                          }
	                          else
	                            $out.="\n";
	                          
	                          if ($decryptlevel>=15)
	                          {                                                
												      $sres = dbquery("
												      SELECT
													      ship_name,
													      fs_ship_cnt
												      FROM
													      fleet_ships
												      INNER JOIN
													      ships
													      ON ship_id=fs_ship_id
													      AND fs_fleet_id=".$farr['fleet_id']."
												      ;");
												      if (mysql_num_rows($sres)>0)
												      {
	                              $cntr;
													      while ($sarr=mysql_fetch_array($sres))
													      {
	                                if ($decryptlevel <=25 )
	                                {                                    
														        $out.="".$sarr['ship_name']."\n";
	                                }
	                                else
	                                {
	                                  $out.=$sarr['fs_ship_cnt']." ".$sarr['ship_name']."\n";
	                                }
	                                $cntr+=$sarr['fs_ship_cnt'];
													      }
	                              if ($decryptlevel >20)
	                              {
	                                $out.=$cntr." Schiffe total\n"; 
	                              }
												      }	
	                          }
												    $out.="\n";									
											    }
	                      }
										  }								
										  else
										  {
											  $out.="Keine eintreffenden Flotten gefunden!\n\n";
										  }
										  
										  $out.="[b]Wegfliegende Flotten[/b]\n\n";
										  $fres = dbquery("
											SELECT
												id
											FROM
												fleet
											WHERE
												entity_from=".$target->id()."
										  ");
										  if (mysql_num_rows($fres)>0)
										  {
	                      if ($decryptlevel<0)
	                      {
	                        $out.="Es sind Flotten unterwegs\n"; 
	                      }
	                      else if ($decryptlevel<10)
	                      {
	                        $out.="Es sind ".mysql_num_rows($fres)." Flotten unterwegs\n"; 
	                      }
	                      else                      
	                      {
	                        while ($farr=mysql_fetch_row($fres))
	                        {
											    	$fd = new Fleet($farr[0]);
														$source = $fd->getTarget();
											    	$owner = new User($fd->ownerId());
                        	
	                          $out.='[b]Ziel:[/b] '.$source.', [b]Besitzer:[/b] '.$owner;
	                          $out.= "\n[b]Ankunft:[/b] ";
	                          
	                          if ($decryptlevel<=15)
	                          {
	                            $out.="Zwischen ".date("d.m.Y H:i",$fd->landTime()-(30*60))." und ".date("d.m.Y H:i",$fd->landTime()+(30*60))." Uhr";
	                          }
	                          elseif ($decryptlevel<=20)
	                          {
	                            $out.="Zwischen ".date("d.m.Y H:i",$fd->landTime()-(7*60))." und ".date("d.m.Y H:i",$fd->landTime()+(7*60))." Uhr";                            
	                          }                          
	                          elseif ($decryptlevel<=25)
	                          {
	                            $out.=date("d.m.Y H:i",$fd->landTime())." Uhr";                            
	                          }   
	                          else
	                          {
	                            $out.=date("d.m.Y H:i:s",$fd->landTime())." Uhr";                            
	                          }                            
	                          if ($decryptlevel>30)
	                          {                        
	                            $out.=", [b]Aktion:[/b] ".$fd->getAction()."\n";
	                          }
	                          else
	                            $out.="\n";
	                          
	                          if ($decryptlevel>=15)
	                          {                                                

	                              $cntr;
	                              foreach ($fd->getShips() as $sid => $sdat)
	                              {
																	$sids = $fd->getShipIds();
																	$cnt = $sids[$sid];
	                                if ($decryptlevel <=25 )
	                                {                                    
	                                  $out.="".$sdat."\n";
	                                }
	                                else
	                                {
	                                  $out.=$cnt." ".$sdat."\n";
	                                }
	                                $cntr+=$cnt;
	                              }
	                              if ($decryptlevel >20)
	                              {
	                                $out.=$cntr." Schiffe total\n"; 
	                              }
 
	                          }
	                          $out.="\n";                  
	                        }
	                      }                      
										  }								
										  else
										  {
											  $out.='Keine abfliegenden Flotten gefunden!';
										  }
										  
	                    $out.="\n\nEntschlüsselchance: $decryptlevel";                    
	                    
	                    // Subtract resources           
	                    $cp->changeRes(0,0,0,-CRYPTO_FUEL_COSTS_PER_SCAN,0);
	
	                    // Inform oponent
	                    if ($target->ownerId()>0)
	                    {                  
	                      send_msg($target->ownerId(),SHIP_SPY_MSG_CAT_ID,"Funkstörung","Eure Flottenkontrolle hat soeben eine kurzzeitige Störung des Kommunikationsnetzes festgestellt. Es kann sein das fremde Hacker in das Netz eingedrungen sind und Flottendaten geklaut haben.");
	                    }
	                                        
	                    // Display result
										  iBoxStart("Ergebnis der Analyse");
										  echo text2html($out);
										  iBoxEnd();
										                   
											// Add note to user's notepad if selected
											if (isset($_POST['scan_to_notes']))
											{
												$np = new Notepad($cu->id);
												$np->add("Flottenscan: ".$target,$out);
											}										                   
										                   
	                    // Mail result (deprecated)
										  //send_msg($cu->id,SHIP_MISC_MSG_CAT_ID,"Kryptocenter-Bericht",$out);
										  
	                    
										  // Set cooldown
										  $cd = time()+$cooldown;
										  dbquery("
										  UPDATE
											  buildlist
										  SET
											  buildlist_cooldown=".$cd."
										  WHERE
									  	  buildlist_entity_id='".$cp->id()."'
									  	  AND buildlist_building_id='".BUILD_CRYPTO_ID."'
									  	  AND buildlist_user_id='".$cu->id."'");
									    
									    $bl->setCooldown(BUILD_CRYPTO_ID,$cd);
									    
										  if ($cd = $bl->getCooldown(BUILD_CRYPTO_ID))
										  {
											  $status_text = "Bereit in ".tf($bl->getCooldown(BUILD_CRYPTO_ID)-time());
											  $cd_enabled=true;
										  }
										  else
										  {
											  $status_text = "Bereit";
											  $cd_enabled=false;
										  }											
										     
	                  }
	                  else
	                  {
	                    if ($op_jam>0 && $target->ownerId()>0)
	                    {                  
	                      send_msg($target->ownerId(),SHIP_SPY_MSG_CAT_ID,"Störsender erfolgreich","Eure Techniker haben festgestellt, dass von einem anderen Planeten eine Entschlüsselung eures Funkverkehrs versucht wurde. Daraufhin haben eure Störsender die Funknetze mit falschen Werten überlastet, so dass die gegnerische Analyse fehlschlug!");
	                    }                    
	                    echo "Die Analyse schlug leider fehl! Eure Empfangsgeräte haben zu viel Rauschen aufgenommen; anscheinend hat der Zielplanet ein aktives Störfeld oder die dortige Flottenkontrolle ist zu gut getarnt (Chance: ".$chance.")!<br/><br/>";                    
	                  }                  
									}
									else
									{
										echo "Das Ziel ist zu weit entfernt (".ceil($ssae)." AE, momentan sind ".CRYPTO_RANGE_PER_LEVEL*$cryptoCenterLevel." möglich, ".CRYPTO_RANGE_PER_LEVEL." pro Gebäudestufe)!<br/><br/>";
									}									
								}
								else
								{
									echo "Am gewählten Ziel existiert kein Planet!<br/><br/>";
								}						
							}
							else
							{
								echo "Zuwenig ".RES_FUEL.", ".nf(CRYPTO_FUEL_COSTS_PER_SCAN)." benötigt, ".nf($cp->resFuel)." vorhanden!<br/><br/>";
							}						
						}
						else
						{
							echo "Ungültige Koordinaten!<br/><br/>";
						}
					}			
						
		
					tableStart("Kryptocenter-Infos");
					echo "<tr><th class=\"tbltitle\">Aktuelle Reichweite:</th>
						<td class=\"tbldata\">".(CRYPTO_RANGE_PER_LEVEL*$cryptoCenterLevel)." AE ~".floor(CRYPTO_RANGE_PER_LEVEL*$cryptoCenterLevel/$cfg->value('cell_length'))." Systeme (+".CRYPTO_RANGE_PER_LEVEL." pro Stufe) </td></tr>";
					echo "<tr><th class=\"tbltitle\">Kosten pro Scan:</th>
						<td class=\"tbldata\">".nf(CRYPTO_FUEL_COSTS_PER_SCAN)." ".RES_FUEL."</td></tr>";
					echo "<tr><th class=\"tbltitle\">Abklingzeit:</th>
						<td class=\"tbldata\">".tf($cooldown)." (-".tf($cfg->param1("cryptocenter"))." pro Stufe, minimal ".tf($cfg->param2("cryptocenter")).")</td></tr>";
					echo "<tr><th class=\"tbltitle\">Status:</th>
						<td class=\"tbldata\">".$status_text."</td></tr>";
					tableEnd();
					
					if (!$cd_enabled)
					{				
						if (isset($_GET['target']) && $_GET['target']>0)
						{
							$ent = Entity::createFactoryById($_GET['target']);
							$coords = $ent->coordsArray();
						}
						elseif(isset($_POST['scan']))
						{
							$coords[0] = $sx;
							$coords[1] = $sy;
							$coords[2] = $cx;
							$coords[3] = $cy;
							$coords[4] = $pp;
						}				
						else
						{
							$coords[0] = $cp->sx;
							$coords[1] = $cp->sy;
							$coords[2] = $cp->cx;
							$coords[3] = $cp->cy;
							$coords[4] = $cp->pos;
						}  		
						echo '<form action="?page='.$page.'" method="post">';		
						checker_init();
						iBoxStart("Ziel für Flottenanalyse wahlen:");
		
						//
						// Bookmarks laden
						//
	
						$bm = new BookmarkManager($cu->id);
						
						echo 'Koordinaten eingeben: 
							<input type="text" name="sx" id="sx" value="'.$coords[0].'" size="2" maxlength="2" /> / 
							<input type="text" name="sy" id="sy" value="'.$coords[1].'" size="2" maxlength="2" /> :
							<input type="text" name="cx" id="cx" value="'.$coords[2].'" size="2" maxlength="2" /> /
							<input type="text" name="cy" id="cy" value="'.$coords[3].'" size="2" maxlength="2" /> :
							<input type="text" name="p" id="p" value="'.$coords[4].'" size="2" maxlength="2" /><br/><br/>';
						
						// Bookmarkliste anzeigen
						echo "<i>oder</i> Favorit wählen: ".$bm->drawSelector("bookmarkselect","applyBookmark();")."<br/><br/>
						<input type=\"checkbox\" name=\"scan_to_notes\" value=\"1\" checked=\"checked\" /> Bericht zu meinem Notizblock hinzufügen";					
						echo $bm->drawSelectorJavaScript("bookmarkselect","applyBookmark");
						iBoxEnd();
						if ($cp->resFuel >= CRYPTO_FUEL_COSTS_PER_SCAN)
						{
							echo '<input type="submit" name="scan" value="Analyse für '.nf(CRYPTO_FUEL_COSTS_PER_SCAN).' '.RES_FUEL.' starten" />';
						}
						else
						{
							echo "Zuwenig Rohstoffe für eine Analyse vorhanden, ".nf(CRYPTO_FUEL_COSTS_PER_SCAN)." ".RES_FUEL." benötigt, ".nf($cp->resFuel)." vorhanden!";
						}
						echo '</form>';				

					}
					else
					{
						echo "<b>Diese Funktion wurde vor kurzem benutzt! <br/>
						Du musst bis ".df($bl->getCooldown(BUILD_CRYPTO_ID))." warten, um die Funktion wieder zu benutzen!</b>";
						
						countDown("cdcd",$bl->getCooldown(BUILD_CRYPTO_ID));	
						
					}						
				}
			}
			else
			{
				//echo "Gebäude ist deaktiviert! Zu wenig Energie verfügbar, ".nf($werft_arr['building_power_req'])." muss produziert werden, ".nf($cp->prodPower)." wird produziert.";
			}
		}
		else
		{
			// Titel
			echo "<h1>Kryptocenter des Planeten ".$cp->name."</h1>";		
			$cp->resBox($cu->properties->smallResBox);

			echo "<h2>Fehler!</h2>Das Kryptocenter wurde noch nicht gebaut!<br>";
		}*/
  }
  else
  {
    // Titel
    echo "<h1>Kryptocenter des Planeten ".$cp->name."</h1>";    
    $cp->resBox($cu->properties->smallResBox);

    error_msg("Aufgrund eines intergalaktischen Moratoriums der Völkerföderation der Galaxie Andromeda 
    sind sämtliche elektronischen Spionagetätigkeiten zurzeit nicht erlaubt!");
  }  
  
?>