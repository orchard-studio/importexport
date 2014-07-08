<?php
		
		ini_set('xdebug.var_display_max_depth', 5);
		ini_set('xdebug.var_display_max_children', 9000);
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
			
			
			/*********************************
			function __addheaders()
			
					:this function adds the final column handles to the first line of the file
					
					$param : $_REQUEST structure 
					$_REQUEST['headers'] = (string) / (number) [represents section handle of section id]					
					$_REQUEST['type'] = (string) [represents the chosen export type 'json/xml/csv/excel' ] 										
			
			********************************/
			private function __addheaders(){
				$sectionID = $_REQUEST['headers'];
				$type = $_REQUEST['type'];
				$sm = new SectionManager($this);
				$section = $sm->fetch($sectionID);			
				$file = MANIFEST.'/tmp/data-'.$sectionID.'.'.$type;
				$fields = $section->fetchFields(); 
				$fetch = new Helpers();
				if($type != 'json'){
					$fieldscols = $fetch->__getField($fields);					
					$fieldscols = implode(',',$fieldscols);
					$fieldscols .= file_get_contents($file);														
					if($type == 'csv'){
						
						file_put_contents($file,$fieldscols);
						
					}elseif($type == 'xml'){
					
						$content = new ArrayToXml();
						$c = $content->buildXml($file);
						$handle = fopen($file,'w');
						fwrite($handle,$c);
						fclose($handle);
					
					}								
				}else{
					$content = '['.rtrim(file_get_contents($file),',').']';
					$handle = fopen($file,'w');
					fwrite($handle,$content);
					fclose($handle);
					
				}
				$this->_Result = array('progress'=>'headers','file'=>$file,'type'=>$type);	
				
			}
			
			
			
			/*********************************
			function __ajaxexport()
					
					$param : $_REQUEST structure 
					$_REQUEST['section'] = (string) / (number) [represents section handle of section id]
					$_REQUEST['page'] = (number) [represents the current page number and is incremented per request til reaches maximum pages]
					$_REQUEST['type'] = (string) [represents the chosen export type 'json/xml/csv/excel' ] 
					$_REQUEST['total-pages'] = (number) [represents the total amount of pages found after the first request]
					$_REQUEST['limit'] = (number) [represents the amount of entries to grab per request]
					$_REQUEST['progress'] = (string) [contains the current progress of all the pages and batched entries]
					$_REQUEST['filter'] = (string) [contains a json object string to decode allowing export of filtered section entries]
					
			
			********************************/
			private function __ajaxexport(){
				 // Load the fieldmanager:   
					$sm = new SectionManager($this);
					// checking for section string to convert to id instead
					if(!is_numeric($_REQUEST['section'])){
						
						$id = $sm->fetchIDfromHandle($_REQUEST['section']);
						$section = $sm->fetch($id);						
						unset($_REQUEST['section']);
						$_REQUEST['section'] = $id;
						
					}else{
					
						$section = $sm->fetch($_REQUEST['section']);							
						
					}	
					
					$filter = $filter_value = $where = $joins = NULL;
					// check for filtering 
					
					if(isset($_REQUEST['filter'])){
					
							/* grabs filters and converts the filters json string to colon seperated string for field name and value to filter by*/
							$ftls = $_REQUEST['filter'];
							$keys = array_keys((array) json_decode($_REQUEST['filter']));
							$values = array_values((array) json_decode($_REQUEST['filter']));
							$keys = str_replace('filter','',str_replace(']','',str_replace('[','',$keys[0])));
							$values = str_replace('regexp:','',$values[0]);
							unset($_REQUEST['filter']);
							$_REQUEST['filter'] = $keys .':'.$values;

							list($field_handle , $filter_value) = explode(':' ,$_REQUEST['filter'] , 2);
							if(!is_array($field_handle)){
								if(strpos($field_handle,',')){
									$field_names = explode(',' , $field_handle);
								}else{
									$field_names[] = $field_handle;
								}
							}else{
								$field_names = $field_handle;
							}					
							foreach ($field_names as $field_name) {								
								$filter_value = rawurldecode($filter_value);
								
								$filter = Symphony::Database()->fetchVar('id' , 0 , "SELECT `f`.`id`  FROM `tbl_fields` AS `f`, `tbl_sections` AS `s`
								WHERE `s`.`id` = `f`.`parent_section`  AND f.`element_name` = '$field_name'  AND `s`.`handle` = '" . $section->get('handle') . "' "); // LIMIT 1

								$field = FieldManager::fetch($filter);
								if ($field instanceof Field) {
									// For deprecated reasons, call the old, typo'd function name until the switch to the
									// properly named buildDSRetrievalSQL function.
									//$field->buildDSRetrievalSQL(array($filter_value) , $joins , $where , false);
									// removed the previous function to handle ds sql execution with regex instead
									$field->buildRegexSQL('regexp:'.$filter_value,array('value'),$joins,$where);									
									$filter_value = rawurlencode($filter_value);
								}							
							}
							if (!is_null($where)) {
								$where = str_replace('AND' , 'OR' , $where); // multiple fields need to be OR
								$where = trim($where);
								$where = ' AND (' . substr($where , 2 , strlen($where)) . ')'; // replace leading OR with AND
							}																			
					}
					
					$querycond = array('where'=>$where,'joins'=>$joins);
					$page = (int)$_REQUEST['page'];								
					$sectionID = (int)$_REQUEST['section'];
					$limit = $_REQUEST['limit'];
					$type = $_REQUEST['type'];
					$fetch = new Helpers();					
					$data = $fetch->fetchData($querycond,$sectionID,$page,$limit);					
					$records = $data[0]['records'];
					$totalpages = (int) $data[0]['total-pages'];					
					
					$entrys = $this->checkType($records);
					
					$entries = array('entries' => $entrys);
					// insert entries into temp file before export completion
					if($entries != ''){
						$this->__insert($entries,$sectionID,$type);	
					}
					
					// checks if every single page has been exported before returning more data to deal with file download
					if($totalpages != $page){
						// returns an array of values to grab next page in js
						$next = array(
									'section' => $sectionID,
									'page' => $page,
									'limit' => $limit,
									'progress'=>'success',
									'total-pages'=>$totalpages,
									'type' => $type,
									'filter' => $ftls
						);
						$this->_Result = $next;
					}elseif($entries['entries'] == ''){
						// returns an alert if there are no entries in the section
						$this->_Result = array('progress'=>'noentries');			
					}else{						
						// returns for completion of the current batch of entries
						$this->_Result = array('progress'=>'completed','section'=>$sectionID,'type'=>$type);									
					}
			}
			
			/*********************************
				function checkType()
				$records : contains all the entry records from the section
				
			********************************/
			private function checkType($records){
				$sectionID = $_REQUEST['section'];
				$sm = new SectionManager($this);
				$section = $sm->fetch($sectionID);			
				$fields = $section->fetchFields();
				$type = $_REQUEST['type'];
				
				// according to the type value selected grabs data in different ways with each of their relevant functions
				if($type == 'json'){
					$entrys = $this->__getJsonValues($records,$fields,$section);
				}elseif($type == 'xml'){
					$entrys = $this->__getXMLValues($records,$fields,$section);
				}elseif($type == 'csv'){
					$entrys = $this->__getCsvValues($records);
				}
				
				return $entrys;
			}
			
			
			/*********************************
				function __getCsvValues()
				$array 	: contains all the entries retrieved from the section
			
			********************************/
			
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
			
			
			/*********************************
				function __getJsonValues()
				$array 	: contains all the entries retrieved from the section
				$field : contains the field values in order to create the correct json structure
				$section : contains the section id 
				
			********************************/
			private function __getJsonValues($array,$field,$section){						
				$fetch = new Helpers();
				$fields = $fetch->__getField($field,true);
				$c = count($fields);
				$count = count($array);
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
				
				$j = implode(',',$json);				
				return $j;
			}
			
			
			/*********************************
				function __getXMLValues()
				$array 	: contains all the entries retrieved from the section
				$field : contains the field values in order to create the correct json structure
				$section : contains the section id 
				
			********************************/
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
			
			/*********************************
				function __insert()
				$array 	: contains all the entries retrieved from the section
				$type : contains the type of file requested for export
				$sectionID : contains the section id 
				
			********************************/
			private function __insert($array,$sectionID,$type){
				$file = MANIFEST.'/tmp/data-'.$sectionID.'.'.$type;
				$c = count($array);
				if(file_exists($file) && $type == 'json' && $c == 1){
					//unlink($file);
				}
				$handle = fopen($file,'a+');								
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