<?php

	class DB {
		public $link;

		public function __construct($db_host, $db_user, $db_password, $db_name)
		{
			$this->link = mysqli_connect($db_host, $db_user, $db_password);
			if (!mysqli_select_db($this->link, $db_name)) {
				echo("creating database $db_name\n");
				mysqli_query($this->link, "CREATE DATABASE IF NOT EXISTS $db_name");
				mysqli_select_db($this->link, $db_name);
			}
		}

		public function get($id, $field)
		{
			$query = $this->createSelect($id, $field);
			$result = $this->makeQuery($query); //будет в виде ['age'=>25]
			return $result[$field]; //а тут достанем 25
		}

		//Создает строку с запросом:
		private function createSelect($id, $field)
		{
			$table = $this->table;
			return "SELECT $field FROM $table WHERE id=$id";
		}

		//Совершает запрос к базе:
		private function makeQuery($query)
		{
			$result = mysqli_query($this->link, $query);
			return mysqli_fetch_assoc($result);
		}
	}
?>