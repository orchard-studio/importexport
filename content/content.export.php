<?php
		
				
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
				$sm = new SectionManager($this);
				$section = $sm->fetch($sectionID);			
				
				$fields = $section->fetchFields(); 
				$fetch = new Helpers();
				$fieldscols = $fetch->__getField($fields,true);
				$entries = $fieldscols;
				$type = $_REQUEST['type'];
				$file = MANIFEST.'/tmp/data-'.$sectionID.'.'.$type;
				if($type != 'json'){
					$handle = fopen($file,'r+');				
					
					fwrite($handle,$entries);
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
					$data = $ent->getData();
					$all = $fetch->getVals($data);
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
				
				foreach($array as $en => $ent){
					$data = $ent->getData();						
					
					$newarray = array_intersect_key($fields,$data);					
					$ents = $fetch->getVals($data,true);
								 // need to add in empty index positions
					//$new[] = array_combine($newarray,$ents);
				}								
				$json = json_encode($new);				
				return $json;
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
				foreach($array as $data => $dat){
					$dat = $dat . "\r\n";
					file_put_contents($file, $dat, FILE_APPEND | LOCK_EX);			
				}
				unset($array);
				unset($file);
			}
		}
?>