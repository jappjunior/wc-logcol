<?php
/*
 * Plugin Name: Transportadora LogCol
 * Description: Integra a tabela de frete da Kaiowá do Brasil nos métodos de entrega do Woocommerce
 * Plugin URI:           https://github.com/logcol
 * Author:               José Pimenta
 * Author URI:           https://kaiowadobrasil.com.br
 * Version:              1.0
 * License:              GPLv2 or later
 * Text Domain:          logcol
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
    function logcol_shipping_method() {
        if ( ! class_exists( 'LogCol_Shipping_Method' ) ) {
            class LogCol_Shipping_Method extends WC_Shipping_Method {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct( $instance_id = 0 ) {
                
                  //converte para inteiro não negativo
                  $this->instance_id        = absint( $instance_id );

                  $this->method_title       = __( 'LogCol Shipping', 'logcol' );
                  $this->method_description = __( 'Tabela da LogCol para Kaiowá do Brasil', 'logcol' );
                  $this->supports           = array(
                    'shipping-zones',
                    'instance-settings',
                    );

                  $this->enabled            = $this->get_option( 'enabled' );
                  $this->title              = $this->get_option( 'title' );
                  $this->origin_postcode    = $this->get_option( 'origin_postcode' );
                  $this->shipping_class_id  = (int) $this->get_option( 'shipping_class_id', '-1' );
                  $this->show_delivery_time = $this->get_option( 'show_delivery_time' );
                  $this->additional_time    = $this->get_option( 'additional_time' );
                  $this->fee                = $this->get_option( 'fee' );
                  $this->receipt_notice     = $this->get_option( 'receipt_notice' );
                  $this->own_hands          = $this->get_option( 'own_hands' );
                  $this->declare_value      = $this->get_option( 'declare_value' );
                  $this->custom_code        = $this->get_option( 'custom_code' );
                  $this->service_type       = $this->get_option( 'service_type' );
                  $this->minimum_height     = $this->get_option( 'minimum_height' );
                  $this->minimum_width      = $this->get_option( 'minimum_width' );
                  $this->minimum_length     = $this->get_option( 'minimum_length' );
                  $this->extra_weight       = $this->get_option( 'extra_weight', '0' );
                  
                  $this->init();
                  $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
                  $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'LogCol Shipping', 'logcol' );
                
                }

                /**
                * Init your settings
                *
                * @access public
                * @return void
                */
                function init() {
                // Load the settings API
                $this->init_form_fields(); 
                $this->init_settings(); 

                // Save settings in admin if you have any defined
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }
 
                /**
                 * Define settings field for this shipping no painel do WordPress
                 * @return void 
                 */
                function init_form_fields() { 
 
                    $this->form_fields = array(
 
                     'enabled' => array(
                          'title' => __( 'Enable', 'logcol' ),
                          'type' => 'checkbox',
                          'description' => __( 'Enable this shipping.', 'logcol' ),
                          'default' => 'yes'
                          ),
 
                     'title' => array(
                        'title' => __( 'Title', 'logcol' ),
                          'type' => 'text',
                          'description' => __( 'Title to be display on site', 'logcol' ),
                          'default' => __( 'LogCol Shipping', 'logcol' )
                          ),

                     'classe' => array(
                        'title' => __( 'Classe de Entrega', 'logcol' ),
                          'type' => 'text',
                          'description' => __( 'Seleciona a classe aplicável a esse método', 'logcol' ),
                          'default' => __( 'Classe', 'logcol' )
                          ),                     
 
                     'weight' => array(
                        'title' => __( 'Weight (kg)', 'logcol' ),
                          'type' => 'number',
                          'description' => __( 'Maximum allowed weight', 'logcol' ),
                          'default' => 100
                          ),

                    'gris' => array(
                        'title' => __( 'GRIS', 'logcol' ),
                          'type' => 'number',
                          'description' => __( 'Taxa de GRIS', 'logcol' ),
                          'default' => 0.15
                      	  ),

                    'cubagem' => array(
                        'title' => __( 'Cubagem', 'logcol' ),
                          'type' => 'number',
                          'description' => __( 'Fator de Cubagem', 'logcol' ),
                          'default' => 300
                      	  )
                     );
                }

                /**
                 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping( $package ) {/*
                    
                    $weight = 0;
                    $length = 0;
                    $width = 0;
                    $height = 0;
                    $cost = 0;
                    $country = $package["destination"]["country"];
 
                    foreach ( $package['contents'] as $item_id => $values ) 
                    { 
                        $_product = $values['data']; 
                        $weight = $weight + $_product->get_weight() * $values['quantity']; 
           				$height = $height + $_product->get_length() * $values['quantity'];
						$width  = $width + $_product->get_width() * $values['quantity'];
						$length = $length + $_product->get_height() * $values['quantity'];

                    }
 
                    $weight = wc_get_weight( $weight, 'kg' );

                    $cubagem = $length * 0.001 * $width * 0.001 * $height * 0.001 * 300;

                    if( $cubagem > $weight ) {
                    	$fator = $cubagem;
                    } else {
                    	$fator = $weight;
                    }
 
                    if( $fator <= 5 ) {
 
                        $cost = 100;
 
                    } elseif( $fator <= 10 ) {
 
                        $cost = 500;
 
                    } elseif( $fator <= 50 ) {
 
                        $cost = 1000;
 
                    } else {
 
                        $cost = 2000;
 
                    }
 
                    $countryZones = array(
                        'BR' => 3
                        );
 
                    $zonePrices = array(
                        3 => 700
                        );
 
                    $zoneFromCountry = $countryZones[ $country ];
                    $priceFromZone = $zonePrices[ $zoneFromCountry ];
 
                    $cost += $priceFromZone;
 
                    $rate = array(
                        'id' => $this->id,
                        'label' => $this->title,
                        'cost' => $cost
                    );
 
                    $this->add_rate( $rate );*/
                    
                }
            }
        }
    }
 
    add_action( 'woocommerce_shipping_init', 'logcol_shipping_method' );
 
    function add_logcol_shipping_method( $methods ) {
        $methods[] = 'LogCol_Shipping_Method';
        return $methods;
    }
 
    add_filter( 'woocommerce_shipping_methods', 'add_logcol_shipping_method' );
}
