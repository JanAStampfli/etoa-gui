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
	//
	
	$umod = false;

		//
		// Urlaubsmodus einschalten
		//
		
		if (isset($_POST['hmod_on']) && checker_verify())
		{
			if (true || $cu->lastInvasion < time() - $cfg->get("user_umod_min_length")*24*3600)
			{
				$cres = dbquery("SELECT id FROM fleet WHERE user_id='".$cu->id."';");
				$carr = mysql_fetch_row($cres);
				if ($carr[0]==0)
				{
					$pres = dbquery("SELECT 
										f.id 
									FROM 
										fleet as f
									INNER JOIN
										planets as p
									ON f.entity_to=p.id
									AND p.planet_user_id='".$cu->id."'
									AND (f.user_id='".$cu->id."' OR (status=0 AND action NOT IN ('collectdebris','explore','flight','createdebris')));");
					$parr = mysql_fetch_row($pres);
					if ($parr[0]==0)
					{
						$sres = dbquery("SELECT 
											queue_id,
											queue_starttime 
										FROM 
											ship_queue 
										WHERE 
											queue_user_id='".$cu->id."';");
						while ($sarr=mysql_fetch_row($sres))
						{
							if ($sarr[1]>time())
							{
								dbquery("UPDATE 
											ship_queue 
										SET 
											queue_build_type=1
										WHERE 
											queue_user_id='".$cu->id."';");
							}
							else
							{
								dbquery("UPDATE 
											ship_queue 
										SET 
											queue_build_type=1
										WHERE 
											queue_user_id='".$cu->id."';");
							}
						}
						$sres = dbquery("SELECT 
											queue_id,
											queue_starttime 
										FROM 
											def_queue 
										WHERE 
											queue_user_id='".$cu->id."';");
						while ($sarr=mysql_fetch_row($sres))
						{
							if ($sarr[1]>time())
							{
								dbquery("UPDATE 
											def_queue 
										SET 
											queue_build_type=1
										WHERE 
											queue_user_id='".$cu->id."';");
							}
							else
							{
								dbquery("UPDATE 
											def_queue 
										SET 
											queue_build_type=1
										WHERE 
											queue_user_id='".$cu->id."';");
							}
						}
	
						dbquery("UPDATE 
									buildlist 
								SET 
									buildlist_build_type = 1
								WHERE 
									buildlist_user_id='".$cu->id."' 
									AND buildlist_build_start_time>0;");
						dbquery("UPDATE 
									techlist 
								SET 
									techlist_build_type=1
								WHERE 
									techlist_user_id='".$cu->id."' 
									AND techlist_build_start_time>0;");
					
						$hfrom=time();
						
						$hto = $hfrom+($cfg->get("user_umod_min_length")*86400);

						dbquery ("
							UPDATE
								planets
							SET
								planet_last_updated='0',
								planet_prod_metal=0,
								planet_prod_crystal=0,
								planet_prod_plastic=0,
								planet_prod_fuel=0,
								planet_prod_food=0
							WHERE
								planet_user_id='".$cu->id."';");
										
						$cu->hmode_from = $hfrom;
						$cu->hmode_to = $hto;
						success_msg("Du bist nun im Urlaubsmodus bis [b]".df($hto)."[/b].");
						$cu->addToUserLog("settings","{nick} ist nun im Urlaub.",1);
						$umod = true;
					}
					else
					{
						error_msg("Es sind noch Flotten unterwegs!");
					}
				}
				else
				{
					error_msg("Es sind noch Flotten unterwegs!");
				}
			}
			else
			{
				error_msg("Du musst mindestens ".$cfg->get("user_umod_min_length")." Tage nach deiner letzten Invasion warten, bis du in den Urlaubsmodus gehen kannst!");
			}
				
		}
	
		//
		// Urlaubsmodus aufheben
		//
	
		if (isset($_POST['hmod_off']) && checker_verify())
		{
			if ($cu->hmode_from > 0 && $cu->hmode_from < time() && $cu->hmode_to < time())
			{
				$hmodTime = time() - $cu->hmode_from;
				$bres = dbquery("
								SELECT
									buildlist_id,
									buildlist_build_end_time,
									buildlist_build_start_time,
									buildlist_build_type
								FROM
									buildlist
								WHERE
									buildlist_build_start_time>0
									AND buildlist_build_type>0
									AND buildlist_user_id=".$cu->id.";");
							
				while ($barr=mysql_fetch_row($bres))
				{
					$start = $barr[2]+$hmodTime;
					$end = $barr[1]+$hmodTime;
					$status = $barr[3] + 2;
					dbquery("UPDATE
								buildlist
							SET
								buildlist_build_type='".$status."',
								buildlist_build_start_time='".$start."',
								buildlist_build_end_time='".$end."'
							WHERE
								buildlist_id='".$barr[0]."';");
				} 
				
				$tres = dbquery("
								SELECT
									techlist_id,
									techlist_build_end_time,
									techlist_build_start_time,
									techlist_build_type
								FROM
									techlist
								WHERE
									techlist_build_start_time>0
									AND techlist_build_type>0
									AND techlist_user_id=".$cu->id.";");
									
				while ($tarr=mysql_fetch_row($tres))
				{
					$status = $tarr[3] + 2;
					$start = $tarr[2]+$hmodTime;
					$end = $tarr[1]+$hmodTime;
					dbquery("UPDATE
								techlist
							SET
								techlist_build_type='".$status."',
								techlist_build_start_time='".$start."',
								techlist_build_end_time='".$end."'
							WHERE
								techlist_id=".$tarr[0].";");
				}
				
				$sres = dbquery("SELECT 
									queue_id,
									queue_endtime,
									queue_starttime
								 FROM 
								 	ship_queue 
								WHERE 
									queue_user_id='".$cu->id."'
								ORDER BY 
									queue_starttime ASC;");
				$time = time();
				while ($sarr=mysql_fetch_row($sres))
				{
					$start = $sarr[2]+$hmodTime;
					$end = $sarr[1]+$hmodTime;
					dbquery("UPDATE 
								ship_queue
							SET
								queue_build_type=0,
								queue_starttime='".$start."',
								queue_endtime='".$end."'
							WHERE
								queue_id=".$sarr[0].";");
				}
				
			$dres = dbquery("SELECT 
									queue_id,
									queue_endtime,
									queue_starttime
								 FROM 
								 	def_queue 
								WHERE 
									queue_user_id='".$cu->id."'
								ORDER BY 
									queue_starttime ASC;");
				$time = time();
				while ($darr=mysql_fetch_row($dres))
				{
					$start = $darr[2]+$hmodTime;
					$end = $darr[1]+$hmodTime;
					dbquery("UPDATE 
								def_queue
							SET
								queue_build_type=0,
							queue_starttime='".$start."',
								queue_endtime='".$end."'
							WHERE
								queue_id=".$darr[0].";");
				}
					
        // Prolong specialist contract
        dbquery("
        UPDATE
          users
        SET
          user_specialist_time=user_specialist_time+".$hmodTime."
        WHERE
          user_specialist_id > 0
          AND user_id=".$cu->id."
        ;");          
          
				dbquery("UPDATE users SET user_hmode_from=0,user_hmode_to=0,user_logouttime='".time()."' WHERE user_id='".$cu->id."';");
				dbquery ("UPDATE planets SET planet_last_updated=".time()." WHERE planet_user_id='".$cu->id."';");
				
				foreach ($planets as $pid) {
					BackendMessage::updatePlanet($pid);
				}
				
				success_msg("Urlaubsmodus aufgehoben! Denke daran, auf allen deinen Planeten die Produktion zu überprüfen!");
				$cu->addToUserLog("settings","{nick} ist nun aus dem Urlaub zurück.",1);
				
				echo '<input type="button" value="Zur Übersicht" onclick="document.location=\'?page=overview\'" />';
			}
			else
			{
				error_msg("Urlaubsmodus kann nicht aufgehoben werden!");
			}
		}
	
		//
		// Löschbestätigung
		//
		elseif (isset($_POST['remove']) && checker_verify())
		{
				echo "<form action=\"?page=$page&amp;mode=misc\" method=\"post\">";
	    	iBoxStart("Löschung bestätigen");
				echo "Soll dein Account wirklich zur Löschung vorgeschlagen werden?<br/><br/>";
				echo "<b>Passwort eingeben:</b> <input type=\"password\" name=\"remove_password\" value=\"\" />";
				iBoxEnd();
				echo "<input type=\"button\" value=\"Abbrechen\" onclick=\"document.location='?page=$page&mode=misc'\" /> 
				<input type=\"submit\" name=\"remove_submit\" value=\"Account l&ouml;schen\" />";
				echo "</form>";
		}

		//
		// User löschen
		//	
		elseif (isset($_POST['remove_submit']))
		{
			$pres = dbquery("
			SELECT 
				user_password
			FROM 
				users 
			WHERE 
				user_id=".$cu->id."
			;");
			$parr = mysql_fetch_row($pres);
			if (validatePasswort($_POST['remove_password'], $parr[0]))
			{
				$t = time() + ($conf['user_delete_days']['v']*3600*24);
				dbquery("
				UPDATE
					users
				SET
					user_deleted=".$t."
				WHERE
					user_id=".$cu->id."
				;");
				
				$s=Null;
				session_destroy();
				success_msg("Deine Daten werden am ".df($t)." Uhr von unserem System gelöscht! Wir w&uuml;nschen weiterhin viel Erfolg im Netz!");
				$cu->addToUserLog("settings","{nick} hat seinen Account zur Löschung freigegeben.",1);
				echo '<input type="button" value="Zur Startseite" onclick="document.location=\''.getLoginUrl().'\'" />';
			}
			else
			{
				error_msg("Falsches Passwort!");
				echo '<input type="button" value="Weiter" onclick="document.location=\'?page=userconfig&mode=misc\'" />';
			}
		}

		//
		// Löschantrag aufheben
		//
		elseif (isset($_POST['remove_cancel']) && checker_verify())
		{
			dbquery("
			UPDATE
				users
			SET
				user_deleted=0
			WHERE
				user_id=".$cu->id."
			;");
			success_msg("Löschantrag aufgehoben!");
			$cu->addToUserLog("settings","{nick} hat seine Accountlöschung aufgehoben.",1);
			echo '<input type="button" value="Weiter" onclick="document.location=\'?page=userconfig&mode=misc\'" />';
		}

		//
		// Auswahl
		//
		else
		{
			echo "<form action=\"?page=$page&amp;mode=misc\" method=\"post\">";		
	    	checker_init();
	    	tableStart("Sonstige Accountoptionen");
			
	    	// Urlaubsmodus
	    	echo "<tr><th style=\"width:150px;\">Urlaubsmodus</th>
	    	<td>Im Urlaubsmodus kannst du nicht angegriffen werden, aber deine Produktion steht auch still. </br> Dauer: mindestens ".MIN_UMOD_TIME." Tage, nach ".MAX_UMOD_TIME." Tagen Urlaubsmodus wird der Account inaktiv und kann wieder angegriffen werden.</td>
	    	<td>";
			
	    	if ($cu->hmode_from>0 && $cu->hmode_from<time() && $cu->hmode_to<time())
	    	{
	    		echo "<input type=\"submit\" style=\"color:#0f0\" name=\"hmod_off\" value=\"Urlaubsmodus deaktivieren\" />";
	    	}
	    	elseif ($cu->hmode_from>0 && $cu->hmode_from<time() && $cu->hmode_to>=time() || $umod)
	    	{
	    	  echo "<span style=\"color:#f90\">Urlaubsmodus ist aktiv bis mindestens <b>".df($cu->hmode_to)."</b>!</span>";
	    	}
	    	else
	    	{
	    	  echo "<input type=\"submit\" value=\"Urlaubsmodus aktivieren\" name=\"hmod_on\" onclick=\"return confirm('Soll der Urlaubsmodus wirklich aktiviert werden?')\" />";
	    	} 
	    	echo "</td></tr>";
	
			// Account löschen
	    	echo "<tr><th>Account l&ouml;schen</th>
	    	<td>Hier kannst du deinen Account mitsamt aller Daten löschen. Der Account wird erst nach ".$conf['user_delete_days']['v']." Tagen gelöscht.</td>
	    	<td>";
	    	if ($cu->deleted>0)
	    	{
	    		echo "<input type=\"submit\" name=\"remove_cancel\" value=\"Löschantrag aufheben\"  style=\"color:#0f0\" />";
	    	}
	    	else
	    	{
	    		echo "<input type=\"submit\" name=\"remove\" value=\"Account l&ouml;schen\" />";
	    	}
	    	echo "</td></tr>";
	    	
			tableEnd();
			echo "</form>";
		}
	
?>