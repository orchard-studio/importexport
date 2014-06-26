<?php
		
				ini_set('xdebug.var_display_max_depth', 5);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);
		require_once(BOOT . '/func.utilities.php');
		require_once(EXTENSIONS . '/importexport/lib/php-export-data/php-export-data.class.php');
		require_once(EXTENSIONS . '/importexport/lib/parsecsv-0.3.2/parsecsv.lib.php');		
		require_once(TOOLKIT . '/class.jsonpage.php');		
		require_once(EXTENSIONS . '/importexport/lib/helpers/helpers.sym.php');
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
				}
				
				$file = MANIFEST.'/tmp/data-'.$sectionID.'.'.$type;
				$fieldscols = implode(',',$fieldscols) . "\r\n";
				$fieldscols .= file_get_contents($file);
				
				if($type != 'json'){
														
					if($type == 'csv'){
						file_put_contents($file,$fieldscols);
					}else{
						$handle = fopen($file,'r+');
						fwrite($handle,$fieldscols);
						fclose($handle);
					}
								
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
					
					$this->__insert($entries,$sectionID,$type);
					
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
					$entrys = $this->__getXMLValues($records,$fields);
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
				$l = implode("\r\n",$ents);		
				return $l;
			}
			
			private function __getJsonValues($array,$field,$section){		
				$all = array();
				$ents = array();
				$fetch = new Helpers();
				$fields = $fetch->__getField($field,true);
				$c = count($fields);
				$json = array();
				foreach($array as $en => $ent){		
					$data = array_values((array)$ent);
					$newarray = array_intersect_key($fields,$data);					
					$fe = array_flip($fields);
					$d = array();
					foreach($fe as $fi => $f){
						$ed = $fetch->getVals($data[1],true,$f);
						$ents[$f] = 	(string) $ed[0];
					}
					$json[] = json_encode($ents);
				}	
				$j = '['.implode(',',$json).']';
				
				
				return $j;
			}
			
			private function __getXMLValues($array){		
				$all = array();
				$ents = array();
				$fetch = new Helpers();
					
				foreach($array as $en => $ent){
					$data = $ent->getData();
					$all = $fetch->getVals($data);
					$ents[] = implode(',',$all);
				}
				$l = implode("\r\n",$ents);		
				return $l;
			}
			public function getData(){
				return $this->_data;
			}
			
			private function __insert($array,$sectionID,$type){
				$file = MANIFEST.'/tmp/data-'.$sectionID.'.'.$type;
				$handle = fopen($file,'a+');
				
				foreach($array as $data => $dat){					
					
					fwrite($handle, $dat);			
				}				
				fclose($handle);
				unset($array);
				unset($file);
			}
		}
?>