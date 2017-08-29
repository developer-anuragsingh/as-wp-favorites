<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Favorites {

	/**
	 * The single instance of Favorites.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token = 'favorites';

		// Load plugin environment variables
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		add_shortcode( 'as_my_favorites', array($this, 'my_favorites_form') );
		add_shortcode( 'as_my_favorites_items', array($this, 'my_favorites_items_list') );
		
		add_filter('the_content', array($this, 'add_shortcode_on_every_page'));	// Hooks shortcode in "the_content"


		add_action('wp_ajax_add_to_user_favorites', array($this, 'add_to_user_favorites'));
		add_action('wp_ajax_nopriv_add_to_user_favorites', array($this, 'add_to_user_favorites'));


		// Load API for generic admin functions
		if ( is_admin() ) {
			$this->admin = new Favorites_Admin_API();
		}

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
	} // End __construct ()

	/**
	 * Wrapper function to register a new post type
	 * @param  string $post_type   Post type name
	 * @param  string $plural      Post type item plural name
	 * @param  string $single      Post type item single name
	 * @param  string $description Description of post type
	 * @return object              Post type class object
	 */
	public function register_post_type ( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		$post_type = new Favorites_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 * @param  string $taxonomy   Taxonomy name
	 * @param  string $plural     Taxonomy single name
	 * @param  string $single     Taxonomy plural name
	 * @param  array  $post_types Post types to which this taxonomy applies
	 * @return object             Taxonomy class object
	 */
	public function register_taxonomy ( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) return;

		$taxonomy = new Favorites_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		//wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		//wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
		wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-frontend' );

		wp_localize_script( 
            $this->_token . '-frontend', 
            'myAjax', 
            array(
                'url'   => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( "process_reservation_nonce" ),
            )
        );
	} // End enqueue_scripts ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'favorites', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'favorites';

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Main Favorites Instance
	 *
	 * Ensures only one instance of Favorites is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Favorites()
	 * @return Main Favorites instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

	public function get_favorites_button(){
		$userId = get_current_user_id();
		//$favoritesID = "7";
		$favoritesID = get_the_id();
		?>
		<form id="form-favorites" >
			<input type="hidden" name="id_loged_in_user" id="id_loged_in_user" value="<?php echo $userId; ?>">
			<input type="hidden" name="id_to_add_in_favorites" id="id_to_add_in_favorites" value="<?php echo $favoritesID; ?>">
			<input type="submit" name="add_to_favorites" id="add_to_favorites" value="Add to Favorites" >
		</form>
		<?php
	}

	function my_favorites_form() {
	    $this->get_favorites_button();
	}

	function add_to_user_favorites() {
		$idUser = $_POST['id_user'];
		$idFavorites = ($_POST['id_favorites']);
		$metaKey = "as_my_favorites";

		$arrExistingMetaVals  = get_user_meta($idUser, $metaKey);		// Get all existing values from Meta Key

		if(in_array($idFavorites, $arrExistingMetaVals)) {				// If value already exists in Meta key

			$checkDeleted = delete_user_meta( $idUser, $metaKey, $idFavorites );	// Remove provided (only) value from Meta Key

			if($checkDeleted === TRUE) {
				$status = 2;
				$msg = "Removed from favorites";
			}
		} else {														// If value not exists
			$checkMetaAdded = add_user_meta($idUser, $metaKey, $idFavorites);	// Add new value in Meta Key

			if ($checkMetaAdded) {
				$status = 1;
				$msg = "Added to favorites";
			}
		}		

		$response = array(
						'status' 		=> $status
						,'msg'			=> $msg
						,'effected_id' 	=> $idFavorites
					);

		echo json_encode($response);

		wp_die(); 
	}

	public function add_shortcode_on_every_page($content) {
		$shortcode =  do_shortcode( '[as_my_favorites]' );
		$shortcode .=  do_shortcode( '[as_my_favorites_items]' );
		$newContent = $content . $shortcode;
		return $newContent;
	}


	public function my_favorites_items_list() {
		if(is_user_logged_in()) { 	// user loged in 
			$arrAllFavoriteItems = get_user_meta(get_current_user_id(), 'as_my_favorites');

			echo '<ul class="favorites-list">';
				foreach ($arrAllFavoriteItems as $itemID) {
					if(get_post_status($itemID) === 'publish') {
						echo '<li>';
						echo '<a href = "' . esc_url(get_the_permalink($itemID)) . '" title = "' . get_the_title($itemID) . '">';
						echo esc_html_e(get_the_title($itemID));
						echo '</a>';
						echo '</li>';
					}
					
				}
			echo '</ul>';

		} else {					// user not loged in
			echo '<p>Please <a href="' . get_bloginfo('url') . '/login/">login</a> to view your favorites. </p>';
		}
	}
	
}