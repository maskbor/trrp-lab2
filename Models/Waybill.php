<?php

	class Waybill {

		private $db;
		private $table;

		public function __construct($db)
		{
			$this->db = $db;
			$this->table = get_class($this);
			mysqli_query($this->db->link,
			"create table if not exists $this->table ( `id` INT NOT NULL AUTO_INCREMENT ,
				`id_region` INT NOT NULL ,
				`id_vehicle` INT NOT NULL ,
				`fuel_add` INT NOT NULL ,
				`fuel_start` INT NOT NULL ,
				`fuel_end` INT NOT NULL ,
				`odometer_start` INT NOT NULL ,
				`odometer_end` INT NOT NULL ,
				`is_city` INT NOT NULL ,
				`comment` VARCHAR(1024) NOT NULL,
				PRIMARY KEY (`id`))"
			);
		}

		public function findOrCreate($values)
		{
			$query = "SELECT id FROM $this->table where "
				." id_region = ".$values['id_region']
				." and id_vehicle = ".$values['id_vehicle']
				." and fuel_add = ".$values['fuel_add']
				." and fuel_start = ".$values['fuel_start']
				." and fuel_end = ".$values['fuel_end']
				." and odometer_start = ".$values['odometer_start']
				." and odometer_end = ".$values['odometer_end']
				." and is_city = ".$values['is_city']
				." and comment like '".$values['comment']."'";
			$result = mysqli_fetch_assoc($this->makeQuery($query));
			if(is_null($result)){
				$query = "INSERT INTO $this->table ("
					."id_region, id_vehicle, fuel_add, fuel_start, fuel_end, odometer_start, odometer_end, is_city, comment"
					.") VALUES ("
					.$values['id_region'].", "
					.$values['id_vehicle'].", "
					.$values['fuel_add'].", "
					.$values['fuel_start'].", "
					.$values['fuel_end'].", "
					.$values['odometer_start'].", "
					.$values['odometer_end'].", "
					.$values['is_city'].", "
					."'".$values['comment']."' "
					.");";
				$result = $this->makeQuery($query);
				return mysqli_insert_id($this->db->link);
			}
			return $result['id'];
		}

		public function getAll()
		{
			$query = "SELECT r.name region, v.name, v.regNumber, resp.fio, resp.phone, v.fuel, v.odometer, w.`fuel_add`, w.`fuel_start`, w.`fuel_end`,w.`odometer_start`,w.`odometer_end`, w.`is_city`,w.`comment` FROM `Waybill` w, Region r, Vehicle v, Responsible resp WHERE w.`id_region`=r.id and w.`id_vehicle`=v.id and resp.id=v.id_responsible ";
			return $this->makeQuery($query);
		}

		private function makeQuery($query)
		{
			return mysqli_query($this->db->link, $query);
		}
	}
?>