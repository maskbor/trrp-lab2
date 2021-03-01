<?php

	class Region {

		private $db;
		private $table;

		public function __construct($db)
		{
			$this->db = $db;
			$this->table = get_class($this);
			mysqli_query($this->db->link, 
			"create table if not exists $this->table ( `id` INT NOT NULL AUTO_INCREMENT ,
				`name` VARCHAR(1024) NOT NULL,
				PRIMARY KEY (`id`))"
			);
		}

		public function findOrCreate($values)
		{
			$query = "SELECT id FROM $this->table where `name` like '".$values['region']."';";
			$result = mysqli_fetch_assoc($this->makeQuery($query));
			if(is_null($result)){
				$query = "INSERT INTO $this->table (name) VALUES ('".$values['region']."');";
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