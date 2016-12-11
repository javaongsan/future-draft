<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://imakeplugins.com
 * @since      1.0.0
 *
 * @package    Future_Draft
 * @subpackage Future_Draft/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Future_Draft
 * @subpackage Future_Draft/admin
 * @author     Bob Ong <ongsweesan@gmail.com>
 */
class Future_Draft_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private $metakey;
	private $metakey2;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $metakey,  $metakey2) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->metakey = $metakey;
		$this->metakey2 = $metakey2;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Future_Draft_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Future_Draft_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/future-draft-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Future_Draft_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Future_Draft_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/future-draft-admin.js', array( 'jquery' ), $this->version, false );
  		wp_localize_script( $this->plugin_name, 'future_draft_version_vars', 
                array( 
                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                    'nonce' => wp_create_nonce('future-draft-version-nonce'),
                    'error_message' => __('Sorry, there was a problem processing your request.', 'future_draft_version')
                ) 
            );  
	}

	public function add_tabs() {
		$screen = get_current_screen();

		if ( ! $screen || 'post' != $screen->base ) {
			return;
		}
		$post_type = $screen->post_type;
		$post = get_post();
		$post_id = $post->ID;

		add_action( 'admin_print_footer_scripts', array( $this, 'generate_javascript' ), 9 );

		$version_no = 0;
		$base_url = remove_query_arg( $_SERVER["REQUEST_URI"] );
		$tabs = '<div class="tabify-tabs tab-horizontal"><h2 class="nav-tab-wrapper"><a id="tab-0" href="' . $base_url . '" class="tabify-tab nav-tab nav-tab-active">Master</a>';
		
		$main_id = get_post_meta( $post_id,  $this->metakey2 , true);
		$meta = '';
	    if ($main_id) {	       
		    $meta = get_post_meta( $main_id, $this->metakey, true );
		}

		if ($meta)	{
			$base_url = admin_url('post.php?action=edit&post=' . $main_id);
			$tabs = '<div class="tabify-tabs tab-horizontal"><h2 class="nav-tab-wrapper"><a id="tab-0" href="' . $base_url . '" class="tabify-tab nav-tab">Master</a>';
		}
		else {
			$meta = get_post_meta($post_id, $this->metakey, true);
		}
		
		if (! empty($meta) &&  is_array( $meta ) ) {
			foreach ($meta as  $key) {
				$version_no = $key['version_no']; 
				$url = admin_url('post.php?action=edit&post=' . $key['new_id']);
				$tabs .= '<a id="' . $version_no . '"-ew-tab" class="tabify-tab nav-tab';
				if ($key['new_id'] == $post_id)
					$tabs .= ' nav-tab-active';
				$tabs .= '" href="'. $url . '">Version ' . $version_no . '</a>';
			}
		}

		$version_no = (int)$version_no + 1;
		$tabs .= '<a id="new-tab" class="tabify-tab nav-tab" href="#/" data-post-type="'. $post_type . '"  data-post-id="';

		if ($main_id) 
		 	$tabs .= $main_id;
		else
			$tabs .= $post_id;

		$tabs .='"  data-version-no="'. $version_no . '">*</a></h2></div>';
			$func  = create_function('', 'echo "$(\'#post\').prepend(\'' . addslashes( $tabs ) . '\');";');

			add_action( 'tabify_custom_javascript' , $func );
	}

	public function generate_javascript() {
		echo '<script type="text/javascript">';
		echo 'jQuery(function($) {';
		do_action( 'tabify_custom_javascript' );
		echo '});';
		echo '</script>';
	}

	public function create_new_draft () {

			if (isset($_POST['post_id']) && isset($_POST['version_no']))
			{
				$id = $_POST['post_id'];
				$version_no =  $_POST['version_no'];
			}

			// Check if this is an auto save routine. If it is we dont want to do anything
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
				return $id;

			// Only continue if this request is for the post or page post type
			if (!in_array($_POST['post_type'], array('post', 'page'))) {
					return $id;
			}

			// Check permissions
			if (!current_user_can('edit_' . ($_POST['post_type'] == 'posts' ? 'posts' : 'page'), $id )) {
		  			return $id;
		  	}

		  	$meta = get_post($id);

			// Duplicate post and set as a draft
			$draftPost = array(
			  'menu_order' => $meta->menu_order,
			  'comment_status' => $meta->comment_status,
			  'ping_status' => $meta->ping_status,
			  'post_author' => $meta->post_author,
			  'post_category' => $meta->post_category,
			  'post_content' => $meta->post_content,
			  'post_excerpt' => $meta->post_excerpt,
			  'post_parent' => $meta->parent_id,
			  'post_password' => $meta->post_password,
			  'post_status' => 'draft',
			  'post_title' => $meta->post_title  . ' Version ' . $version_no ,
			  'post_type' => $meta->post_type,
			  'tags_input' => wp_get_post_tags($id),
			  'filter'=>true
			);

			// Insert the post into the database
			kses_remove_filters();
			$newId = wp_insert_post($draftPost);
			kses_init_filters();

			// Custom meta data
			$custom = get_post_custom($id);
			foreach ($custom as $ckey => $cvalue) {
				if ($ckey != '_edit_lock' && $ckey != '_edit_last') {
					foreach ($cvalue as $mvalue) {
						add_post_meta($newId, $ckey, $mvalue, true);
					}
				}
			}

			$version_array = get_post_meta( $id, $this->metakey, true );
			$version_array[] =array('new_id'=>$newId, 'version_no' => $version_no);

			// Add a hidden meta data value to indicate that this is a draft of a live page
			update_post_meta($id, $this->metakey, 	$version_array);
			update_post_meta($newId, $this->metakey2, $id);
			// Send user to new edit page
			$reponse = admin_url('post.php?action=edit&post=' . $newId);
			echo $reponse;
			die();
		}
}
