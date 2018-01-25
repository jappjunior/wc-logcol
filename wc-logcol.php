<?php
/*
 * Plugin Name: Transportadora LogCol
 * Description: Integra a tabela de frete da Kaiowá do Brasil nos métodos de entrega do Woocommerce
 * Plugin URI:           https://github.com/jappjunior/wc-logcol
 * Author:               José Pimenta
 * Author URI:           https://kaiowadobrasil.com.br
 * Version:              1.0
 * License:              GPLv2 or later
 * Text Domain:          wc-logcol
 * Domain Path:          /languages
 * WC requires at least: 3.0.0
 * WC tested up to:      3.2.0
*/

//não permite o acesso ao plugin diretamente
if ( !function_exists( 'add_action' ) ) {
	echo 'Olá, sou apenas um plugin, não posso fazer nada ao ser chamado diretamente!';
	exit;
}

//Includes
include ( 'includes/activate.php' );

//verifica a versão do WordPress
register_activation_hook( __FILE__, 'r_activate_plugin');

//Check if WooCommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
 
    //função que inclui as configuraões do método de postagem antes da classe ser instsanciada
    function logcol_shipping_method_init() {
        if ( ! class_exists( 'LogCol_Shipping_Method' ) ) {
            class LogCol_Shipping_Method extends WC_Shipping_Method {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct( $instance_id = 0 ) {

                      $this->id                 = 'logcol_shipping_method'; // Id for your shipping method. Should be uunique.
                      $this->instance_id        = absint( $instance_id );
                      
                      $this->method_title       = __( 'LogCol Shipping', 'wc-logcol' );
                      $this->method_description = __( 'Tabela da LogCol para Kaiowá do Brasil', 'wc-logcol' );
                      
                      $this->supports           = array(
                            'shipping-zones',
                            'instance-settings'
                            );

                      $this->init();  
                }

                /**
                * Init your settings
                *
                * @access public
                * @return void
                */
                function init() {
                  // Load the settings API
                  $this->init_settings(); //cometario teste
                  $this->init_form_fields(); 


                  // Define user set variables.
                  $this->enabled    = $this->get_option('enabled');
                  $this->title      = $this->get_option( 'title' );
                  $this->min_amount = $this->get_option( 'min_amount', 0 );
                  $this->requires   = $this->get_option( 'requires' );

                  // Save settings in admin if you have any defined
                  add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }

                /**
                 * Define settings field for this shipping no painel do WordPress
                 * @return void 
                 */
                function init_form_fields() { 
 
                    $this->instance_form_fields = array(
                          'title' => array(
                              'title'       => __( 'Title', 'wc-logcol' ),
                              'type'        => 'text',
                              'description' => __( 'Title to be display on site', 'wc-logcol' ),
                              'default'     => $this->method_title,
                              'desc_tip'    => true,
                              ),

                        'requires' => array(
                              'title'   => __( 'Free shipping requires...', 'wc-logcol' ),
                              'type'    => 'select',
                              'class'   => 'wc-enhanced-select',
                              'default' => '',
                              'options' => array(
                                  ''           => __( 'N/A', 'wc-logcol' ),
                                  'coupon'     => __( 'A valid free shipping coupon', 'wc-logcol' ),
                                  'min_amount' => __( 'A minimum order amount', 'wc-logcol' ),
                                  'either'     => __( 'A minimum order amount OR a coupon', 'wc-logcol' ),
                                  'both'       => __( 'A minimum order amount AND a coupon', 'wc-logcol' ),
                                  ),
                              ),

                              'min_amount' => array(
                                  'title'       => __( 'Minimum order amount', 'wc-logcol' ),
                                  'type'        => 'price',
                                  'placeholder' => wc_format_localized_price( 0 ),
                                  'description' => __( 'Gasto mínimo para usar esse método.', 'wc-logcol' ),
                                  'default'     => '0',
                                  'desc_tip'    => true,
                              ),
                        );
              
              $this->form_fields = $this->instance_form_fields;

              }




                 /** This function is used to calculate the shipping cost. Within this function we can check for weights,
                 * calculate_shipping function.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                 
                public function calculate_shipping( $package ) {
                  $rate = array(
                  'id' => $this->id,
                  'label' => $this->title,
                  'cost' => '2010.99',
                  'calc_tax' => 'per_item'
                  );

                  //Register the rate
                  $this->add_rate( $rate );                    

                 }
            }
        }
    }

   add_action( 'woocommerce_shipping_init', 'logcol_shipping_method_init' );
   
   function add_logcol_shipping_method( $methods ) {
          $methods['logcol_shipping_method'] = 'LogCol_Shipping_Method';
          return $methods;
  }
   
  add_filter( 'woocommerce_shipping_methods', 'add_logcol_shipping_method' );
}