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
	
	function fetchData($querycond,$sectionID,$page,$limit){
		$em = new EntryManager($this);
		$count = $em->fetchCount($sectionID,$querycond['where'],$querycond['joins']);
		$totalpages = round(($count / $limit),0);
		$pageentries = $em->fetchByPage($page,$sectionID,$limit,$querycond['where'],$querycond['joins']);
		$all  = array($pageentries,$totalpages,$count);
		return $all;
	}
	function getVals($data,$noquotes = false,$f = false,$fi = false){
		$a = array();		
		//$diff = array_values($diff);
		
		
		
		foreach($data as $d => $dat){				
				//var_dump($d);
				if($f == $d){
					if($noquotes){
						
						if(array_key_exists('value',$dat) && $dat['value'] != null){
							$a[] =  $dat['value'];
							continue;
						}elseif(array_key_exists('password',$dat) && $dat['password'] != null){
							$a[] = $dat['password'];
							continue;
						}elseif(array_key_exists('relation_id',$dat) && $dat['relation_id'] != null){
							$a[] = $dat['relation_id'];
							continue;
						}elseif(array_key_exists('file',$dat) && $dat['file'] != null){
							$a[] = $dat['file'];
							continue;
						}else{
							$a[] =  'empty';							
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
								$a[] = '"'.$dat['relation_id'].'"';
								continue;
							}elseif(array_key_exists('file',$dat) && $dat['file'] != null){
								$a[] = '"'.$dat['file'].'"';
								continue;
							}else{
								$a[] =  'empty';							
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
							$a[] = $dat['relation_id'];
							continue;
						}elseif(array_key_exists('file',$dat) && $dat['file'] != null){
							$a[] = $dat['file'];
							continue;
						}else{
							$a[] =  'empty';							
							continue;
						}	
					}
				}
				
		}
		return $a;
		
		
		
	}
	
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
	
	function closest($array, $number) {

		sort($array);
		foreach ($array as $a) {
			if ($a >= $number) return $a;
		}
		return end($array); // or return NULL;
	}
	function __getField($fields,$noquotes = false){
		$r = array();		
		foreach($fields as $field){				
			$id = $field->get('id');
			$label = $field->get('label');
			
			if($noquotes){
				$r[$id] = $label;									
			}else{
				$r[] = '"'.$label.'"';															
				
			}
			
			
		}	
		
		return $r;
	}
}

?>