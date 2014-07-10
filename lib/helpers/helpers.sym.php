<?php
require_once(TOOLKIT . '/class.entrymanager.php');
require_once(TOOLKIT . '/class.xsltpage.php');		
require_once(TOOLKIT . '/class.sectionmanager.php');
require_once(TOOLKIT . '/class.fieldmanager.php');
Class Helpers{

	var $sectionID = null;
	var $querycond = array();
	var $data = array();
	var $page = null;
	var $limit = null;
	
	/*********************************
			function fetchData()			
			grabs all data perpage from the section 
					
	********************************/
			
	function fetchData($querycond,$sectionID,$page,$limit){
		$em = new EntryManager($this);
		$count = $em->fetchCount($sectionID,$querycond['where'],$querycond['joins']);
		$totalpages = round(($count / $limit),0);
		$pageentries = $em->fetchByPage($page,$sectionID,$limit,$querycond['where'],$querycond['joins']);
		$all  = array($pageentries,$totalpages,$count);
		return $all;
	}
	
	
	/*********************************
			function getVals()			
			extracts the values from the objects provdied and formats according to the $noquotes value
					
	********************************/
	function getVals($data,$noquotes = false,$f = false,$fi = false){
		$a = array();				
		$i = 0;
		foreach($data as $d => $dat){				
		$i++;
				var_dump($d);
				if($f == $d){
					if($noquotes){
						
						if(array_key_exists('value',$dat) && $dat['value'] != null){
							$a[] =  $dat['value'];
							continue;
						}elseif(array_key_exists('password',$dat) && $dat['password'] != null){
							$a[] = $dat['password'];
							continue;
						}elseif(array_key_exists('relation_id',$dat) && $dat['relation_id'] != null){
							if(is_array($dat['relation_id'])){
								$a[] = '"'.implode(',',$dat['relation_id']).'"';
							}else{
								$a[] = '"'.$dat['relation_id'].'"';
							}
							continue;
						}elseif(array_key_exists('file',$dat) && $dat['file'] != null){
							$a[] = $dat['file'];
							continue;
						}else{
							$a[] =  '';							
							continue;
						}	
						
						
					}else{
						$a[$fi] =  '"'.$dat['value'].'"';
						continue;
					}
				}else{
					if($noquotes == false){
							if(array_key_exists('value',$dat) && $dat['value'] != null){
								$a[] =  '"'.$dat['value'].'"';
								continue;
							}elseif(array_key_exists('password',$dat) && $dat['password'] != null){
								$a[] = '"'.$dat['password'].'"';
								continue;
							}elseif(array_key_exists('relation_id',$dat) && $dat['relation_id'] != null){
								
								if(is_array($dat['relation_id'])){
									$a[] = '"'.implode(',',$dat['relation_id']).'"';
								}else{
									$a[] = '"'.$dat['relation_id'].'"';
								}
								continue;
							}elseif(array_key_exists('file',$dat) && $dat['file'] != null){
								$a[] = '"'.$dat['file'].'"';
								continue;
							}else{
								$a[] =  '""';							
								continue;
							}
					}else{
						if(array_key_exists('value',$dat) && $dat['value'] != null){
							$a[] =  $dat['value'];
							continue;
						}elseif(array_key_exists('password',$dat) && $dat['password'] != null){
							$a[] = $dat['password'];
							continue;
						}elseif(array_key_exists('relation_id',$dat) && $dat['relation_id'] != null){
							if(is_array($dat['relation_id'])){
								$a[] = '"'.implode(',',$dat['relation_id']).'"';
							}else{
								$a[] = '"'.$dat['relation_id'].'"';
							}
							continue;
						}elseif(array_key_exists('file',$dat) && $dat['file'] != null){
							$a[] = $dat['file'];
							continue;
						}else{
							$a[] =  '';							
							continue;
						}	
					}
				}
				
		}
		return $a;
		
		
		
	}
	
	
	/*********************************
			function fetchData()			
			merges several arrays when they do not have  same amount of values
					
	********************************/
	function array_insert(&$array,$element,$position=null) {
		if (count($array) == 0) {
			$array[] = $element;
		}
		elseif (is_numeric($position) && $position < 0) {
			if((count($array)+position) < 0) {
				$array = array_insert($array,$element,0);
			}
			else {
				$array[count($array)+$position] = $element;
			}
		}
		elseif (is_numeric($position) && isset($array[$position])) {
			$part1 = array_slice($array,0,$position,true);
			$part2 = array_slice($array,$position,null,true);
			$array = array_merge($part1,array($position=>$element),$part2);
			foreach($array as $key=>$item) {
				if (is_null($item)) {
					unset($array[$key]);
				}
			}
		}
		elseif (is_null($position)) {
			$array[] = $element;
		}  
		elseif (!isset($array[$position])) {
			$array[$position] = $element;
		}
		$array = array_merge($array);
		return $array;
	}
	
	
	/*********************************
			function fetchData()			
			grabs all data perpage from the section 
					
	********************************/
	function closest($array, $number) {

		sort($array);
		foreach ($array as $a) {
			if ($a >= $number) return $a;
		}
		return end($array); // or return NULL;
	}
	
	/*********************************
			function __getField()			
			grabs all fields handles and formats according to the $noquotes value
					
	********************************/
	function __getField($fields,$noquotes = false){
		$r = array();		
		foreach($fields as $field){				
			$id = $field->get('id');
			$label = $field->get('label');
			$order = $field->get('sortorder');
			if($noquotes){
				$r[$order] = $label;									
			}else{
				$r[] = '"'.$label.'"';															
				
			}
			
			
		}	
		
		return $r;
	}
}

?>