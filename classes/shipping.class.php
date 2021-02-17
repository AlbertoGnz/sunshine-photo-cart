<?php
class SunshineShipping {

	public $methods = array();

	function __construct() {
		$this->methods = apply_filters( 'sunshine_add_shipping_methods', $this->methods );
	}

	public function get_shipping_methods() {
		return apply_filters( 'sunshine_shipping_methods', $this->methods );
	}

	public function get_shipping_method_cost( $method ) {
		global $sunshine;
		$cost = ( isset( $sunshine->options[ $method . '_cost' ] ) ? $sunshine->options[ $method . '_cost' ] : 0 );
		/*
		if ( $sunshine->options['price_has_tax'] != 'yes' && $this->is_taxable( $method ) ) {
			$cost += $sunshine->cart->tax_shipping;
		}
		*/
		return apply_filters( 'sunshine_shipping_method_cost', $cost, $method );
	}

	public function is_taxable( $method ) {
		global $sunshine;
		if ( !empty( $sunshine->options[ $method . '_taxable' ] ) ) {
			return $sunshine->options[ $method . '_taxable' ];
		}
		return false;
	}

	public function clear() {
		$this->methods = array();
	}

}
?>
