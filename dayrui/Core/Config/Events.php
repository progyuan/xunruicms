<?php namespace Config;

use CodeIgniter\Events\Events;

if (CI_DEBUG)
{
	Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
	// Handles the display of the toolbar itself. MUST remain here for toolbar to be displayed.
	Events::on('pre_system', function () {
		Services::toolbar()->respond();
	});
}

require_once WEBPATH.'config/hooks.php';