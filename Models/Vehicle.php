<?php

	class Vehicle {

		private $db;
		private $table;

		public function __construct($db)
		{
			$this->db = $db;
			$this->table = get_class($this);
			mysqli_query($this->db->link, "create table if not exists $this->table ( `id` INT NOT NULL AUTO_INCREMENT , `name` VARCHAR(1024) NOT NULL, `regNumber` VARCHAR(1024) NOT NULL , `fuel` INT NOT NULL , `odometer` INT NOT NULL , `id_responsible` INT NOT NULL, `id_norma` INT NOT NULL , PRIMARY KEY (`id`))");
		}

		
		public function findOrCreate($values)
		{
			$query = "SELECT id FROM $this->table where "
				." name like '".$values['name']."'"
				." and regNumber like '".$values['regNumber']."'"
				." and id_norma = ".$values['id_norma']
				." and fuel =  ".$values['fuel']
				." and odometer =  ".$values['odometer']
				." and id_responsible = ".$values['id_responsible'];
			$result = mysqli_fetch_assoc($this->makeQuery($query));
			if(is_null($result)){
				$query = "INSERT INTO $this->table ("
					."name, regNumber, id_norma, fuel, odometer, id_responsible"
					.") VALUES ("
					."'".$values['name']."', "
					."'".$values['regNumber']."', "
					.$values['id_norma'].", "
					.$values['fuel'].", "
					.$values['odometer'].", "
					.$values['id_responsible']." "
					.");";
				$result = $this->makeQuery($query);
				return mysqli_insert_id($this->db->link);
			}
			return $result['id'];
		}

		private function makeQuery($query)
		{
			return mysqli_query($this->db->link, $query);
		}
	}
?>