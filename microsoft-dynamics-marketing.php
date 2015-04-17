<?php
/*
Plugin Name: Microsoft Dynamics Marketing Tracking
Description: Bare bones option for people looking to simply insert the basic Microsoft Dynamics Marketing web site tracking code into every page without any fuss.
Version: 1.0.0 
Author: Hans Van de Velde
Author URI: http://www.net-it.be
License: GPLv3
Copyright 2015 Net IT NV (http://www.net-it.be/)
*/

if( !class_exists( 'microsoftDynamicsMarketingTracking' ) ) : // namespace collision check
class microsoftDynamicsMarketingTracking {
	// declare globals
	var $options_name = 'microsoft_dynamics_marketing_tracking_item';
	var $options_group = 'microsoft_dynamics_marketing_tracking_option_option';
	var $options_page = 'microsoft_dynamics_marketing_tracking';
	var $plugin_homepage = 'http://www.net-it.be/';
	var $plugin_name = 'Microsoft Dynamics Marketing Tracking';
	var $plugin_textdomain = 'MicrosoftDynamicsMarketingTracking';

	// constructor
	function microsoftDynamicsMarketingTracking() {
		$options = $this->optionsGetOptions();
		add_filter( 'plugin_row_meta', array( &$this, 'optionsSetPluginMeta' ), 10, 2 ); // add plugin page meta links
		add_action( 'admin_init', array( &$this, 'optionsInit' ) ); // whitelist options page
		add_action( 'admin_menu', array( &$this, 'optionsAddPage' ) ); // add link to plugin's settings page in 'settings' menu on admin menu initilization
		add_action( 'wp_footer', array( &$this, 'getTrackingCode' ), 99999 ); // add the tracking code in the footer
		register_activation_hook( __FILE__, array( &$this, 'optionsCompat' ) );
	}

	// load i18n textdomain
	function loadTextDomain() {
		load_plugin_textdomain( $this->plugin_textdomain, false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) . 'lang/' );
	}
		
	// compatability with upgrade from version <1.4
	function optionsCompat() {
		$old_options = get_option( 'ssga_item' );
		if ( !$old_options ) return false;
		
		$defaults = optionsGetDefaults();
		foreach( $defaults as $key => $value )
			if( !isset( $old_options[$key] ) )
				$old_options[$key] = $value;
		
		add_option( $this->options_name, $old_options, '', false );
		delete_option( 'ssga_item' );
		return true;
	}
	
	// get default plugin options
	function optionsGetDefaults() { 
		$defaults = array( 
			'script' => '', 
			'insert_code' => 0,
			'track_admin' => 0
		);		
		return $defaults;
	}
	
	function optionsGetOptions() {
		$options = get_option( $this->options_name, $this->optionsGetDefaults() );
		return $options;
	}
	
	// set plugin links
	function optionsSetPluginMeta( $links, $file ) { 
		$plugin = plugin_basename( __FILE__ );
		if ( $file == $plugin ) { // if called for THIS plugin then:
			$newlinks = array( '<a href="options-general.php?page=' . $this->options_page . '">' . __( 'Settings', $this->plugin_textdomain ) . '</a>' ); // array of links to add
			return array_merge( $links, $newlinks ); // merge new links into existing $links
		}
	return $links; // return the $links (merged or otherwise)
	}
	
	// plugin startup
	function optionsInit() { 
		register_setting( $this->options_group, $this->options_name, array( &$this, 'optionsValidate' ) );
	}
	
	// create and link options page
	function optionsAddPage() { 
		add_options_page( $this->plugin_name . ' ' . __( 'Settings', $this->plugin_textdomain ), __( 'Dynamics Marketing Tracking', $this->plugin_textdomain ), 'manage_options', $this->options_page, array( &$this, 'optionsDrawPage' ) );
	}
	
	// sanitize and validate options input
	function optionsValidate( $input ) { 
		$input['insert_code'] = ( $input['insert_code'] ? 1 : 0 ); 	// (checkbox) if TRUE then 1, else NULL
		$input['track_admin'] = ( $input['track_admin'] ? 1 : 0 ); 	// (checkbox) if TRUE then 1, else NULL
		return $input;
	}
	
	// draw a checkbox option
	function optionsDrawCheckbox( $slug, $label, $style_checked='', $style_unchecked='' ) { 
		$options = $this->optionsGetOptions();
		if( !$options[$slug] ) 
			if( !empty( $style_unchecked ) ) $style = ' style="' . $style_unchecked . '"';
			else $style = '';
		else
			if( !empty( $style_checked ) ) $style = ' style="' . $style_checked . '"';
			else $style = ''; 
	?>
		 <!-- <?php _e( $label, $this->plugin_textdomain ); ?> -->
			<tr valign="top">
				<th scope="row">
					<label<?php echo $style; ?> for="<?php echo $this->options_name; ?>[<?php echo $slug; ?>]">
						<?php _e( $label, $this->plugin_textdomain ); ?>
					</label>
				</th>
				<td>
					<input name="<?php echo $this->options_name; ?>[<?php echo $slug; ?>]" type="checkbox" value="1" <?php checked( $options[$slug], 1 ); ?>/>
				</td>
			</tr>
			
	<?php }
	
	// draw the options page
	function optionsDrawPage() { ?>
		<div class="wrap">
		<div class="icon32" id="icon-options-general"><br /></div>
			<h2><?php echo $this->plugin_name . __( ' Settings', $this->plugin_textdomain ); ?></h2>
			<form name="form1" id="form1" method="post" action="options.php">
				<?php settings_fields( $this->options_group ); // nonce settings page ?>
				<?php $options = $this->optionsGetOptions();  //populate $options array from database ?>
				
				<!-- Description -->
				<p style="font-size:0.95em">
		
				<table class="form-table">
	
					<?php $this->optionsDrawCheckbox( 'insert_code', 'Insert tracking code?', '', 'color:#f00;' ); ?>					 
	
					<tr valign="top">
					<th scope="row" valign="middle">
					<label for="<?php echo $this->options_name; ?>[script]">Redirecting Script</label>
					</th>
						<td>
							<textarea rows="10" cols="75" name="<?php echo $this->options_name; ?>[script]">
							<?php echo $options['script']; ?>
							</textarea>							
						</td>
					</tr>
					
					<?php echo $this->optionsDrawCheckbox( 'track_admin', 'Track administrator hits?' ); ?>
					
				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->plugin_textdomain ) ?>" />
				</p>
			</form>
		</div>
		
		<?php
	}
	
	// 	the MDM tracking code to be inserted
	function getTrackingCode() { 
		$options = $this->optionsGetOptions();
	
	// header
	$header = sprintf( 
		__( '
<!-- 

Plugin: %1$s by Net IT nv (http://www.net-it.be/)', $this->plugin_textdomain ), $this->plugin_name );
	// footer
	$footer = '-->';
	
	// code removed for all pages
	$disabled = $header . __( 'You\'ve chosen to prevent the tracking code from being inserted on 
	any page. 
	
	You can enable the insertion of the tracking code by going to 
	Settings > Dynamics Marketing Tracking on the Dashboard.', $this->plugin_textdomain ) . $footer;
	
	// code removed for admin
	$admin = $header . __( 'You\'ve chosen to prevent the tracking code from being inserted on 
	pages viewed by logged-in administrators. 
	
	You can re-enable the insertion of the tracking code on all pages
	for all users by going to Settings > Dynamics Marketing Tracking on the Dashboard.', $this->plugin_textdomain ) . $footer;
	
	// core tracking code
	$core = $options['script']; 
	
	// build code
	if( !$options['insert_code'] ) 
		echo $disabled; 
	elseif( current_user_can( 'manage_options' ) && !$options['track_admin'] ) 
		echo $admin; 
	else 
		echo $header . "\n\n" . $footer . "\n\n" . $core ; 
	}
} // end class
endif; // end collision check

$microsoftDynamicsMarketingTracking_instance = new microsoftDynamicsMarketingTracking;
?>