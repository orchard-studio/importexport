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
				$this->__ajaxImportRows();
			
			}
			private function __importStep3Page()
			{
				// Store the entries:
				$sectionID = $_POST['section'];
				$uniqueAction = $_POST['uniqueaction'];
				$uniqueField = $_POST['uniquefield'];
				$countNew = 0;
				$countUpdated = 0;
				$countIgnored = 0;
				$countOverwritten = 0;
				
				$fm = new FieldManager($this);		
				
				
				$ext = pathinfo($_POST['file'], PATHINFO_EXTENSION);	
				if($ext == 'json'){			
					$section = $fm->fetch(null,$sectionID); // contains field ids
					$csv = $this->JsonToCsv(false);
				}elseif($ext == 'csv'){			
					$csv = $this->__getCSV(false);
				}
				
				// Load the information to start the importing process:
				$this->__addVar('section-id', $sectionID);
				$this->__addVar('unique-action', $uniqueAction);
				$this->__addVar('unique-field', $uniqueField);
				$this->__addVar('import-url', URL . '/symphony/extension/importexport/');
				
				// Output the CSV-data:
				$csvData = $csv->data;
				
				$csvTitles = (array) $csv->titles;
				$this->__addVar('total-entries', count($csvData));
				$i = 0;
				$ids = array();
				if($ext == 'json'){			
					foreach ($section as $id)
					{
						$label = $id->get('label');
						$ids[] = $label;			
					}			
				}elseif($ext == 'csv'){
					foreach ($csvTitles as $title){				
						$ids[] = $_POST['field-' . $i];		
						$i++;				
					}
				}
				// Store the associated Field-ID's:
			   
				
				$this->__addVar('field-ids', implode(',', $ids));

				$this->addScriptToHead(URL . '/extensions/importexport/assets/import.js');
				$this->Form->appendChild(new XMLElement('h2', __('Import in progress...')));
				$this->Form->appendChild(new XMLElement('div', '<div class="bar"></div>', array('class' => 'progress')));
				$this->Form->appendChild(new XMLElement('div', null, array('class' => 'console')));
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
			
			private function __getCSV($csv)
			{
				$fname = MANIFEST.'/tmp/data-1.json';
				if($csv){			
					if(file_exists($fname)){
						unlink($fname);
					}
					
					file_put_contents($fname,json_encode($csv->data));
				}else{
					$data = file_get_contents($fname);
					$content = json_decode($data);
					$container->titles = $content[0];
					$container->data = $content;
					
				
					return $container;
				}		
			}
			private function JsonToCsv($json){
					$fname = MANIFEST.'/tmp/data-2.json';
					if($json){
						if(file_exists($fname)){
							unlink($fname);
						}
						
						file_put_contents($fname,$json);				
					}else{
						$data = file_get_contents($fname);
						$content = json_decode($data);
						$container = $content;
						
					
						return $container;
					}
					
			}
			private function importJson($json,$csvNode){
		
				$fm = new FieldManager();
				$t = array();
				foreach($json as $js => $j){
					$array = (array) $j;
					
					foreach($array as $arr => $ar){
						$a = $fm->fetch($arr,$sectionID);
						
						$t[] = $a->get('label');
					}
					
				}
				$t = array_unique($t);			
				foreach($t as $f => $field){
					$csvNode->appendChild(new XMLElement('key', $field));
				}
			}

			private function __addVar($name, $value)
			{
				$this->Form->appendChild(new XMLElement('var', $value, array('class' => $name)));
			}		
			
			private function __ajaxImportRows()
			{
				$fm = new FieldManager($this);		
				
				$ext = pathinfo($_REQUEST['file'], PATHINFO_EXTENSION);	
				if($ext == 'json'){			
					$section = $fm->fetch(null,$sectionID); // contains field ids
					$csv = $this->JsonToCsv(false);
				}elseif($ext == 'csv'){			
					$csv = $this->__getCSV(false);
				}
				
				
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

					// Load the fieldmanager:
					
					// Load the entrymanager:
					$em = new EntryManager($this);

					// Load the CSV data of the specific rows:
					$csvTitles = $csv->titles;
					$csvData = $csv->data;
					for ($i = $currentRow * 10; $i < ($currentRow + 1) * 10; $i++)
					{
						// Start by creating a new entry:
						$entry = new Entry($this);
						$entry->set('section_id', $sectionID);
						
						// Ignore this entry?
						$ignore = false;
						
						// Import this row:
						if($ext == 'json'){
							$row = array_values((array) $csv[$i]);													
						}
						else{
							$row = $csvData[$i];
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
								foreach ($row as $value)
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
										
										if ($data != false) {
											$entry->setData($fieldID, $data);
										}
										//var_dump($data);
										//var_dump($fieldID);
									}
															
									$j++;
								}
								
								// Store the entry:
								$entry->commit();
							}
						}
					}	
//die;					
				} else {
					die(__('[ERROR: Data not found!]'));
				}
				
				if (count($updated) > 0) {
					$messageSuffix .= ' ' . __('(updated: ') . implode(', ', $updated) . ')';
				}
				if (count($ignored) > 0) {
					$messageSuffix .= ' ' . __('(ignored: ') . implode(', ', $updated) . ')';
				}

				die('[OK]' . $messageSuffix);
			}
		}
		
		?>