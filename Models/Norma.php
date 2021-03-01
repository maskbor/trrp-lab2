<?php

	class Norma {

		private $db;
		private $table;

		public function __construct($db)
		{
			$this->db = $db;
			$this->table = get_class($this);
			mysqli_query($this->db->link, "create table if not exists $this->table ( `id` INT NOT NULL AUTO_INCREMENT , `winter_highway` INT NOT NULL ,	`winter_city` INT NOT NULL , `summer_highway` INT NOT NULL , `summer_city` INT NOT NULL , PRIMARY KEY (`id`))");
		}

		public function findOrCreate($values)
		{
			$query = "SELECT id FROM $this->table where "
				." winter_highway = ".$values['winter_highway']
				." and winter_city = ".$values['winter_city']
				." and summer_highway = ".$values['summer_highway']
				." and summer_city = ".$values['summer_city'];
			$result = mysqli_fetch_assoc($this->makeQuery($query));
			if(is_null($result)){
				$query = "INSERT INTO $this->table ("
					."winter_highway, winter_city, summer_highway, summer_city"
					.") VALUES ("
					.$values['winter_highway'].", "
					.$values['winter_city'].", "
					.$values['summer_highway'].", "
					.$values['summer_city']." "
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