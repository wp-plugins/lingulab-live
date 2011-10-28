<?php
/*
Plugin Name: LinguLab Live 
Plugin URI: http://live.lingulab.de
Description: LinguLab Live Wordpress Plugin ermöglicht die Messung der Textqualität
Author: LinguLab GmbH
Version: 1.0.13
Author URI: http://live.lingulab.de
Min WP Version: 2.8
Stable tag: 1.0.13
*/
/*  Copyright 2009 - 2011  Oliver Storm, Tom Klingenberg

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

lingulabLivePlugin::bootstrap(); # initialize plugin

/**
 * LinguLab Live Plugin Class
 * 
 * Plugin to get rating of text
 * 
 * @author Tom Klingenberg
 * @author Oliver Storm
 */
class lingulabLivePlugin
{	
	/**
	 * instance local store
	 * 
	 * needed for bootstrapping the plugin with reduced global  
	 * namespace interference.
	 * 
	 * @see lingulabLivePlugin::bootstrap()
	 * @var lingulabLivePlugin
	 */	
	private static $__instance = null;
	
	/*
	 * magic numbers
	 */
	private $_homepageUrl = 'http://live.lingulab.de/'; # URI of homepage
	
	private $_version     = '1.0.12'; # plugin version
	
	/**
	 * bootstrap
	 * 
	 * @return unknown_type
	 */
	public static function bootstrap()
	{
		if ( null == lingulabLivePlugin::$__instance )
		{
			lingulabLivePlugin::$__instance = new lingulabLivePlugin(); 
		}
		
		return lingulabLivePlugin::$__instance;
	}	
	
	/**
	 * helper method for ajax_getContent
	 * 
	 * @note not by me, no guarantees taken for this one for 
	 *       whatsoever (tk) 
	 * 
	 * @param  string $code
	 * @return string
    */
    private function _delUnwantedTags ($code){
        $tags = array('h1','h3');
     
        for($a = 0;$a <= count($tags);$a++){
            $code = preg_replace("#<".$tags[$a].".*</".$tags[$a].">#Ui", "", $code);
        }
        return $code;
    }
	
	/**
	 * is this plugin compatible to this wordpress ?
	 * 
	 * private helper function
	 *
	 * @see lingulabLivePlugin::add_lingulab_box()
	 * @return bool false if it is not compatible, true if yes
	 */
	private function _isCompatible()
	{
		if ( version_compare(PHP_VERSION, '5.0.5', '<') )
			return false;
		
		if ( function_exists('add_meta_box') )
			return false;

		return true;
	}
	
	/**
	 * 
	 * @return lingulabLiveOptions
	 */
	private function _getOptions()
	{		
		require_once 'inc/options.php';		
		return lingulabLiveOptions::my();					
	}
	
	/**
	 * Determine the location of the Plugin
	 * 
	 * private helper function
	 * 
	 * @return string url to the directory of this plugin
	 */
	private function _getPluginPath()
	{
		return plugins_url('', __FILE__).'/';
	}
  
 	/**
	 * helper method for ajax_getContent
	 *
	 * @note not by me, no guarantees taken for this one for 
	 *       whatsoever (tk)
	 * 
	 * @param  string $string
     * @param  string $start
     * @param  string $end
	 * @return string
    */  
    private function _getStringBetween($string, $start, $end)
    {    	
		//Calculate the length of the start and end tags
        $lenStart = strlen($start);
        $lenEnd   = strlen($end);
        $startTag = strpos($string, $start);
        if ( false === $startTag ) 
        	return '';
        	        	
        //Calculate the start tag position and the first end tag position
        $strStart = $startTag + $lenStart;
        $strEnd   = strpos($string, $end);
        
        //Set a counter for the tags
        $tagCount = 0;
        
        //Use $test to see if there is another $start string after the first, but before the $strEnd position
        $test = strpos($string, $start, $strStart);
        
        //Use this while loop to check if there are other matching tags
        while( $test !== false && $strEnd > $test )
        {
            $tagCount ++;
            $next = $test + $lenStart;
            $test = strpos($string, $start, $next);
        }
        
        //If there is more than one tag, calculate the new end tag position
        if ( $tagCount ) 
        {
            for( $i = 0; $i < $tagCount; $i++ ) 
            {
                $strEnd = strpos($string, $end, $strEnd + $lenEnd);
            }
        }
                      
        $tmp = $strEnd - $strStart;
                
        return substr($string, $strStart, $tmp);
    }
	
	/**
	 * constructor
	 * 
	 * @return object as instance of self
	 */
	public function __construct()
	{
		# show the user if this plugin is not compatible to this wp setup
		if ( !$this->_isCompatible() )
		{
			add_action('admin_notices', array($this, 'admin_notices_version'));			
			return;
		}
		
		# ajax hooks
		add_action('wp_ajax_checkContent', array($this, 'ajax_checkContent'));         
		add_action('wp_ajax_getContent',   array($this, 'ajax_getContent'  ));

		# admin init
		add_action('admin_init', array($this, 'admin_init'));

		# hook scripts
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		        				
		# Add in the nice "settings" link to the plugins listing
		add_filter('plugin_action_links',   array($this, 'filter_plugin_actions'), 10, 2); 
		
		# user can change lingulab user and pass on own profile
		add_action('show_user_profile',     array($this, 'profile_edit'));                 		
		add_action('profile_update',        array($this, 'profile_update'));
	}
	
	/**
	 * add "Lingulab Box" to post and page edit screen
	 * 
	 * @-wp-hook admin_menu
	 * @return void
	 */
	public function add_box()
	{
		if ( function_exists('add_meta_box') )
		{
			foreach(array('post', 'page') as $page)
			{
				add_meta_box(
					'lingulab', // id of the <div> we'll add
					'LinguLab live', //title
					array($this, 'box_ajaxversion'), // callback function that will echo the box content
					$page,  // where to add the box: on "post", "page", or "link" page
					$context = 'side',
					'low' 
				);
			}
   		}	
	}
	
	/**
	 * admin_enqueue_scripts
	 * 
	 * @-wp-hook admin_enqueue_scripts
	 * @param string $hook_suffix
	 * @return void
	 */
	public function admin_enqueue_scripts($hook_suffix)
	{
		switch($hook_suffix)
		{
			case 'page.php':
			case 'page-new.php':
			case 'post.php':
			case 'post-new.php':
				//Add javascript enqeueing callback
				add_action('wp_print_scripts', array($this, 'admin_head_editor_javascript'));
		}
	}
	
	/**
	 * admin_head_editor
	 *
	 * Adds in the JavaScript and CSS for the linguLab
	 * Live admin box
	 * 
	 * @-wp-hook admin_head-... (diverse post and page)
	 * @return unknown_type
	 */
	public function admin_head_editor()
	{		
		
?>
<script type="text/javascript">
/* <![CDATA[ */
	/**
 	 * LinguLab Live Plugin Meta Box (javascript/jQuery)
 	 *
 	 * @author Tom Klingenberg
 	 */

 	// support wp versions without ajaxurl 
 	if ( !ajaxurl ) {
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
	}
 //]]>
</script>
<?php

	} // function

    /**
     * admin_head_editor_javascript
     * 
     * enqueue javascripts
     * 
     * @-wp-hook wp_print_scripts
     * @return void
     */
    public function admin_head_editor_javascript()
    {
    	//Enqueue editor javascript
		wp_enqueue_script('lingulab-editor-js',  $this->_getPluginPath() . 'js/editor.js'  );		
    }
    
    /**
     * admin_head_editor_styles
     * 
     * @-wp-hook admin_print_styles- diverse
     * @return unknown_type
     */
    public function admin_head_editor_styles()
    {
    	//Enqueue editor stylesheet
    	wp_enqueue_style ('lingulab-editor-css', $this->_getPluginPath() . 'css/editor.css');
    }
    
	/**
	 * admin_head_profile
	 *
	 * Adds in a CSS fragment for the tabs in the adminsitrative 
	 * interface on the current users profile page.
	 * 
	 * @-wp-hook admin_head-profile.php
	 * @return unknown_type
	 */
	public function admin_head_profile()
	{
		?>
<style type="text/css">
	/**
	 * LinguLab Live (CSS)
	 * 
	 * @see LinguLab Live (WordPress Plugin)
	 * @author Tom Klingenberg 
	 */
	 	.form-table .lingulab-description p,
	 	.form-table .lingulab-description ul {font-size: 1em; list-style: inside;}	 
	 	.form-table .lingulab-description p {margin-top:0;}
</style>
<?php

	} // function
	
	/**
	 * admin init
	 * 
	 * @-wp-hook admin_init
	 * @return void
	 */
	public function admin_init()
	{
		// hook add meta boxes
		$this->add_box();
		
		// register css & scripts for user-page 
		add_action('admin_head-profile.php', array($this, 'admin_head_profile'));
		
		// register css & scripts post/page edit/new
		foreach( array('post', 'post-new', 'page', 'page-new') as $hook_suffix )
		{
			add_action(sprintf('admin_head-%s.php', $hook_suffix), array($this, 'admin_head_editor'));
			add_action(sprintf('admin_print_styles-%s.php', $hook_suffix), array($this, 'admin_head_editor_styles'));	
		}
	}
	
	/**
	 * display wordpress version warning
	 * 
	 * @-wp-hook admin_notices
	 * @return void
	 */
	public function admin_notices_version()
	{
		$message = __('Ihre System unterstützt das LinguLab Live Plugin nicht. Bitte prüfen Sie die Systemanforderungen wenn Sie dieses Plugin benutzen wollen.');
		printf('<div class="error"><p>%s</p></div>', $message);
	}
	
	/**
	 * check content (via ajax request)
	 * 
	 * AJAX-based Check via Webservice and admin-ajax.php
	 * 
	 * @-wp-hook wp_ajax_checkContent
	 * @return void
	 */
	public function ajax_checkContent()
	{
		require 'inc/webserviceclient.php';
		
		$client = new lingulabLiveWebserviceClient();		
		$configs = $client->getConfigurations();
		
		# get input	
        $h1         = isset($_POST['h1']) ? '<h1>' . stripslashes($_POST['h1']) . '</h1>' : '';
		$h3         = isset($_POST['h3']) ? '<h3>' . stripslashes($_POST['h3']) . '</h3>' : '';
		$text       = $h1.$h3.(isset($_POST['text']) ? stripslashes($_POST['text']) : '');		
		$kw1        = isset($_POST['kw1']) ? stripslashes($_POST['kw1']) : '';
		$kw2        = isset($_POST['kw2']) ? stripslashes($_POST['kw2']) : '';
		$kw3        = isset($_POST['kw3']) ? stripslashes($_POST['kw3']) : '';
		$configType = isset($_POST['lingulab-mode']) ? stripslashes($_POST['lingulab-mode']) : $configs[0]["Id"];
		$lang = isset($_POST['lang']) ? stripslashes($_POST['lang']) : "de";
		
		$inputData = array(
			'Text'            => $text, 
			'ConfigurationId' => $configType,
			'LanguageKey'     => $lang,
			'SearchKeyword1'  => $kw1, 
			'SearchKeyword2'  => $kw2, 
			'SearchKeyword3'  => $kw3
		);
		
		# process input data
		$client = new lingulabLiveWebserviceClient();
		$result = $client->processText($inputData);

		# store resultId		
		$this->_getOptions()->resultId = $result['ResultId'];
		
		if($result['ErrorMessage']){
			printf('<div class="error">%s</div>', utf8_encode($result['ErrorMessage']));
		}else{
			printf('<div class="starshtml">%s</div>', utf8_encode($result['MeasureStarsHtml']));
			printf('<div class="showdetailsbutton"><a href="%s" class="button-primary" target="blank">%s</a></div>',  
			$result['LinkOnResultPage'], __('Ergebnissdetails anzeigen'));
		}
						
		exit();
	} // function ajax_checkContent 	
	
	/**
	 * get content (via ajax request)
	 * 
	 * AJAX-based getContent via Webservice and admin-ajax.php
	 * 
	 * @return void
	 */
    function ajax_getContent()
    {
    	require 'inc/webserviceclient.php';

    	# restore resultId
    	$resultId = $this->_getOptions()->resultId;
    	
    	# default values
    	$h1     = '';
		$h3		= '';
    	$text   = '';
    	$status = 0; // 0: error, 1: ok
    	    	
    	$client = new lingulabLiveWebserviceClient();
    	try {    	
			$result = $client->getUpdatedText($resultId);
						
			# parse result
			# @note not by me, especially the next two lines, should
			#       be refactored after webservice specs get better (tk)
			$h1     = $this->_getStringBetween($result['RawText'], "<h1>", "</h1>"); 
			$h3     = $this->_getStringBetween($result['RawText'], "<h3>", "</h3>"); 
        	$text   = $this->_delUnwantedTags($result['RawText']);
        	$status = 1;
        
    	} catch(lingulabLiveWebservice_Exception $e) {
    		$text = $e->getMessage();    		
    	}

    	# create and send response
        $ajaxResponse = new WP_Ajax_Response(array(
        	'what' => 'updatedContent',			
			'supplemental' => array(
				'h1'     => utf8_encode(html_entity_decode($h1)),
				'h3'     => utf8_encode(html_entity_decode($h3)),
				'text'   => utf8_encode($text),
        		'status' => $status,
       		),
		));
		
		$ajaxResponse->send(); // died here
    }    

	/**
	 * lingulab ajax version
	 * 
	 * echoes the lingulab Webservice-Box in the page and post section
	 * 
	 * @-wp-hook meta-box post/page
	 * @return void
	 */
	public function box_ajaxversion()
	{
		require_once 'inc/webserviceclient.php';
		
		$client = new lingulabLiveWebserviceClient();		
		
		try {
			$configs = $client->getConfigurations();
			$langs = $client->getLanguages();
?>
	<p>
		<strong>Bitte wählen Sie die entsprechende Textgattung:</strong>
	</p>
	<select name="lingulab-mode" id="lingulab-mode" class="dropdown mode">
<?php

	$i = 0;	
	foreach( $configs as $config )
	{
		$extra = (0 == ++$i) ? 'selected="selected" ' : ''; # select first entry
		$value = sprintf('%s,%s', $config['Id'], $config['IsKeywordsSupported']);
		$name  = $config['Name'];				
		printf('<option %s value="%s">%s</option>', $extra, htmlspecialchars($value), htmlspecialchars($name));		
	}
	
?>
	</select>
	
	<p>
		<strong>Bitte wählen Sie die Sprache des Textes:</strong>
	</p>
	
	<select name="lingulab-lang" id="lingulab-lang" class="dropdown mode">
<?php

	$i = 0;	
	foreach( $langs as $lang )
	{
		$extra = (0 == ++$i) ? 'selected="selected" ' : ''; # select first entry
		$value = $lang['LanguageKey'];
		$name  = $lang['Name'];				
		printf('<option %s value="%s">%s</option>', $extra, htmlspecialchars($value), htmlspecialchars($name));		
	}
	
?>
	</select>
	
<?php
	
		} catch(lingulabLiveWebserviceClient_Exception $e) {
			
?>
	<p>
		<strong>Kann Konfigurationen nicht laden. Bitte prüfen Sie <a 
			href="<?php echo admin_url('profile.php#lingulab-form'); ?>"
		>Ihre Einstellungen</a>. </strong>
	</p>
<?php

		} // try-catch-block
		
?>
	<div id="lingulab-keywordsdiv">
	
		<div class="trenner"></div>
		
		<p>
			<strong>Bitte geben Sie die für Ihren Text relevanten Keywords ein:</strong>
		</p>
		<label for="lingulab-kw1">Keyword 1:</label><input id="lingulab-kw1" name="lingulab-kw1" type="text" class="" />
        <label for="lingulab-kw2">Keyword 2:</label><input id="lingulab-kw2" name="lingulab-kw2" type="text" class="" />
        <label for="lingulab-kw3">Keyword 3:</label><input id="lingulab-kw3" name="lingulab-kw3" type="text" class="" />
        
 	</div>
 	
 	<div class="trenner"></div>
 	
 	<div>
		<img class="ajax-loading check" alt="" src="images/wpspin_light.gif" />
    	<input type="button" class="button-primary" id="lingulab-check" value="Text jetzt überprüfen" />
    </div>
		
	<div id="lingulab-resultdiv">
		<div class="trenner"></div>
		<p>
			<strong>Ergebnis:</strong>
		</p>		
  		<div class="done">Der Text wurde bisher noch nicht überprüft.</div>
  		
        <div id="lingulab-getcontentdiv">
        	<div class="trenner"></div>        	
        	<p>
        		<strong>Nachdem Sie Ihren Text überarbeitet haben, haben Sie hier die Möglichkeit 
        		Ihren Text hier zu aktualisieren:</strong>
        	</p>
        	<div>        	
        		<img class="ajax-loading refresh" alt="" src="images/wpspin_light.gif" />
            	<input type="button" class="button-primary" id="lingulab-refreshtext" value="Text aktualisieren" />
            </div>
		</div>
	</div>
<?php

	} // function
	
	/**
	 * filter_plugin_actions
	 * 
	 * Places in a link to the settings page in the plugins listing entry
	 * 
	 * @-wp-hook plugin_action_links
	 * @param  (array)  $links An array of links that are output in the listing
	 * @param  (string) $file The file that is currently in processing
	 * @return (array)  Array of links that are output in the listing.
	 */
	function filter_plugin_actions($links, $file)
	{
		static $this_plugin;
		
		if ( !$this_plugin ) {
			$this_plugin = plugin_basename(__FILE__);
		}
		
		//Make sure we are adding only for LinguLab Live
		if ( $file == $this_plugin )
		{
			$link = 'profile.php#lingulab-form';
			
			$styleOuter = 'padding-left:22px;';
			$styleInner = 'background:transparent url(images/menu.png) no-repeat scroll -245px -33px; height:28px; margin:-6px 0 0 -22px; position:absolute; width: 22px;';
						
			//Setup the link string
			$settings_link = sprintf('<a href="%s" style="%s"><span style="%s"></span>%s</a>', $link, $styleOuter, $styleInner, __('Settings'));
			
			//Add it to the end of the array to better integrate into the WP 2.8 plugins page
			$links[] = $settings_link;
		}
		
		return $links;
	}

	/**
	 * edit profile
	 * 
	 * lingulab options on users profile page (html output)
	 * 
	 * @-wp-hook edit_user_profile
	 * @return void
	 */
	public function profile_edit()
	{
		$options = $this->_getOptions();
		$value_user = $options->user;
		$value_pass = $options->pass; # currently unused

		// some informative output placed in the source:
		require_once 'inc/webserviceclient.php';
		$client  = new lingulabLiveWebserviceClient();
		$authkey = '';
		$code    = $client->getLoginResult($value_user, $value_pass, $authkey);
		printf('<!-- user: %s; pass (len): %d; login code: %d; key: %s -->', $value_user, strlen($value_pass), $code, $authkey);		
		
?>
<h3 id="lingulab-form">LinguLab Live</h3>
<table class="form-table">
<tr>
	<th><label for="lingulab-user"><?php _e('Benutzername'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
	<td><input type="text" name="lingulab-user" id="lingulab-user" value="<?php echo esc_attr($value_user) ?>" class="regular-text" /></td>
</tr>
<tr>
	<th><label for="lingulab-pass"><?php _e('Passwort') ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
	<td><input type="password" name="lingulab-pass" id="lingulab-pass" size="16" value="" /></td>	
</tr>
<tr>
	<th><label>So einfach funktioniert's</label></th>
	<td class="lingulab-description">
		<p>
			Gehen Sie auf <a target="_blank" href="http://live.lingulab.de/">live.lingulab.de</a> und klicken Sie auf <em>"Weiter zur kostenlosen 
			Registrierung"</em>. Direkt zur Anmeldung geht's mit <a target="_blank" href="http://live.lingulab.de/Public/Anmelden.aspx">diesem Link</a>.<br />
			Nach Ihrer Registrierung steht Ihnen der volle Funktionsumfang von LinguLab live 30 Tage kostenlos zur Verfügung.<br />
			Danach wird Ihr Account in einen kostenlosen, werbefinanzierten Basic-Zugang umgewandelt.
		</p>
		<p>
			Sie können jederzeit über die LinguLab Website Premium-Leistungen auf monatlicher Basis erwerben.<br />
			Eine Übersicht unserer Leistungen und Preise finden Sie <a href="http://live.lingulab.de/Public/Prices.aspx">hier</a>.		
		</p>
		<p>
			Hilfreiche Tipps zum Thema Textqualität und zur Nutzung von LinguLab Live finden Sie hier:
		</p>
		<ul>
			<li><a target="_blank" href="http://textkritik.lingulab.de/category/screencasts/">Video-Anleitungen / Screencasts</a></li>
			<li><a target="_blank" href="http://textkritik.lingulab.de/category/so-funktionierts/">So funktioniert's / Benutzerhandbuch</a></li>
		</ul>
		<p>
			Wir wünschen Ihnen viel Spaß mit LinguLab live.
		</p>
	</td>	
</tr>
</table>
<?php

	} // function profile_edit
	
	/**
	 * profile_update
	 * 
	 * @-wp-hook profile_update
	 * @return void
	 */
	public function profile_update()
	{
		if ( isset($_POST['lingulab-user']) && isset($_POST['lingulab-pass']) )
		{		
			$value_user = stripslashes($_POST['lingulab-user']);
			$value_pass = stripslashes($_POST['lingulab-pass']);			
		
			$options = $this->_getOptions();
						
			$options->user = $value_user;
						
			if ( strlen($value_pass) && $options->pass != $value_pass ) {
				$options->pass = $value_pass;
			}
		}
	} // function profile_update
        	
} // class
