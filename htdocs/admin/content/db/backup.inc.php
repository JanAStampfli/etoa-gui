<?PHP
		echo "<h2>Backups</h2>";
	
		// Backup erstellen
		if (isset($_POST['create']))
		{
			try
			{
				$tr = new PeriodicTaskRunner();
				ok_msg($tr->runTask('CreateBackupTask'));
			}
			catch (Exception $e)
			{
				error_msg("Beim Ausf&uuml;hren des Backup-Befehls trat ein Fehler auf! ".$e->getMessage());
			}
		}

		// Backup wiederherstellen
		elseif (isset($_GET['action']) && $_GET['action']=="backuprestore" && $_GET['date']!="")
		{
			// Sicherungskopie anlegen
			try 
			{
				$tr = new PeriodicTaskRunner();
				$tr->runTask('CreateBackupTask');
				
				if (DBManager::getInstance()->restoreDB($_GET['date'])) {
					echo "Das Backup ".$_GET['date']." wurde wiederhergestellt und es wurde eine Sicherungskopie der vorherigen Daten angelegt!<br/><br/>";
				} else {
					cms_err_msg("Beim Ausf&uuml;hren des Restore-Befehls trat ein Fehler auf! $result");
				}
			}
			catch (Exception $e)
			{
				cms_err_msg("Beim Ausf&uuml;hren des Backup-Befehls trat ein Fehler auf! ".$e->getMessage());
			}
		}
		
		$frm = new Form("bustn","?page=$page&amp;sub=$sub");
		if (isset($_POST['submit_changes'])) //$frm->checkSubmit("submit_changes")
		{
			$cfg->set("backup_dir", $_POST['backup_dir']);
			$cfg->set("backup_retention_time", $_POST['backup_retention_time']);
			$cfg->set("backup_use_gzip", $_POST['backup_use_gzip']);
			ok_msg("Einstellungen gespeichert");
		}

		echo $frm->begin();
		echo "<fieldset><legend>Backup-Einstellungen</legend>";
		echo "Speicherpfad: <input type=\"text\" value=\"".$cfg->backup_dir."\" name=\"backup_dir\" size=\"50\" /><br/>
		Aufbewahrungsdauer: <input type=\"text\" value=\"".$cfg->get('backup_retention_time')."\" name=\"backup_retention_time\" size=\"2\" /> Tage &nbsp; &nbsp;
		GZIP benutzen: <input type=\"radio\" name=\"backup_use_gzip\" value=\"1\" ".($cfg->get('backup_use_gzip')=="1" ? ' checked="checked"' : '')."/> Ja  
		<input type=\"radio\" name=\"backup_use_gzip\" value=\"0\" ".($cfg->get('backup_use_gzip')=="0" ? ' checked="checked"' : '')."/> Nein<br/>";
		echo "<input type=\"submit\" value=\"Speichern\" name=\"submit_changes\"  />";
		echo "</fieldset>";
		echo $frm->end();

		echo "<p>Im Folgenden sind alle verfügbaren Backups aufgelistet. Backups werden durch ein Skript erstellt dass per Cronjob aufgerufen wird.</p>";

		echo "<form action=\"?page=$page&amp;sub=$sub\" method=\"post\">";
		echo "<p><input type=\"submit\" value=\"Neues Backup erstellen\" name=\"create\" /></p>
		 </form>";
		if (is_dir($cfg->backup_dir) && $d = opendir($cfg->backup_dir))
		{
			$cnt=0;
			echo "<table class=\"tb\" style=\"width:auto;\"><tr><th>Name</th><th>Grösse</th><th>Optionen</th></tr>";
			$bfiles = DBManager::getInstance()->getBackupImages(0);

			foreach ($bfiles as $f)
			{
				$sr = round(filesize($cfg->backup_dir."/".$f)/1024/1024,2);
				$date=substr($f,strpos($f,"-")+1,16);
				echo "<tr><td>".$f."</td>";
				echo "<td>".$sr." MB</td>";
				echo "<td>
					<a href=\"?page=$page&amp;sub=backup&amp;action=backuprestore&amp;date=$date\" onclick=\"return confirm('Soll die Datenbank mit den im Backup $date gespeicherten Daten &uuml;berschrieben werden?');\">Wiederherstellen</a> &nbsp; 
					<a href=\"".createDownloadLink($cfg->backup_dir."/".$f)."\">Download</a>
				</td></tr>";
				$cnt++;
			}
			if ($cnt==0)
			{
				echo "<tr><td colspan=\"3\"><i>Es sind noch keine Dateien vorhanden!</i></td></tr>";
			}

			echo "</table>";
			closedir($d);
		}
		else {
			cms_err_msg("Das Verzeichnis ".$cfg->backup_dir." wurde nicht gefunden!");
		}
?>
