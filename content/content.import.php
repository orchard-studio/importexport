<?php
		
		ini_set('xdebug.var_display_max_depth', 5);
		ini_set('xdebug.var_display_max_children', 256);
		ini_set('xdebug.var_display_max_data', 1024);
		require_once(BOOT . '/func.utilities.php');
		require_once(EXTENSIONS . '/importexport/lib/php-export-data/php-export-data.class.php');
		require_once(EXTENSIONS . '/importexport/lib/parsecsv-0.3.2/parsecsv.lib.php');		
		require_once(TOOLKIT . '/class.jsonpage.php');		
		require_once(EXTENSIONS . '/importexport/lib/helpers/helpers.sym.php');
		class contentExtensionImportexportImport extends JSONPage{
			
			
			
			 public function view()
			{
				if(isset($_REQUEST['uniqueaction'])){
					$this->__ajaxImportRows();
				}elseif($_REQUEST['section']){
					$this->__removefiles();
				}
			
			}
			private function __removefiles(){
				array_map('unlink', glob(MANIFEST."/tmp/*"));
				$nextFields = array('progress'=>'finished');
				$this->_Result = $nextFields;
			}
			private function getDrivers()
			{
				$classes = glob(EXTENSIONS . '/importexport/drivers/*.php');
				$drivers = array();
				foreach ($classes as $class)
				{
					include_once($class);
					$a = explode('_', str_replace('.php', '', basename($class)));
					$driverName = '';
					for ($i = 1; $i < count($a); $i++)
					{
						if ($i > 1) {
							$driverName .= '_';
						}
						$driverName .= $a[$i];
					}
					$className = 'ImportDriver_' . $driverName;
					$drivers[$driverName] = new $className;
				}
				return $drivers;
			}
			
			private function __getCSV()
			{
					$fname = MANIFEST.'/tmp/data-1.json';
					$data = file_get_contents($fname);
					$content = json_decode($data);
					$container->titles = $content[0];
					$container->data = $content;
					return $container;				
			}
			
			private function __getXML(){
					$fname = MANIFEST.'/tmp/data-3.xml';			
					$se = (array) simplexml_load_file($fname);					
					return $se;
			}
			
			private function JsonToCsv(){
					$fname = MANIFEST.'/tmp/data-2.json';					
					$data = file_get_contents($fname);					
					$fm = new FieldManager();
					$t = array();										
					$container = json_decode($data);
					return $container;					
			}
			/*private function importJson($json,$csvNode){
		
				$fm = new FieldManager();
				$t = array();		
				$json = explode('},{',str_replace(']','',str_replace('[','',$json)));
				array_pop($json);
				$ja = array();
				foreach($json as $js => $jso){
					$ja[] = str_replace('{','',$jso);
				}
				$json = '[{'.implode("},{",$ja).'}]';
				$json = json_decode($json);
				$t = array_unique($t);			
				foreach($t as $f => $field){
					$csvNode->appendChild(new XMLElement('key', $field));
				}
			}*/

			private function __addVar($name, $value)
			{
				$this->Form->appendChild(new XMLElement('var', $value, array('class' => $name)));
			}		
			
			private function returnLast2Levels($item){
				
				foreach($item as $i => $obj){
					if(is_object($obj)){
						
						$items[] = array_values((array) $obj);
					}else{
						$items[] = $obj;
					}	
				}
				return $items;
			}
			private function objToArray($obj, &$arr){

				if(!is_object($obj) && !is_array($obj)){
					$arr = $obj;
					return $arr;
				}

				foreach ($obj as $key => $value)
				{
					if (!empty($value))
					{
						$arr[$key] = array();
						$this->objToArray($value, $arr[$key]);
					}
					else
					{
						$arr[$key] = $value;
					}
				}
				return $arr;
			}
			    /**
			 * This function imports 10 rows of the CSV data
			 * @return void
			 */
			private function __ajaxImportRows()
			{
				$fm = new FieldManager($this);		
				$em = new EntryManager($this);	
				$ext = pathinfo($_REQUEST['file'], PATHINFO_EXTENSION);	
				if($ext == 'json'){			
					$section = $fm->fetch(null,$sectionID); // contains field ids
					$csv = $this->JsonToCsv();
				}elseif($ext == 'csv'){			
					$csv = $this->__getCSV();
				}elseif($ext == 'xml'){
					$csv = $this->__getXML();
				}
				
				$msg = null;
				
				
				if ($csv != false) {
					// Load the drivers:
					$drivers = $this->getDrivers();

					// Default parameters:
					$currentRow = intval($_REQUEST['row']);
					$sectionID = $_REQUEST['section'];
					$uniqueAction = $_REQUEST['uniqueaction'];
					$uniqueField = $_REQUEST['uniquefield'];
					$fieldIDs = explode(',', $_REQUEST['fieldids']);
					
					$entryID = null;
					$limit = $_REQUEST['limit'];
					$currentamount = $_REQUEST['currentamount'];
					if($ext != 'xml'){
						$count = (int) count($csv->data);					
					}else{
						$count = (int) count($csv['entry']);					
					}
					$co = intval($currentRow * 200);
					if($currentamount <= $count){
						$nextFields = array(
							'row'=> $currentRow,
							'section'=> $_REQUEST['section'],
							'uniqueaction'=>$_REQUEST['uniqueaction'],
							'uniquefield'=>$_REQUEST['uniquefield'],
							'fieldids'=>$_REQUEST['fieldids'],
							'progress'=>'success',
							'file' => $_REQUEST['file'],
							'count' => $count,
							'currentamount' => $currentamount
						);
						
					}else{
						$nextFields = array(
							'row'=> $currentRow,
							'section'=> $_REQUEST['section'],							
							'progress'=>'completed',
							'type'=> $ext
						);	
					
					}
					
					
					// Load the CSV data of the specific rows:
					$csvTitles = $csv->titles;
					$csvData = $csv->data; 
					///$currentamount = $_REQUEST['count'];
					//$newamount = $currentRow + 50;
					$a = 0;
					//while($currentamount < $newamount; $currentamount++)
					for ($i = $currentRow * 200; $i < ($currentRow + 1) * 200; $i++)
					{	
						$a = $i;						
						// Start by creating a new entry:
						$entry = new Entry($this);
						$entry->set('section_id', $sectionID);
						
						// Ignore this entry?
						$ignore = false;						
						// Import this row:
						if($ext == 'json'){
							$row = array_values((array) $csv);									
							$r =array();
							$x =array();
							foreach($row as $obj){
								$x[] = $this->objtoArray($obj,$r);
							}							
							unset($row);
							$row = $x[$i];
							
						}
						elseif($ext == 'csv'){
							$row = array_values((array) $csvData);
							//unset($row[0]);
							$x = array();
							foreach($row as $r => $ro){
								$x[] = $this->returnLast2Levels($ro);
							}
							unset($row);
							
							$row = $x[$i];
							
						}elseif($ext == 'xml'){
							
							$r =array();
							$x =array();							
							foreach($csv as $obj){
								$x[] = (array) $obj;//$this->objtoArray($obj,$r);
							}							
							unset($row);
							$row = $x[0][$i];
												
						}
						
						if ($row != false) {
							
							// If a unique field is used, make sure there is a field selected for this:
							if ($uniqueField != 'no' && $fieldIDs[$uniqueField] == 0) {
								die(__('[ERROR: No field id sent for: "' . $csvTitles[$uniqueField] . '"]'));
							}
							
							// Unique action:
							if ($uniqueField != 'no') {
								// Check if there is an entry with this value:
								$field = $fm->fetch($fieldIDs[$uniqueField]);
								
								$type = $field->get('type');
								if (isset($drivers[$type])) {
									$drivers[$type]->setField($field);
									
									$entryID = $drivers[$type]->scanDatabase($row[$csvTitles[$uniqueField]]);
								} else {
									$drivers['default']->setField($field);
									
									$entryID = $drivers['default']->scanDatabase($row[$csvTitles[$uniqueField]]);
								}
								
								if ($entryID != false) {
									// Update? Ignore? Add new?
									switch ($uniqueAction)
									{
										case 'update' :
											{
											$a = $em->fetch($entryID);
											$entry = $a[0];
											$updated[] = $entryID;
											break;
											}
										case 'ignore' :
											{
											$ignored[] = $entryID;
											$ignore = true;
											break;
											}
									}
								}
							}
							
							if (!$ignore) {
								// Do the actual importing:
								$j = 0;
								
								foreach ($row as $val => $value)
								{
									
										
										// When no unique field is found, treat it like a new entry
										// Otherwise, stop processing to safe CPU power.
										$fieldID = intval($fieldIDs[$j]);
										
										// If $fieldID = 0, then `Don't use` is selected as field. So don't use it! :-P
										if ($fieldID != 0) {
											$field = $fm->fetch($fieldID);
											
											// Get the corresponding field-type:
											$type = $field->get('type');
											
											if (isset($drivers[$type])) {
												$drivers[$type]->setField($field);
												$data = $drivers[$type]->import($value, $entryID);
											} else {
												$drivers['default']->setField($field);
												
																							
												$data = $drivers['default']->import($value, $entryID);
											}
											// Set the data:
											$msg->data = $data;
											$msg->fields = $fieldID;
											if ($data != false) {
												$entry->setData($fieldID, $data);
											}
											
										
										}
										//var_dump($data);	
										//var_dump($fieldID);	
											
									
									$j++;
								}
								
								$entry->commit();
								// Store the entry:
								
							}
							//die;
						}
					}			
					$nextFields['currentamount'] = $a +1;					
				} else {
					die(__('[ERROR: Data not found!]'));
				}
				
				
				if (count($updated) > 0) {
					$messageSuffix .= ' ' . __('(updated: ') . implode(', ', $updated) . ')';
				}
				if (count($ignored) > 0) {
					$messageSuffix .= ' ' . __('(ignored: ') . implode(', ', $updated) . ')';
				}
				
				$this->_Result = $nextFields;
			}
		}
		
		?>