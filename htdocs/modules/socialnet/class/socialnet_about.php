<?php
//  ------------------------------------------------------------------------ //
//                      SOCIALNET - MODULE FOR XOOPS 2                       //
//                  Copyright (c) 2009-2010  David Yanez Osses               //
//                     <http://www.ipwgc.com/>                      		 //
// ------------------------------------------------------------------------- //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //

if (!defined("XOOPS_ROOT_PATH")) {
die("XOOPS root path not defined");
}

/**
* Class About is a simple class that lets you build an about page
* @author The SmartFactory <www.smartfactory.ca>
*/

class SocialnetAbout
{
	var $_lang_aboutTitle;
	var $_lang_author_info;
	var $_lang_developer_lead;
	var $_lang_developer_contributor;
	var $_lang_developer_website;
	var $_lang_developer_email;
	var $_lang_developer_credits;
	var $_lang_module_info;
	var $_lang_module_status;
	var $_lang_module_release_date;
	var $_lang_module_demo;
	var $_lang_module_support;
	var $_lang_module_bug;
	var $_lang_module_submit_bug;
	var $_lang_module_feature;
	var $_lang_module_submit_feature;
	var $_lang_module_disclaimer;
	var $_lang_author_word;
	var $_lang_version_history;
	var $_lang_by;
	var $_tpl;

	function SocialnetAbout($aboutTitle='About')
	{
		global $xoopsModule, $xoopsConfig;

		$fileName = XOOPS_ROOT_PATH . "/modules/" . $xoopsModule->getVar('dirname') . '/language/' . $xoopsConfig['language'] . '/about.php';
		if (file_exists($fileName)) {
			include_once $fileName;
		} else {
			include_once XOOPS_ROOT_PATH . "/modules/" . $xoopsModule->getVar('dirname') . '/language/english/about.php';
		}
		$this->_aboutTitle = $aboutTitle;

		$this->_lang_developer_contributor = _AB_SOCIALNET_DEVELOPER_CONTRIBUTOR;
		$this->_lang_developer_website = _AB_SOCIALNET_DEVELOPER_WEBSITE;
		$this->_lang_developer_email = _AB_SOCIALNET_DEVELOPER_EMAIL;
		$this->_lang_developer_credits = _AB_SOCIALNET_DEVELOPER_CREDITS;
		$this->_lang_module_info = _AB_SOCIALNET_MODULE_INFO;
		$this->_lang_module_status = _AB_SOCIALNET_MODULE_STATUS;
		$this->_lang_module_release_date =_AB_SOCIALNET_MODULE_RELEASE_DATE ;
		$this->_lang_module_demo = _AB_SOCIALNET_MODULE_DEMO;
		$this->_lang_module_support = _AB_SOCIALNET_MODULE_SUPPORT;
		$this->_lang_module_bug = _AB_SOCIALNET_MODULE_BUG;
		$this->_lang_module_submit_bug = _AB_SOCIALNET_MODULE_SUBMIT_BUG;
		$this->_lang_module_feature = _AB_SOCIALNET_MODULE_FEATURE;
		$this->_lang_module_submit_feature = _AB_SOCIALNET_MODULE_SUBMIT_FEATURE;
		$this->_lang_module_disclaimer = _AB_SOCIALNET_MODULE_DISCLAIMER;
		$this->_lang_author_word = _AB_SOCIALNET_AUTHOR_WORD;
		$this->_lang_version_history = _AB_SOCIALNET_VERSION_HISTORY;

	}

	function sanitize($value) {
		$myts = MyTextSanitizer::getInstance();
		return $myts->displayTarea($value, 1);
	}

	function render()
	{
		/**
		 * @todo move the output to a template
		 * @todo make the output XHTML compliant
		 */
		$myts = &MyTextSanitizer::getInstance();

		global $xoopsModule;

		xoops_cp_header();

		$module_handler = &xoops_gethandler('module');
		$versioninfo = &$module_handler->get($xoopsModule->getVar('mid'));

		adminmenu(3);

		include_once XOOPS_ROOT_PATH . '/class/template.php';

		$this->_tpl =& new XoopsTpl();

		$this->_tpl->assign('module_url', XOOPS_URL . "/modules/" . $xoopsModule->getVar('dirname') . "/");
		$this->_tpl->assign('module_image', $versioninfo->getInfo('image'));
		$this->_tpl->assign('module_name', $versioninfo->getInfo('name'));
		$this->_tpl->assign('module_version', $versioninfo->getInfo('version'));
		$this->_tpl->assign('module_status_version', $versioninfo->getInfo('status_version'));

		// Left headings...
		if ($versioninfo->getInfo('author_realname') != '') {
			$author_name = $versioninfo->getInfo('author') . " (" . $versioninfo->getInfo('author_realname') . ")";
		} else {
			$author_name = $versioninfo->getInfo('author');
		}
		$this->_tpl->assign('module_author_name', $author_name);

		$this->_tpl->assign('module_license', $versioninfo->getInfo('license'));

		$this->_tpl->assign('module_credits', $versioninfo->getInfo('credits'));

		// Developers Information
		$this->_tpl->assign('module_developer_lead', $versioninfo->getInfo('developer_lead'));
		$this->_tpl->assign('module_developer_contributor', $versioninfo->getInfo('developer_contributor'));
		$this->_tpl->assign('module_developer_website_url', $versioninfo->getInfo('developer_website_url'));
		$this->_tpl->assign('module_developer_website_name', $versioninfo->getInfo('developer_website_name'));
		$this->_tpl->assign('module_developer_email', $versioninfo->getInfo('developer_email'));

		$people = $versioninfo->getInfo('people');
		if ($people) {

			$this->_tpl->assign('module_people_developers', isset($people['developers']) ? array_map(array($this, 'sanitize'), $people['developers']) : false);
			$this->_tpl->assign('module_people_testers', isset($people['testers']) ? array_map(array($this, 'sanitize'), $people['testers']) : false);
			$this->_tpl->assign('module_people_translaters', isset($people['translaters']) ? array_map(array($this, 'sanitize'), $people['translaters']) : false);
			$this->_tpl->assign('module_people_documenters', isset($people['documenters']) ? array_map(array($this, 'sanitize'), $people['documenters']) : false);
			$this->_tpl->assign('module_people_other', isset($people['other']) ? array_map(array($this, 'sanitize'), $people['other']) : false);
		}
		//$this->_tpl->assign('module_developers', $versioninfo->getInfo('developer_email'));

		// Module Development information
		$this->_tpl->assign('module_date', $versioninfo->getInfo('date'));
		$this->_tpl->assign('module_status', $versioninfo->getInfo('status'));
		$this->_tpl->assign('module_demo_site_url', $versioninfo->getInfo('demo_site_url'));
		$this->_tpl->assign('module_demo_site_name', $versioninfo->getInfo('demo_site_name'));
		$this->_tpl->assign('module_support_site_url', $versioninfo->getInfo('support_site_url'));
		$this->_tpl->assign('module_support_site_name', $versioninfo->getInfo('support_site_name'));
		$this->_tpl->assign('module_submit_bug', $versioninfo->getInfo('submit_bug'));
		$this->_tpl->assign('module_submit_feature', $versioninfo->getInfo('submit_feature'));

		// Warning
		$this->_tpl->assign('module_warning', $this->sanitize($versioninfo->getInfo('warning')));

		// Author's note
		$this->_tpl->assign('module_author_word', $versioninfo->getInfo('author_word'));

	    // For changelog thanks to 3Dev
	    global $xoopsModule;
	    $filename = XOOPS_ROOT_PATH . '/modules/' . $xoopsModule->getVar('dirname') . '/changelog.txt';
	    if(is_file($filename)){

	        $filesize = filesize($filename);
	        $handle = fopen($filename, 'r');
	        $this->_tpl->assign('module_version_history', $myts->displayTarea(fread($handle, $filesize), true));
	        fclose($handle);
	    }

		$this->_tpl->display( 'db:socialnet_about.html' );

		xoops_cp_footer();
	}
}

?>
