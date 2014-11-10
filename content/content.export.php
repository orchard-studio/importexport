<?php
		
				ini_set('xdebug.var_display_max_depth', 5);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);
		require_once(BOOT . '/func.utilities.php');
		require_once(EXTENSIONS . '/importexport/lib/php-export-data/php-export-data.class.php');
		require_once(EXTENSIONS . '/importexport/lib/parsecsv-0.3.2/parsecsv.lib.php');		
		require_once(TOOLKIT . '/class.jsonpage.php');		
		require_once(EXTENSIONS . '/importexport/lib/helpers/helpers.sym.php');
		require_once(EXTENSIONS . '/importexport/lib/helpers/arraytoxml.php');
		class contentExtensionImportexportExport extends JSONPage{
			
			
			
			 public function view()
			{	
				
				if(isset($_REQUEST['headers'])){
					$this->__addheaders();
				}elseif(isset($_REQUEST['section'])){
					$this->__ajaxexport();
				}
			}
			private function __addheaders(){
				$sectionID = $_REQUEST['headers'];
				$type = $_REQUEST['type'];
				$sm = new SectionManager($this);
				$section = $sm->fetch($sectionID);			
				
				$fields = $section->fetchFields(); 
				$fetch = new Helpers();
				if($type == 'json'){
					$fieldscols = $fetch->__getField($fields,true);
				}elseif($type == 'csv'){
					$fieldscols = $fetch->__getField($fields);
				}elseif($type == 'xml'){
					$fieldscols = $fetch->__getField($fields);
				}
				
				$file = MANIFEST.'/tmp/data-'.$sectionID.'.'.$type;
				$fieldscols = implode(',',$fieldscols);
				$fieldscols .= file_get_contents($file);
				
				if($type != 'json'){
														
					if($type == 'csv'){
						file_put_contents($file,$fieldscols);
					}elseif($type == 'xml'){
						$content = new ArrayToXml();
						$c = $content->buildXml($file);//'['.file_get_contents($file).']';
						$handle = fopen($file,'w');
						fwrite($handle,$c);
						fclose($handle);
					}
								
				}else{
					$content = '['.file_get_contents($file).']';
					$handle = fopen($file,'w');
					fwrite($handle,$content);
					fclose($handle);
					
				}
				$this->_Result = array('progress'=>'headers','file'=>$file,'type'=>$type);	
				
			}
			
			private function __ajaxexport(){
				 // Load the fieldmanager:           
					$querycond = array('where'=>$where,'joins'=>$joins);
					$page = (int)$_REQUEST['page'];								
					$sectionID = (int)$_REQUEST['section'];
					$limit = $_REQUEST['limit'];
					$type = $_REQUEST['type'];
					$fetch = new Helpers();
					
					$data = $fetch->fetchData($querycond,$sectionID,$page,$limit);
					$records = $data[0]['records'];
					$totalpages = $data[0]['total-pages'];					
					$entrys = $this->checkType($records);
					
					$entries = array('entries' => $entrys);
					
					if($entries != ''){
						$this->__insert($entries,$sectionID,$type);	
					}
					
					if($totalpages != $page){
						
						$next = array(
									'section' => $sectionID,
									'page' => $page,
									'limit' => $limit,
									'progress'=>'success',
									'total-pages'=>$totalpages,
									'type' => $type
						);
						$this->_Result = $next;
					}elseif($entries['entries'] == ''){
						$this->_Result = array('progress'=>'noentries');			
					}else{						
						$this->_Result = array('progress'=>'completed');									
					}
			}
			private function checkType($records){
				$sectionID = $_REQUEST['section'];
				$sm = new SectionManager($this);
				$section = $sm->fetch($sectionID);			
				$fields = $section->fetchFields();
				$type = $_REQUEST['type'];
				if($type == 'json'){
					$entrys = $this->__getJsonValues($records,$fields,$section);
				}elseif($type == 'xml'){
					$entrys = $this->__getXMLValues($records,$fields,$section);
				}elseif($type == 'csv'){
					$entrys = $this->__getCsvValues($records);
				}
				
				return $entrys;
			}
			
			
			
			
			private function __getCsvValues($array){						
				$ents = array();
				$fetch = new Helpers();
					
				foreach($array as $en => $ent){
					$data = array_values((array)$ent);
					$all = $fetch->getVals($data[1]);
					
					$ents[] = implode(',',$all);
					
				}
				
				$l = "\r\n".implode("\r\n",$ents);		
				return $l;
			}
			
			private function __getJsonValues($array,$field,$section){						
				$fetch = new Helpers();
				$fields = $fetch->__getField($field,true);
				$c = count($fields);
				$count = count($array);
				$json = array();				
				$a = array();
				//var_dump($array);
				foreach($array as $en => $ent){						
					$data = array_values((array)$ent);										
					$fields = array_values($fields);
					$data = array_values($data[1]);
					foreach($fields as $fi => $f){
							if($data[$fi] != null){
								if(array_key_exists('value',$data[$fi]) && $data[$fi]['value'] != null){
									$a[$f] =  $data[$fi]['value'];
									
								}elseif(array_key_exists('password',$data[$fi]) && $data[$fi]['password'] != null){
									$a[$f] = $data[$fi]['password'];
									
								}elseif(array_key_exists('relation_id',$data[$fi]) && $data[$fi]['relation_id'] != null){
									if(is_array($data[$fi]['relation_id'])){
										$a[$f] = implode(',', $data[$fi]['relation_id']);
									}else{
										$a[$f] = $data[$fi]['relation_id'];									
									}
								}elseif(array_key_exists('file',$data[$fi]) && $data[$fi]['file'] != null){
									$a[$f] = $data[$fi]['file'];
									
								}else{
									$a[$f] =  'empty';							
									
								}	
								
							}							
					}	
					
					$json[] = json_encode($a);					
				}								
				//die;
				
				$j = implode(',',$json);				
				return $j;
			}
			
			private function __getXMLValues($array,$field,$section){		
				$fetch = new Helpers();
				$fields = $fetch->__getField($field,true);
				$c = count($fields);
				$json = array();				
				$a = array();
				foreach($array as $en => $ent){						
					$data = array_values((array)$ent);										
					$fields = array_values($fields);
					$data = array_values($data[1]);
					foreach($fields as $fi => $f){
							if($data[$fi] != null){
								if(array_key_exists('value',$data[$fi]) && $data[$fi]['value'] != null){
									$a[$f] =  $data[$fi]['value'];
									
								}elseif(array_key_exists('password',$data[$fi]) && $data[$fi]['password'] != null){
									$a[$f] = $data[$fi]['password'];
									
								}elseif(array_key_exists('relation_id',$data[$fi]) && $data[$fi]['relation_id'] != null){
									$a[$f] = $data[$fi]['relation_id'];
									
								}elseif(array_key_exists('file',$data[$fi]) && $data[$fi]['file'] != null){
									$a[$f] = $data[$fi]['file'];
									
								}else{
									$a[$f] =  'empty';							
									
								}	
								
							}							
					}				
					$xml = new ArrayToXml();
					$json[] = $xml->generate_valid_xml_from_array($a, 'entry');	
					
				}	
				
				
				
				$j = implode($json);	
				
				return $j;
			}
			public function getData(){
				return $this->_data;
			}
			
			private function __insert($array,$sectionID,$type){
				$file = MANIFEST.'/tmp/data-'.$sectionID.'.'.$type;
				$handle = fopen($file,'a+');
				$c = count($array);
				
				foreach($array as $data => $dat){					
					if($type == 'json'){
						fwrite($handle, $dat.',');			
					}else{
						fwrite($handle, $dat);			
					}
				}				
				fclose($handle);
				unset($array);
				unset($file);
			}
		}
?>