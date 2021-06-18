<?php
global $sunshine;
$order_data = sunshine_get_order_data(SunshineFrontend::$current_order->ID);
$order_items = sunshine_get_order_items(SunshineFrontend::$current_order->ID);
$customer_id = get_post_meta( SunshineFrontend::$current_order->ID, '_sunshine_customer_id', true );
$status = sunshine_get_order_status(SunshineFrontend::$current_order->ID);
?>
<div id="sunshine" class="sunshine-clearfix <?php sunshine_classes(); ?>">

	<?php do_action('sunshine_before_content'); ?>

	<div id="sunshine-main">

		<h2><?php echo sprintf( __( 'Order #%s', 'sunshine' ), SunshineFrontend::$current_order->ID ); ?></h2>

		<p id="sunshine-order-status" class="sunshine-status-<?php echo $status->slug; ?>">
			<strong><?php echo $status->name; ?>:</strong> <?php echo $status->description; ?>
		</p>
		<?php do_action( 'sunshine_order_notes', SunshineFrontend::$current_order->ID ); ?>
		<div class="sunshine-form" id="sunshine-order">
			<div id="sunshine-order-contact-fields" class="sunshine-clearfix">
				<h2><?php _e('Contact Information','sunshine'); ?></h2>
				<div class="field field-left"><label><?php _e('Email','sunshine'); ?></label> <?php echo $order_data['email']; ?></div>
				<?php if ( $order_data['phone'] ) { ?>
				<div class="field field-right"><label><?php _e('Phone','sunshine'); ?></label> <?php echo $order_data['phone']; ?></div>
				<?php } ?>
			</div>
			<div id="sunshine-order-billing-fields">
				<h2><?php _e('Billing Information','sunshine'); ?></h2>
				<div class="field field-left"><label><?php _e('First Name','sunshine'); ?></label> <?php echo $order_data['first_name']; ?></div>
				<div class="field field-right"><label><?php _e('Last Name','sunshine'); ?></label> <?php echo $order_data['last_name']; ?></div>
				<div class="field field-left"><label><?php _e('Address','sunshine'); ?></label> <?php echo $order_data['address']; ?></div>
				<div class="field field-right"><label><?php _e('Zip / Postcode','sunshine'); ?></label> <?php echo $order_data['zip']; ?></div>
				<div class="field field-left"><label><?php _e('City','sunshine'); ?></label> <?php echo $order_data['city']; ?></div>
				<div class="field field-right"><label><?php _e('State / Province','sunshine'); ?></label> <?php echo ( isset( SunshineCountries::$states[$order_data['country']][$order_data['state']] ) ) ? SunshineCountries::$states[$order_data['country']][$order_data['state']] : $order_data['state']; ?></div>
			</div>
			<?php if ( $order_data['shipping_first_name'] ) { ?>
				<div id="sunshine-order-shipping-fields">
					<h2>Comentarios</h2>
					<div class="field"><label>Producto</label> <?php echo $order_data['shipping_first_name']; ?></div>
					<div class="field"><label>Recordatorio: </label> <?php echo $order_data['shipping_last_name']; ?></div>
					<!-- <div class="field field-left"><label><?php _e('Address','sunshine'); ?></label> <?php echo $order_data['shipping_address']; ?></div> -->
					<div class="field"><label>Comentario comuniones: </label> <?php echo $order_data['shipping_address2']; ?></div>
<!-- 					<div class="field field-left"><label><?php _e('City','sunshine'); ?></label> <?php echo $order_data['shipping_city']; ?></div>
					<div class="field field-right"><label><?php _e('State / Province','sunshine'); ?></label> <?php echo ( isset( SunshineCountries::$states[$order_data['shipping_country']][$order_data['shipping_state']] ) ) ? SunshineCountries::$states[$order_data['shipping_country']][$order_data['shipping_state']] : $order_data['shipping_state']; ?></div>
					<div class="field field-left"><label><?php _e('Zip / Postcode','sunshine'); ?></label> <?php echo $order_data['shipping_zip']; ?></div>
					<div class="field field-right"><label><?php _e('Country','sunshine'); ?></label> <?php echo SunshineCountries::$countries[$order_data['shipping_country']]; ?></div>
-->				</div>
			<?php } ?>
		</div>
		<div id="sunshine-order-cart-items">
			<h2><?php _e('Items','sunshine'); ?></h2>
			<?php do_action('sunshine_before_order_items', SunshineFrontend::$current_order->ID, $order_items); ?>
			<table id="sunshine-cart-items">
			<tr>
				<th class="sunshine-cart-image"><?php _e('Image','sunshine'); ?></th>
				<th class="sunshine-cart-name"><?php _e('Product','sunshine'); ?></th>
			</tr>
			<?php
			$i = 1; foreach ($order_items as $item) {
			?>
				<tr class="sunshine-cart-item">
					<td class="sunshine-cart-item-image">
						<?php
						$thumb = wp_get_attachment_image_src($item['image_id'], 'sunshine-thumbnail');
						$image_html = '<a href="'.get_permalink($item['image_id']).'"><img src="'.$thumb[0].'" alt="" class="sunshine-image-thumb" /></a>';
						echo apply_filters('sunshine_cart_image_html', $image_html, $item, $thumb);
						?>
					</td>
					<td class="sunshine-cart-item-name">
						<div class="sunshine-item-comments"><?php echo apply_filters('sunshine_order_line_item_comments', $item['comments'], SunshineFrontend::$current_order->ID, $item); ?></div>
					</td>

				</tr>

			<?php $i++; } ?>
			</table>

			<div id="sunshine-order-totals">
				<table>
				<?php if ( $order_data['shipping_method'] ) { ?>
				<tr class="sunshine-shipping" hidden>
					<th><?php _e('Shipping','sunshine'); ?> (<?php echo sunshine_get_shipping_method_name( $order_data['shipping_method'] ); ?>)</th>
					<td>
						<?php
						if ( empty( $order_data['shipping_with_tax'] ) ) {
							sunshine_money_format( $order_data['shipping_cost'], true, true );
						} else {
							sunshine_money_format( $order_data['shipping_with_tax'] );
						}
						?>
					</td>
				</tr>
				<?php } ?>
				<?php if ( $order_data['discount_total'] > 0 ) { ?>
				<tr class="sunshine-discounts">
					<th><?php _e('Discounts','sunshine'); ?></th>
					<td>-<?php sunshine_money_format( $order_data['discount_total'] ); ?></td>
				</tr>
				<?php } ?>
				<?php if ( empty( $order_data['subtotal_with_tax'] ) && $order_data['tax'] > 0 ) { ?>
				<tr class="sunshine-tax">
					<th><?php _e('Tax','sunshine'); ?></th>
					<td><?php sunshine_money_format( $order_data['tax'], true, false ); ?></td>
				</tr>
				<?php } ?>
				<?php if ($order_data['credits'] > 0) { ?>
				<tr class="sunshine-credits">
					<th><?php _e('Credits','sunshine'); ?></th>
					<td>-<?php sunshine_money_format( $order_data['credits'], true, true ); ?></td>
				</tr>
				<?php } ?>

				</tr>
				</table>
			</div>
		</div>

	</div>

	<?php do_action('sunshine_after_content'); ?>

</div>
