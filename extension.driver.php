<?php
require_once(TOOLKIT.'/class.author.php');

Class extension_importexport extends Extension
{
	// About this extension:
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
	
	public function fetchNavigation() {
		
			return array(
				array(
					'location'	=> __('System'),
					'name'		=> __('Importer / Exporter'),
					'link'		=> '/'
				)
			);
		
	}

    public function update()
    {
        if(file_exists(TMP.'/importcsv.csv'))
        {
            @unlink(TMP.'/importcsv.csv');
        }
    }
	
	
}
