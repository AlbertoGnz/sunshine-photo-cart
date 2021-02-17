<?php
/*
Borrowed from WooCommerce, no wheels being invented here
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sunshine_Setup {

    private $step = '';
    private $steps = array();
    private $errors = array();

    public function __construct() {
        if ( empty( $_GET['page'] ) || 'sunshine-setup' !== $_GET['page'] ) {
            return;
        }
        if ( current_user_can( 'sunshine_manage_options' ) ) {
            add_action( 'admin_menu', array( $this, 'admin_menus' ) );
            add_action( 'admin_init', array( $this, 'setup' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        }
    }

    public function admin_menus() {
        add_dashboard_page( '', '', 'manage_options', 'sunshine-setup', '' );
    }

    public function setup() {
        $default_steps = array(
            'license' => array(
                'name'    => __( 'License', 'sunshine' ),
                'view'    => array( $this, 'step_license' ),
                'handler' => array( $this, 'step_license_save' ),
            ),
            'addons'     => array(
                'name'    => __( 'Add-ons', 'sunshine' ),
                'view'    => array( $this, 'step_addons' ),
                'handler' => array( $this, 'wc_setup_payment_save' ),
            ),
            'shipping'    => array(
                'name'    => __( 'Shipping', 'sunshine' ),
                'view'    => array( $this, 'step_shipping' ),
                'handler' => array( $this, 'wc_setup_shipping_save' ),
            ),
            'recommended' => array(
                'name'    => __( 'Recommended', 'sunshine' ),
                'view'    => array( $this, 'step_recommended' ),
                'handler' => array( $this, 'wc_setup_recommended_save' ),
            ),
            'activate'    => array(
                'name'    => __( 'Activate', 'sunshine' ),
                'view'    => array( $this, 'step_activate' ),
                'handler' => array( $this, 'wc_setup_activate_save' ),
            ),
            'next_steps'  => array(
                'name'    => __( 'Ready!', 'sunshine' ),
                'view'    => array( $this, 'step_ready' ),
                'handler' => '',
            ),
        );

        $this->steps = $default_steps;
        $this->step  = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );

        if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
            check_admin_referer( 'sunshine-setup' );
            call_user_func( $this->steps[ $this->step ]['handler'], $this );
            // If no errors move on
            wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
            exit;
            // Otherwise try again
        }

        ob_start();
        $this->header();
        $this->steps();
        $this->content();
        $this->footer();
        exit;
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'sunshine-setup', SUNSHINE_URL . '/assets/css/setup.css', array( 'dashicons', 'install' ), WC_VERSION );
    }

    public function header() {
        set_current_screen();
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta name="viewport" content="width=device-width" />
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <title><?php esc_html_e( 'Sunshine &rsaquo; Setup Wizard', 'sunshine' ); ?></title>
            <?php do_action( 'admin_enqueue_scripts' ); ?>
            <?php wp_print_scripts( 'sunshine-setup' ); ?>
            <?php do_action( 'admin_print_styles' ); ?>
            <?php do_action( 'admin_head' ); ?>
        </head>
        <body class="sunshine-setup wp-core-ui">
            <h1><a href="https://www.sunshinephotocart.com/"><img src="https://sunshine-wpengine.netdna-ssl.com/wp-content/themes/sunshine-fremium/images/logo.png" alt="Sunshine Photo Cart" /></a></h1>
    <?php
    }

    public function steps() {
    ?>
		<ol class="sunshine-setup-steps">
			<?php
			foreach ( $this->steps as $step_key => $step ) {
				$is_completed = array_search( $this->step, array_keys( $this->steps ), true ) > array_search( $step_key, array_keys( $this->steps ), true );

				if ( $step_key === $this->step ) {
					?>
					<li class="active"><?php echo esc_html( $step['name'] ); ?></li>
					<?php
				} elseif ( $is_completed ) {
					?>
					<li class="done">
						<a href="<?php echo esc_url( add_query_arg( 'step', $step_key, remove_query_arg( 'activate_error' ) ) ); ?>"><?php echo esc_html( $step['name'] ); ?></a>
					</li>
					<?php
				} else {
					?>
					<li><?php echo esc_html( $step['name'] ); ?></li>
					<?php
				}
			}
			?>
		</ol>
    	<?php
    }

    public function get_next_step_link( $step = '' ) {
        if ( ! $step ) {
            $step = $this->step;
        }

        $keys = array_keys( $this->steps );
        if ( end( $keys ) === $step ) {
            return admin_url();
        }

        $step_index = array_search( $step, $keys, true );
        if ( false === $step_index ) {
            return '';
        }

        return add_query_arg( 'step', $keys[ $step_index + 1 ], remove_query_arg( 'activate_error' ) );
    }

    public function content() {
        echo '<div class="sunshine-setup-content">';
		if ( ! empty( $this->steps[ $this->step ]['view'] ) ) {
			call_user_func( $this->steps[ $this->step ]['view'], $this );
		}
		echo '</div>';
    }

    public function footer() {
    ?>
        </body>
        </html>
    <?php
    }

    public function step_license() {
    ?>
        <form method="post">
            <?php wp_nonce_field( 'sunshine-setup' ); ?>

			<div style="width: 45%; float: left; margin: 0 0 40px 0;">
				<h2>Setup Sunshine Pro</h2>
            	<p>If you have a Sunshine Pro license key, enter it below.</p>
				<p><input type="text" name="pro" /></p>
			</div>

			<div style="width: 45%; float: right; margin: 0 0 40px 0;">
				<h2>What is Sunshine Pro?</h2>
				<p>Sunshine has many great add-ons to extend the features available. Sunshine Pro gives you access to all these add-ons and premium support for one low cost.</p>
				<p><span style="text-decoration: line-through; color: #999;">$149</span> <strong>Now only $99!</strong></p>
				<p><a href="https://www.sunshinephotocart.com/prohttps://www.sunshinephotocart.com/pro/?utm_source=plugin&utm_medium=link&utm_campaign=setup" target="_blank">Learn more about Pro!</a></p>
			</div>

            <p class="sunshine-setup-actions">
                <button type="submit" class="sunshine-setup-button" value="<?php esc_attr_e( "Let's go!", 'woocommerce' ); ?>" name="save_step"><?php esc_html_e( "Continue Setup", 'woocommerce' ); ?></button>
            </p>
        </form>

    <?php
    }

    public function step_license_save() {
        // Do the saving
        // Set errors if need be
    }

	public function step_addons() {
	?>
		<form method="post">
			<?php wp_nonce_field( 'sunshine-setup' ); ?>

			<h2>Add-ons for Sunshine</h2>
			<p>If Pro, check options to choose which ones to enable otherwise links to the individual pages</p>

			<p class="sunshine-setup-actions step">
				<button type="submit" class="sunshine-setup-button" value="<?php esc_attr_e( "Continue", 'woocommerce' ); ?>" name="save_step"><?php esc_html_e( "Let's go!", 'woocommerce' ); ?></button>
			</p>
		</form>

	<?php
	}

	public function step_addons_save() {
		// Do the saving
		// Set errors if need be
	}


}

new Sunshine_Setup();
