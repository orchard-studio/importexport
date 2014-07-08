<?php
require_once(TOOLKIT.'/class.author.php');

Class extension_importexport extends Extension
{
	// About this extension:
	private static $assets_loaded = false;
	public function about()
	{
		return array(
			'name' => 'Importer / Exporter',
			'version' => '0.3',
			'release-date' => '2011-12-15',
			'author' => array(
				'name' => 'Trigger121',
				'website' => ''),
			'description' => 'Import a file in various formats to create new entries for a certain section, or export an existing section to a specified format file.'
		);
	}
	public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'appendAssets'
					
				),
				array(
					'page'=> '/backend/',
					'delegate' => 'AdminPagePreGenerate',
					'callback' => 'loadfilters'
				)				
			);
		}

	public function fetchNavigation() {
		if(Administration::instance()->Author->isDeveloper())
		{
			return array(
				array(
					'location'	=> __('System'),
					'name'		=> __('Importer / Exporter'),
					'link'		=> '/'
				)
			);
		}
	}

    public function update()
    {
        if(file_exists(TMP.'/importcsv.csv'))
        {
            @unlink(TMP.'/importcsv.csv');
        }
    }
	 public function loadfilters(array $context){			
			$page = Administration::instance()->getPageCallback();
			
			if($page['driver'] == 'publish') {		
					$sectionhandle = $page['context']['section_handle'];
					$body = $context['oPage'];
					$wrapper = $body->Context;
					if(!is_null($wrapper)){
						$actions = $wrapper->getChild(1);
						$li = new XMLElement('li');
						$li->setAttribute('class','export-nav');
						$a = new XMLElement('a','Export Entries');
						$a->setAttribute('class','button drawer horizontal export-button');
						$a->setAttribute('data-sectionhandle',$sectionhandle);
						if(isset($_GET['filter'])){
							$keys = 
							$js = array_keys($_GET['filter']);
							$key = $js[0];
							$k = explode(':',$_GET['filter'][$key]);
							
							$content = ''.$key.':'.$k[1];							
							$a->setAttribute('data-filter',$content);												
						}
						$a->setAttribute('href',$_GET['symphony-page']);
						$select = new XMLElement('select');
						$select->setAttribute('class','filtering-fields export-entries');
						$json = new XMLElement('option','JSON');
						$json->setAttribute('value','json');
						$xml = new XMLElement('option','XML');
						$xml->setAttribute('value','xml');
						$csv = new XMLElement('option','CSV');
						$csv->setAttribute('value','csv');
						$options = array($xml,$csv,$json);
						$select->setChildren($options);
						$li->appendChild($a);
						$li->appendChild($select);
						$actions->appendChild($li);
						
					}
					if(isset($_GET['filter'])){
						//var_dump($_GET);
					}
			}			
			
	}
	public static function appendAssets(){
			if( self::$assets_loaded === false
				&& class_exists('Administration')
				&& Administration::instance() instanceof Administration
				&& Administration::instance()->Page instanceof HTMLPage
			){

				self::$assets_loaded = true;

				$page = Administration::instance()->Page;
				$page->addStylesheetToHead(URL . '/extensions/importexport/assets/importexport.css','screen',time() + 1,false);
				$page->addScriptToHead(URL.'/extensions/importexport/assets/importexport.js', 3001);							
			}
		}
	
}
