<?php

	class Responsible {

		private $db;
		private $table;

		public function __construct($db)
		{
			$this->db = $db;
			$this->table = get_class($this);
			mysqli_query($this->db->link, "create table if not exists $this->table ( `id` INT NOT NULL AUTO_INCREMENT , `fio` VARCHAR(1024) NOT NULL, `phone` VARCHAR(1024) NOT NULL , PRIMARY KEY (`id`))");
			
		}

		public function findOrCreate($values)
		{
			$query = "SELECT id FROM $this->table where "
				." fio like '".$values['fio']."' "
				." and phone like '".$values['phone']."'";
			$result = mysqli_fetch_assoc($this->makeQuery($query));
			if(is_null($result)){
				$query = "INSERT INTO $this->table ("
					."fio, phone"
					.") VALUES ("
					."'".$values['fio']."', "
					."'".$values['phone']."' "
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