<?php

class JsonExport{
	var $data = array();
	function jsonencode($data){
		return json_encode($data);	
	}
	function jsondecode($data){
		return $data;
	}
	function outputjson($data){
		return $data;
	}
	
}

?>