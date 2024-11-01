<?php
	/* ViralURL WordPress Plugin 1.2.7 */

	// Plugin menu
	function vpurl_menu()
	{
		add_menu_page('ViralURL WordPress Plugin - Setup', 'ViralURL WP Plugin', 'administrator', basename(__FILE__), 'vpurl_options', 'http://viralurl.com/favicon.ico');
		add_submenu_page(basename(__FILE__), 'ViralURL WordPress Plugin - Setup', 'Setup', 'administrator', basename(__FILE__), 'vpurl_options');
		add_submenu_page(basename(__FILE__), 'ViralURL WordPress Plugin - Add Keyword Phrase', 'Add Keyword Phrase', 'administrator', basename(__FILE__).'modify', 'vpurl_modify');
		add_submenu_page(basename(__FILE__), 'ViralURL WordPress Plugin - Show Keyword Phrases', 'Show Keyword Phrases', 'administrator', basename(__FILE__).'show', 'vpurl_show');
		// add_management_page('ViralURL WordPress Plugin - Process', 'Process', 'administrator', basename(__FILE__).'process', 'vpurl_process');
	}
	
	add_action('admin_menu', 'vpurl_menu');
	
	// Custom heading
	function admin_register_head()
	{
		$siteurl = get_option('siteurl').'/wp-content/plugins/'.basename(dirname(__FILE__));
				
	?>
		<link rel='stylesheet' type='text/css' href='<?= $siteurl ?>/css/vpurl.css' />
		<script src='<?= $siteurl ?>/javascript/jquery.validate.min.js'></script>
	<?
	}
	
	add_action('admin_head', 'admin_register_head');
	
	//
	// UTILITY FUNCTIONS
	//
	
	// Function for reverse string position searching.
	// Required due to PHP < 5.0
	//	http://www.php.net/manual/en/function.strrpos.php#92158
	function backwardStrpos($haystack, $needle, $offset = 0){
		$length = strlen($haystack);
		$offset = ($offset > 0)?($length - $offset):abs($offset);
		$pos = strpos(strrev($haystack), strrev($needle), $offset);
		return ($pos === false)?false:( $length - $pos - strlen($needle) );
	}
	
	// Display a standard checkbox
	function vpurl_checkbox($option_name)
	{
		if (get_option($option_name))
			return ' checked = "checked"';
		else return '';
	}

	// Display a standard radio
	function vpurl_radio($option_name)
	{
		if (get_option($option_name))
			return ' checked = "checked"';
		else return '';
	}

	// Display a standard radio 2
	function vpurl_radio2($option_name)
	{
		if (get_option($option_name))
			return '';
		else return ' checked = "checked"';
	}
	
	// Display an admin notice with class
	function admin_notice($message, $class = 'updated')
	{
	?>
		<div class = '<?= $class ?> fade'>
			<p><strong><?= $message ?></strong></p>
		</div>
	<?
	}
	
	/**
	 * Unique Keyword
	 *  Returns true if keyword is unique
	 */
	function unique_keyword($keyword_name, $id=''){
		global $wpdb;

		$table_name = $wpdb->prefix . "vpurl_keywords";
		$keyword_name = $wpdb->escape($keyword_name);
		
		// Check against the name of the keyword associated with a provided ID
		if (!empty($id)){
			$current_keyword = $wpdb->get_var($wpdb->prepare("SELECT keyword_name FROM $table_name WHERE id=$id"));
			
			if (strtolower($keyword_name) == strtolower($current_keyword))
				return true;
		}
		
		if($wpdb->get_results("SELECT * FROM $table_name WHERE lower(keyword_name)=lower('$keyword_name')")){
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * ViralURL Cloak
	 * 	Returns a viralurl cloaked affiliate link
	 */
	function vpurl_cloak($url){
		
		$folder = get_option('vpurl_username');
		$password = get_option('vpurl_password');
		$adbar = get_option('vpurl_adbar_bottom') ? 'bottom' : 'top';
		$url = urlencode($url);
		list($x, $x, $domain, $x, $x) = split('/', getenv("HTTP_REFERER")); // e.g. http://vur.me/wp-admin/admin.php
		if ($domain != "") { $domain = " at ".$domain; }
		$ref = urlencode($domain);
		$api = "/addlink.php?api=1&short=1&folder=$folder&pw=$password&adbar=$adbar&url=$url&notes=Setup%20with%20ViralURL%20WordPress%20Plugin".$ref;
		
		// API request
		$fp = fsockopen("viralurl.com", 80, $errno, $errstr, 3);
		$response = '';
		
		if (!$fp) {
			vpurl_fatal_error("Socket connection error: $errstr ($errno)<br />\n");
			return;
		} else {
			$out = "GET $api HTTP/1.1\r\n";
			$out .= "Host: viralurl.com\r\n";
			$out .= "Connection: Close\r\n\r\n";
			fwrite($fp, $out);
			while (!feof($fp)) {
				$response .= fgets($fp, 128);
			}
			fclose($fp);
		}
		
		$response = substr($response, strpos($response, "\r\n\r\n")+1);

		$response = explode('|', $response);
		if ( $response[0] == 0 ) {
			if ( $response[1] == 'Login' )
				$response[1] = 'Invalid login';
			vpurl_fatal_error("ViralURL API error: ".$response[1]);
			return;
		}
		list($response[1], $drop) = split("[\n\r]+", $response[1]);
		return $response[1];
	}
	
	function vpurl_fatal_error($message) { 
		$message = urlencode($message);
		
		// Redirect on error
		wp_redirect("admin.php?page=".basename(__FILE__)."modify&message=$message&message_class=error");
		exit();
	}
	
	
	//
	// CONFIGURATION FORMS
	//
	
	/**
	 *
	 * VIRALURL PLUGIN CONFIGURATION
	 *	Global plugin configuration
	 *	
	 */
	function vpurl_options()
	{		
		if (array_key_exists('vpurl_hidden_submit', $_POST))
		{
			update_option('vpurl_enable', $_POST['enable']);
			update_option('vpurl_keyword_replacements', $_POST['keyword_replacements']);
			update_option('vpurl_page_replacements', $_POST['page_replacements']);
			update_option('vpurl_username', $_POST['username']);
			update_option('vpurl_password', $_POST['password']);
			update_option('vpurl_show_ad', $_POST['show_ad']);
			update_option('vpurl_adbar_bottom', $_POST['adbar_loc']);
			update_option('vpurl_open_window', $_POST['new_window']);
			update_option('vpurl_no_follow', $_POST['no_follow']);
			update_option('vpurl_css_class', $_POST['css_class']);
		?>
			<div class = 'updated'>
            	<p><strong>Options Saved</strong></p>
            </div>
		<?	
		}
		
		
		$keyword_replacements = get_option('vpurl_keyword_replacements');
		$page_replacements = get_option('vpurl_page_replacements');
		$username = get_option('vpurl_username');
		$password = get_option('vpurl_password');
		$cssClass = get_option('vpurl_css_class');

		$enable = vpurl_checkbox('vpurl_enable');
		$showAd = vpurl_radio('vpurl_show_ad');
		$showAd2 = vpurl_radio2('vpurl_show_ad');
		$adbar_loc = vpurl_radio('vpurl_adbar_bottom');
		$adbar_loc2 = vpurl_radio2('vpurl_adbar_bottom');
		$newWindow = vpurl_radio('vpurl_open_window');
		$newWindow2 = vpurl_radio2('vpurl_open_window');
		$noFollow = vpurl_radio('vpurl_no_follow');
		$noFollow2 = vpurl_radio2('vpurl_no_follow');
		
		
		//
		//	Plugin Configuration Form
		//
		
		?>
		<div class = 'wrap'>
			<div id = 'icon-options-general' class = 'icon32'><br /></div>
			<h2>ViralURL WordPress Plugin Version 1.2.7 - Settings</h2>
			<p><h4><b>What <a href="http://viralurl.com" target="_blank">ViralURL</a> does for you now automatically via the <a href="http://viralplugin.com/wordpress" target="_blank">ViralURL WordPress Plugin</a>...</b></h4></p>
			<p><b>* Monetize your blog</b> - monitize easily any kind of keyword phrases all over your blog. Setup once and benefit from past, current and future posts!</p>
			<p><b>* Cloak, protect, shorten and track your links</b> - uncrackable, short affiliate links! Affiliate commissions thieves now have something to fear! Prevent them from stealing your affiliate commissions and get the full rewards of your promotional efforts.</p>
			<p><b>* Listbuild on auto-pilot</b> - build a massive downline virally! Build a huge downline with no effort and email it every few days to build a strong relationship with them and promote any program you want. List building has never been so easy!</p>
			<p><b>* Advertise your website to a targeted audience</b> - guaranteed traffic increase! Promote your business with Viralbar text ads, banner ads and guaranteed visits! Increase the quality and quantity of your web traffic with our easy-to-use advertising tools.</p>
			<p><b>* Send broadcast emails to our members</b> - cheap quality lead generation! Optional upgrade to email from 3,000 to 6,000 fellow marketers every 3 days with our system mailer & generate qualified leads at a very low cost. A must-have for any internet or network marketer!</p>
			<p><b>* Make huge commissions on upgrades</b> - boost your online income! Earn above industry-average commissions on upgrades and speed up your online income! No matter if you promote the system or not, you can earn commissions virally!</p>
			<p><br>Please watch <a href="http://colinklinkert.com" target="_blank">Colin Klinkert</a>'s tutorial video...</p>
			<p align="center"><object width="640" height="385"><param name="movie" value="http://www.youtube.com/v/XZ8dmdJvgk8&amp;hl=en_US&amp;fs=1?rel=0&amp;hd=1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/XZ8dmdJvgk8&amp;hl=en_US&amp;fs=1?rel=0&amp;hd=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="640" height="385"></embed></object></p>
			<p><br>Please watch <a href="http://frankbauer.name" target="_blank">Frank Bauer</a>'s tutorial video...</p>
			<p align="center"><embed src="http://viralnetworks.com/mediaplayer.swf?v22" width="480" height="360" allowfullscreen="true" allowscriptaccess="always" wmode="opaque"  flashvars="&autostart=false&bufferlength=5&file=http%3A%2F%2Fd2el4marpcphqf.cloudfront.net%2F36550.flv&height=360&image=http%3A%2F%2Fd2el4marpcphqf.cloudfront.net%2F36550.480x360.jpg&width=480&displaywidth=480&backcolor=0x000000&frontcolor=0xffffff&lightcolor=0xffcc00&screencolor=0x000000&rootpath=http%3A%2F%2Fviralnetworks.com%2Fplayer-api%2F&linktarget=_blank&logolink=http%3A%2F%2Fviralnetworks.com%2Fa%2F28&videoid=36550&" /></p>
			<p><br>To get started right away, simply fill out the form below...</p>
    	    <form name = 'vpurl_optionsForm' method = 'post' action = 'admin.php?page=<?=basename(__FILE__) ?>'>
        		<input type = 'hidden' name = 'vpurl_hidden_submit' value = 'Hello' />
                <table class = 'form-table'>
                	<tbody>
                    	<tr>
                        	<th scope = 'row'><p>Enable Plugin:<br><font size='1'>(Needs to be ticked)</font></p></th>
                            <td><input type = 'checkbox' name = 'enable' value = '1' <?= $enable ?> /></td>
                        </tr>
            			<tr>
                        	<th scope = 'row'><p>Maximum Replacements / Keyword:<br><font size='1'>(Recommended value: 100)</font></p></th>
                            <td><input type = 'text' name = 'keyword_replacements' value = "<?= $keyword_replacements ?>" class = 'regular-text' /></td>
                        </tr>
                		<tr>
                        	<th scope = 'row'><p>Maximum Replacements / Page:<br><font size='1'>(Recommended value: 3)</font></p></th>
                            <td><input type = 'text' name = 'page_replacements' value = "<?= $page_replacements ?>" class = 'regular-text' /></td>
                        </tr>
                		<tr>
                        	<th scope = 'row'><p>Your ViralURL Folder Name:<br><font size='1'>(<a href="http://viralurl.com" target="_blank">Click here to get your own free ViralURL account today!</a>)</font></p></th>
                            <td><input type = 'text' name = 'username' value = "<?= $username ?>" class = 'regular-text' /></td>
                        </tr>
                        <tr>
                        	<th scope = 'row'><p>Your ViralURL Password:</p></th>
                            <td><input type = 'password' name = 'password' value = "<?= $password ?>" class = 'regular-text' /></td>
                        </tr>
                        <tr>
                        	<th scope = 'row'><p>Show Ad on Viralbar:<br><font size='1'>(Recommended to earn credits)</font></p></th>
                            <td><input type = 'radio' name = 'show_ad' value = '1' <?=$showAd ?> /> Yes or <input type = 'radio' name = 'show_ad' value='' <?=$showAd2 ?> /> No</td>
                        </tr>
                        <tr>
                        	<th scope = 'row'><p>Viralbar Location:</p></th>
                            <td><input type = 'radio' name = 'adbar_loc' value='' <?=$adbar_loc2 ?> /> Top or <input type = 'radio' name = 'adbar_loc' value='1' <?=$adbar_loc ?> /> Bottom</td>
                        </tr>
                        <tr>
                        	<th scope = 'row'><p>Open links in New Window:</p></th>
                            <td><input type = 'radio' name = 'new_window' value = '1' <?=$newWindow ?> /> Yes or <input type = 'radio' name = 'new_window' value='' <?=$newWindow2 ?> /> No</td>
                        </tr>
                        <tr>
                        	<th scope = 'row'><p>NoFollow by Default:</p></th>
                            <td><input type = 'radio' name = 'no_follow' value = '1' <?=$noFollow ?> /> Yes or <input type = 'radio' name = 'no_follow' value='' <?=$noFollow2 ?> /> No</td></td>
                        </tr>
                        <tr>
                        	<th scope = 'row'><p>CSS Class for Links:<br><font size='1'>(Optional)</font></p></th>
                            <td><input type = 'text' name = 'css_class' value = "<?= $cssClass ?>" /></td>
                        </tr>
                    </tbody>
                </table>
                <p class = 'submit'>
                	<input type = 'submit' name = 'Submit' value = "Update Options" class="button-primary" />
                </p>
            </form>
		</div>
		<p>To your success,</p>
		<table border="0" cellspacing="0" cellpadding="0">
			<tbody>
				<tr>
					<td scope="col"><img src="http://viralurl.com/images/signature.jpg" border="0" alt="Colin Klinkert" width="211" height="39" /></td>
					<td valign="top" scope="col"></td>
					<td style="text-align: center;" valign="top" scope="col"><img src="http://viralurl.com/images/signature.gif" alt="Frank Bauer" width="194" height="39" /></td>
				</tr>
				<tr>
					<td align="center" scope="col"><span style="font-size: small;"><span><strong>Colin Klinkert</strong><br>Director of <a href="http://colinklinkert.com" target="_blank">ColinKlinkert.com</a></span></span></td>
					<td valign="top" scope="col"><span style="font-family: verdana; font-size: small;"><span style="font-family: Georgia; font-size: small;"><strong>&amp;</strong> </span> </span></td>
					<td align="center" valign="top" scope="col"><span style="font-size: small;"><span><strong>Frank Bauer</strong><br>Director of <a href="http://add2it.com" target="_blank">Add2it.com Marketing Pty Ltd</a></span></span></td>
				</tr>
				<tr>
					<td align="center" scope="col"><img src="http://viralurl.com/images/ColinKlinkert.jpg" border="0" alt="Colin Klinkert" width="96" height="114" /></td>
					<td align="center" scope="col"></td>
					<td align="center" scope="col"><img src="http://viralurl.com/images/FrankBauer.jpg" border="0" alt="Frank Bauer" width="96" height="114" /></td>
				</tr>
			</tbody>
		</table>
		<p align="center"><br><a href="http://viralplugin.com" target="_blank"><img src="http://viralplugin.com/images/click-here-for-ff.png" alt="ViralPlugin for FireFox" width="303" height="68" border="0" /></a></p>
		<?php
	}
	
	
	/**
	 *
	 * MODIFY KEYWORD
	 *	Form to modify Keywords
	 *	
	 */
	function vpurl_modify()
	{
		global $wpdb;

		switch($_REQUEST['keyword_action']){
			case 'edit':
				$heading = 'ViralURL WordPress Plugin Version 1.2.7 - Edit Keyword Phrase';
				$action = 'edit';
				
				$table_name = $wpdb->prefix . "vpurl_keywords";
				$id = $wpdb->escape($_GET['id']);
				$values = $wpdb->get_row("SELECT * FROM $table_name WHERE id = " . $id);
				
				$newWindow = ($values->new_window == 1) ? ' checked = "checked"' : '';
				$newWindow2 = ($values->new_window == 0) ? ' checked = "checked"' : '';
				$noFollow = ($values->no_follow == 1) ? ' checked = "checked"' : '';
				$noFollow2 = ($values->no_follow == 0) ? ' checked = "checked"' : '';
				
				break;
			
			case 'add':
			default:
				$heading = 'ViralURL WordPress Plugin Version 1.2.7 - Add Keyword Phrase';
				$action = 'add';
				
				$values = '';
				
				$newWindow = vpurl_radio('vpurl_open_window');
				$newWindow2 = vpurl_radio2('vpurl_open_window');
				$noFollow = vpurl_radio('vpurl_no_follow');
				$noFollow2 = vpurl_radio2('vpurl_no_follow');
				
				break;
		}
		
		//
		// Modify Keywords Form
		//
		
	?>
		<script>
		jQuery(document).ready(function() {
			var validator = jQuery("#vpurl_optionsForm").validate({ 
				rules: { 
					keyword_name: {
						required: true,
						remote: ajaxurl+'?action=vpurl_ajax_keyword&id=<?= $id ?>'
					},
					affiliate_link: {
						required: true,
						url: true
					}
				},
				messages: {
					keyword_name: {
						remote: 'This keyword phrase must be unique.'
					}
				}
				
			});
		});
		</script>
		
		<? // MESSAGE OUTPUT
			if (isset($_REQUEST['message'])){
				admin_notice($_REQUEST['message'], $_REQUEST['message_class']);
			}
		?>
		<div class = 'wrap'>
			<div id = 'icon-options-general' class = 'icon32'>
				<br />
			</div>
			<h2><?= $heading ?></h2>
		
			<form class='vpurl_optionsForm' id = 'vpurl_optionsForm' name = 'vpurl_optionsForm' method = 'post'
				  action = 'admin-ajax.php?action=vpurl_process&keyword_action=<?= $action ?>&id=<?= $id ?>'>
				
				<table class = 'form-table'>
                	<tbody>
                    	<tr>
							<th scope = 'row'><label class='regular-text' for='keyword_name'>Keyword Phrase:</label></th>
							<td><input type='text' name='keyword_name' class='regular-text' value='<?= $values->keyword_name ?>' /></td>
						</tr>
						<tr>
							<th scope = 'row'><label class='regular-text' for='affiliate_link'>Affiliate Link:<br><font size='1'>(incl. http://)</font></label>
							<td><input type = 'text' name = 'affiliate_link' class = 'regular-text' value = '<?= $values->affiliate_link ?>' />
							<input type = 'hidden' name = 'org_affiliate_link' value = '<?= $values->affiliate_link ?>' />
							<input type = 'hidden' name = 'cloaked_link' value = '<?= $values->cloaked_link ?>' /></td>
						</tr>
						<tr>
							<th scope = 'row'><label class='regular-text' for='statusbar'>Statusbar Text:</label></th>
							<td><input type = 'text' name = 'statusbar' class = 'regular-text' value = '<?= $values->statusbar ?>' /></td>
						</tr>
						<tr>
							<th scope = 'row'><label class='regular-text' for='replacement_count'>Max Replacement Count:<br><font size='1'>(0 = No replacement!)</font></label>
							<td><input type = 'text' name = 'replacement_count' class = 'regular-text' value = '<?= $values->replacement_count ?>' /></td>
						</tr>
						<tr>
							<th scope = 'row'><label class='regular-text' for='weight'>Weight:</label></th>
							<td><input type = 'text' name = 'weight' class = 'regular-text' value = '<?= $values->weight ?>' /></td>
						</tr>
						<tr>
							<th scope = 'row'><label class='regular-text' for='new_window'>Open Links in New Window:</label></th>
							<td><input class='checkbox' type = 'radio' name = 'new_window' value = '1' <?=$newWindow ?> /> Yes or <input class='checkbox' type = 'radio' name = 'new_window' value = '' <?=$newWindow2 ?> /> No</td>
						</tr>
						<tr>
							<th scope = 'row'><label class='regular-text' for='no_follow'>Make Links NoFollow:</label></th>
							<td><input class='checkbox' type = 'radio' name = 'no_follow' value = '1' <?=$noFollow ?> /> Yes or <input class='checkbox' type = 'radio' name = 'no_follow' value = '' <?=$noFollow2 ?> /> No</td>
                        </tr>
                    </tbody>
                </table>
                <p class = 'submit'>
                	<input type = 'submit' name = 'Submit' value = "Save Keyword Phrase" class="button-primary" />
                </p>
			</form>
		</div>
	<?
	}
	
	
	/**
	 *
	 * SHOW KEYWORDS FUNCTION
	 * 	Formated list of keywords
	 *
	 */
	function vpurl_show()
	{
		global $wpdb;
		
		$table_name = $wpdb->prefix . "vpurl_keywords";
		
		$order_by = $_GET[order_by];
		if ($order_by != "") { $sort_by = " ORDER BY ".$order_by; } else { $sort_by = ""; }
		$asc = $_GET[asc];
		if ($asc == "") { $asc = "ASC"; }
		if ($sort_by != "") { $sort_by = $sort_by." ".$asc; }
		if ($asc == "ASC") { $opo_asc = "DESC"; } else { $opo_asc = "ASC"; }
		
		// Keywords display here
		$keywords = $wpdb->get_results("SELECT * FROM $table_name".$sort_by);
		
		?>
		<div class = 'wrap'>
			<div id = 'icon-options-general' class = 'icon32'>
				<br />
			</div>
			<h2>ViralURL WordPress Plugin Version 1.2.7 - View Keyword Phrases</h2>
			
			<?
				// MESSAGE OUTPUT
				if (isset($_REQUEST['message'])){
					admin_notice($_REQUEST['message'], $_REQUEST['message_class']);
				}
				
				//
				// Show Keywords Table
				//
			?>

			<table class = 'widefat post'>
				<thead>
					<tr>
						<th><a href="admin.php?page=<?=basename(__FILE__) ?>show&order_by=keyword_name&asc=<?php if ($order_by == "keyword_name") { echo $opo_asc; } else { echo "ASC"; } ?>">Keywords</a></th>
						<th><a href="admin.php?page=<?=basename(__FILE__) ?>show&order_by=affiliate_link&asc=<?php if ($order_by == "affiliate_link") { echo $opo_asc; } else { echo "ASC"; } ?>">Affiliate Link</a></th>
						<th><a href="admin.php?page=<?=basename(__FILE__) ?>show&order_by=statusbar&asc=<?php if ($order_by == "statusbar") { echo $opo_asc; } else { echo "ASC"; } ?>">Status Bar</a></th>
						<th style='width: 9em'>Weight/Count</th>
						<th>Commands</th>
					</tr>
				</thead>
				<tbody>
			
			<?
				$vu_folder = get_option('vpurl_username');
				$vu_password = md5(get_option('vpurl_password'));
				foreach ($keywords as $i=>$keyword)
				{
					$alternate = ($i % 2) ? 'alternate' : '';
					
					// Trunkating of link
					$affiliate_link_short = substr($keyword->affiliate_link, 0, 42);
					if (strlen($keyword->affiliate_link) > 42)
						$affiliate_link_short .= '...';
						list($x, $x, $x, $folder, $key) = split('/', $keyword->cloaked_link); // e.g. http://vur.me/s/P62
				?>
					<tr class = "author-self status-publish iedit <?= $alternate ?>">
						<td><?= $keyword->keyword_name ?></td>
						<td>
							<a href='<?= $keyword->affiliate_link ?>'><?= $affiliate_link_short ?></a> 
							(<a href='<?= $keyword->cloaked_link ?>'>link</a>)
						</td>
						<td><?= $keyword->statusbar ?></td>
						<td style='text-align: center'><?= $keyword->weight ?> / <?= $keyword->replacement_count ?> </td>
						<td>
							<a href = "http://viralurl.com/gostats.php?folder=<?=$vu_folder ?>&f=<?=$folder ?>&k=<?=$key ?>&pw=<?=$vu_password ?>" target="_blank">Stats</a> | 
							<a href = "admin.php?page=<?=basename(__FILE__) ?>modify&keyword_action=edit&id=<?= $keyword->id ?>">Edit</a> | 
							<a href = "admin-ajax.php?action=vpurl_process&keyword_action=delete&id=<?= $keyword->id ?>">Delete</a>
						</td>
					</tr>
				<?
				}
			?>
				</tbody>
			</table>
			<br><a href = "http://viralurl.com/gostats.php?folder=<?=$vu_folder ?>&pw=<?=$vu_password ?>" target="_blank">Visit ViralURL Current URL's Section</a> | <a href = "http://viralurl.com/gomailer.php?folder=<?=$vu_folder ?>&pw=<?=$vu_password ?>" target="_blank">Visit ViralURL System Mailer Section (Gold or higher members only)</a>
		</div>
		<?
	}
	
	
	
	//
	// AJAX FUNCTIONS
	//
	
	/**
	 * AJAX Keyword
	 * 	Prints true if keyword is unique.
	 *
	 */
	function vpurl_ajax_keyword(){
	
		$keyword_name = $_REQUEST['keyword_name'];
		$id = $_REQUEST['id'];
		
		if (unique_keyword($keyword_name, $id))
			echo 'true';
		else
			echo 'false';
		
		exit;		
	}
	
	add_action('wp_ajax_vpurl_ajax_keyword', 'vpurl_ajax_keyword');
	
	
	/**
	 *
	 * PROCESS
	 * 	Database queries for modifying keywords
	 * 	
	 */
	function vpurl_process()
	{
		
		global $wpdb;
		
		// Input initialization
		$table_name = $wpdb->prefix . "vpurl_keywords";
		
		$keyword_name = $wpdb->escape($_POST['keyword_name']);
		$affiliate_link = $wpdb->escape($_POST['affiliate_link']);
		$org_affiliate_link = $wpdb->escape($_POST['org_affiliate_link']);
		$cloaked_link = $wpdb->escape($_POST['cloaked_link']);
		
		$statusbar = $_POST['statusbar'] != "" ? $wpdb->escape($_POST['statusbar']) : $keyword_name;
		$replacement_count = $_POST['replacement_count'] < 0 ? 0 : $wpdb->escape($_POST['replacement_count']);
		$weight = $_POST['weight'] < 1 ? 1 : $wpdb->escape($_POST['weight']);
		$new_window = $_POST['new_window'] == 1 ? 1 : 0;
		$no_follow = $_POST['no_follow'] == 1 ? 1 : 0;
		
		
		//
		// Process Actions
		//
		switch($_GET['keyword_action']){
			case 'add':
				// Validation
				if (!unique_keyword($keyword_name) or empty($keyword_name))
					$error_message = urlencode('Keyword Phrase must be unique.');
				if (empty($affiliate_link))
					$error_message = urlencode('Must enter a valid affiliate link.');
				// Redirect on error
				if (isset($error_message)){
					wp_redirect("admin.php?page=".basename(__FILE__)."modify&message=$error_message&message_class=error");
					exit();
				}
				// Generate cloaked URL
				$cloaked_link = vpurl_cloak($affiliate_link);
				// Insert if valid
				$sql = "INSERT INTO $table_name
						(id, keyword_name, affiliate_link, cloaked_link, statusbar, replacement_count, weight, new_window, no_follow)
						VALUES (null, '$keyword_name', '$affiliate_link', '$cloaked_link', '$statusbar',
						'$replacement_count', '$weight', '$new_window', '$no_follow')";
				if ($wpdb->query($sql) !== false){
					$message = urlencode('Keyword Phrase added succesfully. <a href=admin.php?page='.basename(__FILE__).'modify>Click here to add another Keyword Phrase!</a>');
					wp_redirect("admin.php?page=".basename(__FILE__)."show&message=$message&message_class=updated");
				} else {
					$error_message = urlencode('Error adding entry.');
					wp_redirect("admin.php?page=".basename(__FILE__)."show&message=$error_message&message_class=error");
					exit();
				}
				break;
			case 'edit':
				$id = $wpdb->escape($_GET['id']);
				$current_keyword = $wpdb->get_var($wpdb->prepare("SELECT keyword_name FROM $table_name
																   WHERE id=$id"));
				// Validation
				if ((!unique_keyword($keyword_name) or empty($keyword_name)) and ($keyword_name != $current_keyword))
					$error_message = urlencode('Keyword Phrase must be unique.');
				if (empty($affiliate_link))
					$error_message = urlencode('Must enter a valid affiliate link.');
				// Redirect on error
				if (isset($error_message)){
					wp_redirect("admin.php?page=".basename(__FILE__)."modify&keyword_action=edit&message=$error_message"
								. "&message_class=error&id=$id");
					exit();
				}
				if ($affiliate_link != $org_affiliate_link) {
					// Generate cloaked URL
					$cloaked_link = vpurl_cloak($affiliate_link);
				}
				// Update
				$sql = "UPDATE $table_name
						SET keyword_name='$keyword_name', affiliate_link='$affiliate_link',
							statusbar='$statusbar', replacement_count= '$replacement_count',
							weight='$weight', new_window='$new_window', no_follow = '$no_follow',
							cloaked_link='$cloaked_link'
						WHERE id = '$id'";
				if ($wpdb->query($sql) !== false){
					$message = urlencode('Keyword Phrase edited succesfully.');
					wp_redirect("admin.php?page=".basename(__FILE__)."show&message=$message&message_class=updated");
				} else {
					$error_message = urlencode('Error editing entry.');
					wp_redirect("admin.php?page=".basename(__FILE__)."show&message=$error_message&message_class=error");
					exit();
				}
				break;
			case 'delete':
				$id = $wpdb->escape($_GET['id']);
				// TODO: Lookinto why DELETE queries with wpdb always return false
				$sql = $wpdb->query("DELETE FROM $table_name WHERE id='$id'");
				$message = urlencode('Keyword Phrase deleted.');
				wp_redirect("admin.php?page=".basename(__FILE__)."show&message=$message&message_class=updated");
				break;
		}
	}
	add_action('wp_ajax_vpurl_process', 'vpurl_process');

	//
	// CONTENT ALTERATION
	//	
	function vpurl_inject_keywords($content){
		global $wpdb;
		if (!get_option('vpurl_enable') ) {
			return $content;
		}
		$keywords = $wpdb->get_results("SELECT * FROM wp_vpurl_keywords");
		foreach ( $keywords as $i => $k )
			$keywords[$i]->weight = 1;
		// Replacement variables initialized
		$replacement_count = 0;
		$replacement_count_max = get_option('vpurl_keyword_replacements');
		$weight_sum = 0;
		// Find keywords in post
		$found_keywords = array();
		$lcontent = strtolower($content);
		foreach ($keywords as $keyword) {
			if ($position = strpos($lcontent, strtolower($keyword->keyword_name))) {
				$found_keywords[$keyword->keyword_name] = array($keyword, 'position'=>$position);
				$weight_sum += $keyword->weight;	// Used for weighted random calculation
			}
		}
		uasort($found_keywords, create_function(
									'$keyword1, $keyword2',
									'return ($keyword1[0]->weight > $keyword2[0]->weight);')
		);
		do {		
			$chosen_keyword = null;
			$weight_location = rand(0, $weight_sum-1);
			// Select a keyword at random from those that are in the post
			foreach(array_reverse($found_keywords) as $keyword) {
				// Weighted random algorithm
				// 1. Pick a random number less than the total 
				// 2. At each position of the array, subtract the weight at that position 
				// 3. When the result is negative, return that position
				//	http://www.perlmonks.org/?node_id=242751
				$weight_location -= $keyword[0]->weight;
				if ($weight_location < 0) {
					$chosen_keyword = $keyword;
					$weight_sum -= $keyword[0]->weight;
					// Keywords are not selected more than once
					unset($found_keywords[$keyword[0]->keyword_name]);
					break;
				}
			}
			// Search and Replace
			if($chosen_keyword) {	
				$offset = $keyword['position'];
				$keyword['position'] = array();
				// Find exact occurence of keyword within content
				while($position = strpos($lcontent, strtolower($keyword[0]->keyword_name), $offset)){		
					if (location_is_valid($position, $lcontent, strtolower($keyword[0]->keyword_name))){
						$keyword['position'][] = $position;
					}
					$offset = $position + strlen($keyword[0]->keyword_name);
				}
				// Handle unlimited local replacement counts, make sure we don't replace more instances than there actually are
				if ( $keyword[0]->replacement_count == 0 || $keyword[0]->replacement_count > count($keyword['position']) ){
					$keyword[0]->replacement_count = count($keyword['position']);
				}
				// Prevent local replacements maximum from overriding global limits
				if ($replacement_count_max && $replacement_count + $keyword[0]->replacement_count > $replacement_count_max){
					$keyword[0]->replacement_count = $replacement_count_max - $replacement_count;
				}
				if (get_option('vpurl_page_replacements') && $keyword[0]->replacement_count > get_option('vpurl_page_replacements')){
					$keyword[0]->replacement_count = get_option('vpurl_page_replacements');
				}
				// Placeholder value for maintaining characters during replacement
				$placeholder = '$' . str_repeat('_', strlen($keyword[0]->keyword_name)-1);
				// Randomize positions that keywords will appear at 
				shuffle($keyword['position']);
				// Replace randomly selected occurence of keyword with placeholder
				for($i=0; $i < $keyword[0]->replacement_count; $i++) {
					$content = substr_replace($content, $placeholder,
											  $keyword['position'][$i], strlen($placeholder));
				}
				// Various anchor options
				$options = $keyword[0]->no_follow ? 'rel="nofollow" ' : '';
				$options .= $keyword[0]->new_window ? 'target="_blank" ' : '';
				$options .= $keyword[0]->statusbar ?
					sprintf( "onmouseover=\"window.status='%s'; return true\""
							." onmouseout=\"window.status=''; return true\" ",
							addslashes($keyword[0]->statusbar)) : '';
				// Replace placeholders with cloaked url
				$content = str_replace($placeholder,
									   sprintf('<a class="%s" %s href="%s">%s</a>',
													get_option('vpurl_css_class'),
													$options,
													$keyword[0]->cloaked_link,
													$keyword[0]->keyword_name),
									   $content
				);
				$lcontent = strtolower($content);
				$replacement_count += $keyword[0]->replacement_count;	
			}
		} 
		while((!$replacement_count_max || $replacement_count < $replacement_count_max) and $chosen_keyword);
		return $content;
	}
	add_filter( "the_content", "vpurl_inject_keywords" );

	//
	// Alteration helpers
	//
	
	/**
	 * Location is Valid
	 * 	Determine if a keyword is valid for replacement
	 * 	
	 */
	function location_is_valid($position, $content, $keyword){
		
		// Prevent nesting of shorter keywords within longer
		// Delimit with punctuation and whitespace
		if (!preg_match('/[[:punct:] ]/', $content{$position+strlen($keyword)})){
			
			#echo 'Punctuation incorrect<br>';
			return false;
		}
		
		// Search for brackets indicating HTML
		$left = array(
			backwardStrpos($content, '<', $position),
			backwardStrpos($content, '>', $position)
		);
				
		$right = array(
			strpos($content, '<', $position),
			strpos($content, '>', $position)
		);
				
		// Match within a tag
		if ($position > $left[0] and $position < $right[1]
			and ($left[1] < $left[0] or !$left[1])
			and ($right[0] > $right[1] or !$right[0])
			and preg_match('/[[:alpha:]]/', $content{$left[0]+1})){
			
			#echo 'Within tag match<br>';
			return false;
		}
		
		// Enclosed by tags
		if ($position > $left[1] and $position < $right[0]
			and $left[0] < $left[1] and $right[0] < $right[1]
			and preg_match('/[[:alpha:]]/', $content{$left[0]+1})){
			
			// only apply to anchors at the moment
			if ($content{$left[0]+1} == 'a'){
				
				#echo 'Match within anchor<br>';
				return false;
			}
		}
		
		// All tests passed
		return true;
	}
	
	// Compare weight of two keywords for custom sorting
	function compare_weight($keyword1, $keyword2){
		return ($keyword1[0]->weight > $keyword2[0]->weight);
	}
	
	
?>