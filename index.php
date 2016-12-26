<?php
/**
 * Plugin Name: Typograph
 * Plugin URI: http://www.igorkiselev.com/wp-plugin/typograph/
 * Description: Typographs content, title and excerpt before saving post (or page) to the database.
 * Version: 0.1.1
 * Author: Igor Kiselev, Evgeny Muravjev, Alexander Drutsa
 * Author URI: http://www.igorkiselev.com/
 * License: A "JustBeNice" license name e.g. GPL2.
 */

if( ! defined( 'ABSPATH' ) ) exit;




require_once 'EMT.php';

$typograf = new EMTypograph();

add_action('plugins_loaded', function(){
	load_plugin_textdomain( 'jbn-typograph', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
});



$slug = 'justbenice-';

add_action('admin_init', function () {
	global $slug;
	register_setting( $slug.'typograph-group', $slug.'typograph' );
	register_setting( $slug.'typograph-group', $slug.'typograph-settings' );
});

if(get_option( $slug.'typograph' )){
	add_filter('wp_insert_post_data', function ($data, $postarr) {
		global $typograf, $slug;
		$settings = get_option( $slug.'typograph-settings' );
		if($settings){
			foreach ($settings as $type => $setting ) {
				if($data['post_type'] == $type){
					foreach ($setting as $key => $value ) {
						$typograf->set_text($data[$key]);
						$typograf->setup(array(
							'Text.paragraphs' => 'off',
							'Text.breakline' => 'off',
							'OptAlign.oa_oquote' => 'off',
							'OptAlign.oa_obracket_coma' => 'off'
						));
						$return = $typograf->apply();
						$data[$key] = $return;
					}
				}
			}
		return $data;
		}
	}, '99', 2);

	add_filter( 'edit_form_top', function($post){
		$post->post_title = htmlspecialchars_decode(esc_attr( $post->post_title ));
	} );
	
}

add_action('admin_menu', function () {
	global $slug;
	add_options_page( __('Typograph', 'jbn-typograph'), __('Typograph', 'jbn-typograph'), 'manage_options', $slug.'typograph-options', function(){
		global $slug;
		if (!current_user_can('manage_options')) {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		?><div class="wrap">
			<h2><?php _e('Typograph settings', 'jbn-typograph'); ?></h2>
			<form method="post" action="options.php">
			<?php settings_fields( $slug.'typograph-group' ); ?>
				<table class="form-table">
						<tr>
							<th scope="row"><?php _e('Typograph', 'jbn-typograph'); ?></th>
							<td>
								<label for="<?php echo $slug; ?>typograph">
									<input id="<?php echo $slug; ?>typograph" name="<?php echo $slug; ?>typograph" type="checkbox" value="1" <?php checked( '1', get_option($slug.'typograph') ); ?> />
									<?php _e('Processes content with typographic rules.', 'jbn-typograph'); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">&nbsp;</th>
							<td>
							<?php
								$typograph_settings = get_option( $slug.'typograph-settings' );
								$typograph_fields = array('post_title','post_excerpt','post_content');
								$hidden = get_option( $slug.'typograph' );
								?>
							<h3><?php _e('Basic post types', 'jbn-typograph'); ?></h3>
							<table>
								<tr>
									<th><?php _e('Type'); ?></th>
									<th><?php _e('Title'); ?> <small>(post_title)</small></th>
									<th><?php _e('Excerpt'); ?> <small>(post_excerpt)</small></th>
									<th><?php _e('Content'); ?> <small>(post_content)</small></th>
								</tr>
								<?php foreach (get_post_types(array( 'public' => true, '_builtin' => true), 'objects') as $post_type ) {?>
								<tr>
									<td><?php echo $post_type->labels->name; ?> <em><small>(<?php echo $post_type->name; ?>)</small></em></td>
									<?php foreach ( $typograph_fields as $value ) { ?>
									<td>
										<?php if($post_type->name == "attachment" && $value == "post_content"){?>
										<?php }else{ ?>
											
											<input <?php if(!$hidden){?> disabled<?php } ?>
												name="<?php echo $slug; ?>typograph-settings[<?php echo $post_type->name; ?>][<?php echo $value; ?>]" type="checkbox" value="1"
												<?php if(isset($typograph_settings[$post_type->name][$value])) { ?>
													<?php checked( '1', $typograph_settings[$post_type->name][$value] ); ?>
												<?php } ?> />
												
												<?php if(!$hidden){ ?>
												<input
													name="<?php echo $slug; ?>typograph-settings[<?php echo $post_type->name; ?>][<?php echo $value; ?>]"
													type="hidden"
													value="<?php echo $typograph_settings[$post_type->name][$value] ?>" />
												<?php } ?>
										<?php } ?>
									</td>
									<?php } ?>
								</tr>
								<?php } ?>
							</table>
						</td>
					</tr>
					<tr>
						<th scope="row">&nbsp;</th>
						<td>
							<h3><?php _e('Registered post types', 'jbn-typograph'); ?></h3>
							<p class="description"><?php _e('Public post type that are registered in <a href="/wp-admin/theme-editor.php?file=functions.php">function.php</a> or other plugins.', 'jbn-typograph'); ?></p>
							<table>
								<tr>
									<th><?php _e('Type'); ?></th>
									<th><?php _e('Title'); ?> <small>(post_title)</small></th>
									<th><?php _e('Excerpt'); ?> <small>(post_excerpt)</small></th>
									<th><?php _e('Content'); ?> <small>(post_content)</small></th>
								</tr>
								<?php foreach (get_post_types(array( 'public' => true, '_builtin' => false), 'objects') as $post_type ) {?>
								<tr>
									<td>
										<?php echo $post_type->labels->name; ?> 
										<em>
											<small>(<?php echo $post_type->name; ?>)</small>
										</em>
									</td>
									<?php foreach ( $typograph_fields as $value ) { ?>
									<td>
										<?php if($post_type->name == "attachment" && $value == "post_content"){?>
											<small>не используется</small>
										<?php }else{?>
											<input
												<?php if(!$hidden){?> disabled<?php } ?>
												name="<?php echo $slug; ?>typograph-settings[<?php echo $post_type->name; ?>][<?php echo $value; ?>]"
												type="checkbox"
												value="1"
												<?php if(isset($typograph_settings[$post_type->name][$value])) { ?>
													<?php checked( '1', $typograph_settings[$post_type->name][$value] ); ?>
												<?php } ?> />
												<?php if(!$hidden){ ?>
												<input
													name="<?php echo $slug; ?>typograph-settings[<?php echo $post_type->name; ?>][<?php echo $value; ?>]"
													type="hidden"
													value="<?php echo $typograph_settings[$post_type->name][$value] ?>" />
												<?php } ?>
										<?php } ?>
									</td>
									<?php } ?>
								</tr>
								<?php } ?>
							</table>
						</td>
					</tr>
				</table>
				
				<?php do_settings_sections("theme-options"); submit_button(); ?>
				
				<p>
					<?php _e('Typograph authors: <a href="//mdash.ru" target="_blank">Evgeny Muravjev & Alexander Drutsa</a>', 'jbn-typograph'); ?>,
					<?php _e('plugin author: <a href="//www.igorkiselev.com/">Igor Kiselev</a>', 'jbn-typograph'); ?>
				</p>
				
			</form>
		</div><?php
	});
});

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), function($links){
	global $slug;
	
	return array_merge( $links, array('<a href="' . admin_url( 'options-general.php?page='.$slug.'typograph-options' ) . '">'.__('Settings').'</a>') );
});

?>