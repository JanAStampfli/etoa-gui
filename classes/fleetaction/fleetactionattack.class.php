<?PHP

	class FleetActionAttack extends FleetAction
	{

		function FleetActionAttack()
		{
			$this->code = "attack";
			$this->name = "Angreifen";
			$this->desc = "Greift das Ziel an und klaut Rohstoffe";
			$this->longDesc = "Der Standard-Angriff auf ein bewohntes Ziel. Falls der Kampf gewonnen wird, wird (meistens) die Hälfte der Rohstoffe geklaut.";
			$this->visible = true;
			$this->exclusive = false;
			$this->attitude = 3;
			
			$this->allowPlayerEntities = true;
			$this->allowOwnEntities = false;
			$this->allowNpcEntities = false;
			$this->allowSourceEntity = false;
			$this->allowAllianceEntities = false;
		}

		function startAction() {} 
		function cancelAction() {}		
		function targetAction() {} 
		function returningAction() {}		
		
	}

?>