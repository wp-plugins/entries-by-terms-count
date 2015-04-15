<?php
/*
Plugin Name: Entries By Terms Count
Plugin URI: http://blogestudio.com
Description: Content curation tool to search entries based on the number of terms: display posts without tags, with too many categories, etc.
Version: 1.0
Author: Pau Iglesias, Blogestudio
License: GPLv2 or later
*/

// Avoid script calls via plugin URL
if (!function_exists('add_action'))
	die;

// Check admin area
if (!is_admin())
	return;

/**
 * Entries By Terms Count plugin class
 *
 * @package WordPress
 * @subpackage Entries By Terms Count
 */

// Avoid declaration plugin class conflicts
if (!class_exists('BE_Entries_By_Terms_Count')) {
	
	// Create object plugin
	add_action('init', array('BE_Entries_By_Terms_Count', 'instance'));

	// Main class
	class BE_Entries_By_Terms_Count {



		// Const and properties
		// ---------------------------------------------------------------------------------------------------



		// Slug
		const slug = 		'entries-by-terms-count';
		
		// Editor role
		const capability =  'edit_others_posts';
		
		// Translation
		const text_domain = 'entries-by-terms-count';

		// Excludes
		const excluded_post_types = 'attachment,revision,nav_menu_item';
		const excluded_taxonomies = 'nav_menu,link_category,post_format';



		// Initialization
		// ---------------------------------------------------------------------------------------------------



		/**
		 * Creates a new object instance
		 */
		public static function instance() {
			return new BE_Entries_By_Terms_Count;
		}



		/**
		 * Constructor
		 */
		private function __construct() {
			add_action('admin_menu', array(&$this, 'admin_menu'));
		}



		/**
		 *  Load translation file
		 */
		private function load_plugin_textdomain($lang_dir = 'languages') {
			
			// Check load
			static $loaded;
			if (isset($loaded))
				return;
			$loaded = true;
			
			// Check if this plugin is placed in wp-content/mu-plugins directory or subdirectory
			if (('mu-plugins' == basename(dirname(__FILE__)) || 'mu-plugins' == basename(dirname(dirname(__FILE__)))) && function_exists('load_muplugin_textdomain')) {
				load_muplugin_textdomain(self::text_domain, ('mu-plugins' == basename(dirname(__FILE__))? '' : basename(dirname(__FILE__)).'/').$lang_dir);
			
			// Usual wp-content/plugins directory location
			} else {
				load_plugin_textdomain(self::text_domain, false, basename(dirname(__FILE__)).'/'.$lang_dir);
			}
		}



		// Methods
		// ---------------------------------------------------------------------------------------------------



		/**
		 * Admin menu hook
		 */
		public function admin_menu() {
			add_submenu_page('tools.php', 'Entries By Terms Count', 'Entries By Terms Count', self::capability, self::slug, array(&$this, 'entries_by_terms_count'));
		}



		/**
		 * Terms search page
		 */
		public function entries_by_terms_count() {
			
			/* Critical validation section */
			
			// Check user capabilities
			if (!current_user_can(self::capability))
				wp_die(__('You do not have sufficient permissions to access this page.'));
			
			// Collect data
			$post_types = get_post_types(array(), 'objects');
			$taxonomies = get_taxonomies(array(), 'objects');
			
			// Check post types and taxonomies data
			if (empty($post_types) || !is_array($post_types) || empty($taxonomies) || !is_array($taxonomies))
				return;
			
			// Define exclude post types and categories
			$avoid_post_types = apply_filters('be_ebtc_excluded_post_types', explode(',', self::excluded_post_types));
			$avoid_taxonomies = apply_filters('be_ebtc_excluded_taxonomies', explode(',', self::excluded_taxonomies));
			
			// Check excluded values for post types
			if (!empty($avoid_post_types) && is_array($avoid_post_types))
				$post_types = array_diff_key($post_types, array_fill_keys($avoid_post_types, true));

			// Check excluded values for taxnomies
			if (!empty($avoid_taxonomies) && is_array($avoid_taxonomies))
				$taxonomies = array_diff_key($taxonomies, array_fill_keys($avoid_taxonomies, true));

			// Check again post types and taxonomies data
			if (empty($post_types) || !is_array($post_types) || empty($taxonomies) || !is_array($taxonomies))
				return;
			
			
			/* From here not needed critical validation */
			
			// Load translations
			$this->load_plugin_textdomain();
			
			// Submit flags
			$sended = false;
			$sended_ok = false;
			
			// Check submit
			if (isset($_GET['be_nonce']) && wp_verify_nonce($_GET['be_nonce'], __FILE__)) {
				
				// Check other fields
				if (!empty($_GET['be_post_type']) && !empty($_GET['be_taxonomy']) && isset($_GET['be_operation']) && isset($_GET['be_count'])) {
				
					// Form values
					$post_type = 	$_GET['be_post_type'];
					$taxonomy = 	$_GET['be_taxonomy'];
					$operation = 	$_GET['be_operation'];
					$count = 		(int) $_GET['be_count'];
					
					// Flag sended
					$sended = 		true;
				}
			}
			
			?><div class="wrap">
			
				<?php screen_icon(); ?>
				
				<h2>Entries By Terms Count</h2>
				
				<?php if ($sended) : ?>
				
					<?php if (!isset($post_types[$post_type])) : // Check post type ?>
						
						<div class="notice notice-error" style="margin-top: 15px;"><p><?php printf(__('Error: Post Type <b>%s</b> not found.', self::text_domain), esc_html($post_type)); ?></p></div>
				
					<?php elseif (!isset($taxonomies[$taxonomy])) : // Check taxonomies ?>

						<div class="notice notice-error" style="margin-top: 15px;"><p><?php printf(__('Error: Taxonomy <b>%s</b> not found.', self::text_domain), esc_html($taxonomy)); ?></p></div>
					
					<?php else : $sended_ok = true; endif; ?>

				<?php endif; ?>
				
				<div id="poststuff">
					
					<div class="postbox">
						
						<h3 class="hndle"><span><?php _e('Search entries', self::text_domain); ?></span></h3>
						
						<div class="inside">
							
							<div id="postcustomstuff">

								<form method="get" action="<?php echo admin_url('tools.php'); ?>">

									<input type="hidden" name="page" value="<?php echo self::slug; ?>" />
									
									<label for="sl-post-type"><?php _e('Post Type:', self::text_domain); ?> </label>
									<select id="sl-post-type" name="be_post_type"><?php foreach ($post_types as $slug => $post_type_item) : ?><option <?php if (isset($post_type) && $post_type == $slug) echo 'selected'; ?> value="<?php echo $slug; ?>"><?php echo $post_type_item->labels->name; ?></option><?php endforeach; ?></select>
									
									&nbsp;
									
									<label for="sl-taxonomy"><?php _e('Taxonomy:', self::text_domain); ?> </label>
									<select id="sl-taxonomy" name="be_taxonomy"><?php foreach ($taxonomies as $slug => $taxonomy_item) : ?><option <?php if (isset($taxonomy) && $taxonomy == $slug) echo 'selected'; ?> value="<?php echo $slug; ?>"><?php echo $taxonomy_item->labels->name; ?></option><?php endforeach; ?></select>
									
									&nbsp;

									<label for="sl-operation"><?php _e('Operation:', self::text_domain); ?> </label>
									<?php if (!isset($operation)) $operation = ''; ?><select id="sl-operation" name="be_operation"><option <?php echo ('eq' == $operation)? 'selected ' : ''; ?>value="eq"><?php _e('Equal to', self::text_domain); ?></option><option <?php echo ('neq' == $operation)? 'selected ' : ''; ?>value="neq"><?php _e('Not equal to', self::text_domain); ?></option><option <?php echo ('gt' == $operation)? 'selected ' : ''; ?>value="gt"><?php _e('Greater than', self::text_domain); ?></option><option <?php echo ('gteq' == $operation)? 'selected ' : ''; ?>value="gteq"><?php _e('Greater than or equal to', self::text_domain); ?></option><option <?php echo ('lt' == $operation)? 'selected ' : ''; ?>value="lt"><?php _e('Less than', self::text_domain); ?></option><option <?php echo ('lteq' == $operation)? 'selected ' : ''; ?>value="lteq"><?php _e('Less than or equal to', self::text_domain); ?></option></select>
									
									&nbsp;

									<label for="tx-count"><?php _e('Count:', self::text_domain); ?></label>
									<input type="text" id="tx-count" name="be_count" value="<?php echo isset($count)? $count : 0; ?>" size="3" maxlength="6" /> <?php _e('terms', self::text_domain); ?>
									
									&nbsp;
									
									<input type="submit" value="<?php _e('Search', self::text_domain); ?>" class="button-primary" />
									
									<input type="hidden" name="be_nonce" value="<?php echo wp_create_nonce(__FILE__); ?>" />

								</form>
							
							</div>
						
						</div>
					
					</div>
				
				</div>
				
				<?php if ($sended_ok) : // Form sended correctly
					
					// Perform query
					$entries = $this->get_entries($post_type, $taxonomy, empty($operation)? 'eq' : $operation, $count);
					if (empty($entries)) : ?>
					
						<div class="notice notice-warning"><p><?php _e('No results found.', self::text_domain); ?></p></div>
					
					<?php else : $blog_url = get_bloginfo('url'); ?>
					
						<div class="notice notice-success"><p><?php printf(__('Found <b>%s</b> entries.', self::text_domain), count($entries)); ?></p></div>
						
						<table class="wp-list-table widefat posts" id="be-pls-posts">
							<thead>
								<tr>
									<th><?php _e('Title', self::text_domain); ?></th>
									<th><?php _e('Action', self::text_domain); ?></th>
									<th><?php _e('Status', self::text_domain); ?></th>
									<th><?php echo $taxonomies[$taxonomy]->labels->name; ?></th>
								</tr>
							</thead>
							<tbody><?php $i = 0; foreach ($entries as $entry) : $i++; ?>
								<tr<?php if ($i % 2 != 0) echo ' class="alternate"'; ?>>
									<td><a href="<?php echo $blog_url.'/?p='.$entry->ID; ?>" target="_blank"><?php echo esc_html($entry->post_title); ?></a></td>
									<td><a href="<?php echo admin_url('post.php?post='.$entry->ID.'&action=edit'); ?>" target="_blank">Edit</a></td>
									<td><?php echo ucfirst($entry->post_status); ?></td>
									<td><?php echo $entry->c; ?></td>
								</tr>
							<?php endforeach; ?></tbody>
							<tfoot>
								<tr>
									<th><?php _e('Title', self::text_domain); ?></th>
									<th><?php _e('Action', self::text_domain); ?></th>
									<th><?php _e('Status', self::text_domain); ?></th>
									<th><?php echo $taxonomies[$taxonomy]->labels->name; ?></th>
								</tr>
							</tfoot>
						</table>

					<?php endif; ?>
				
				<?php endif; ?>

			</div><?php
		}



		/**
		 * Return array of entries by terms count
		 */
		private function get_entries($post_type, $taxonomy, $operation, $count) {

			// Globals
			global $wpdb;
			
			// Check op
			$operations = array('eq' => '=', 'neq' => '<>', 'gt' => '>', 'gteq' => '>=', 'lt' => '<', 'lteq' => '<=');
			$operation = isset($operations[$operation])? $operations[$operation] : '=';
			
			// Compose query
			$sql = 'SELECT * FROM (
						SELECT
							ID, post_title, post_type, post_status, COUNT(object_id) c
						FROM
							'.esc_sql($wpdb->posts).'
						LEFT JOIN
							'.esc_sql($wpdb->term_relationships).'
						ON
							ID = object_id AND
							term_taxonomy_id IN (SELECT term_taxonomy_id FROM '.esc_sql($wpdb->term_taxonomy).' WHERE taxonomy = "'.esc_sql($taxonomy).'")
						WHERE
							post_type = "'.esc_sql($post_type).'" AND
							post_status IN ("publish", "future", "draft", "pending", "private")
						GROUP BY
							ID
						ORDER BY
							post_date DESC
					) s
					WHERE s.c '.esc_sql($operation).' '.esc_sql($count);
			
			// Return results
			return $wpdb->get_results($sql);
		}



	}
}