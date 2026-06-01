<?php
/**
 * Email Footer – LadyM brandelt (Fázis 8 / pre-go-live).
 *
 * Forrás-template, amire ráoldódik: 10.4.0 (WC default email-footer.php).
 * Brand-tokenek hex-be inline-olva, lásd az email-header.php-t.
 *
 * @package LadyM
 */

defined( 'ABSPATH' ) || exit;

$email             = $email ?? null;
$email_footer_text = get_option( 'woocommerce_email_footer_text' );
$shop_url          = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$aszf_url          = home_url( '/aszf/' );
$privacy_url       = home_url( '/adatvedelem/' );
$elallasi_url      = home_url( '/elallasi-jog/' );
$impresszum_url    = home_url( '/impresszum/' );
?>
									</div>
								</td>
							</tr>
						</table>
						<!-- BODY END -->

						<!-- FOOTER -->
						<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_footer" role="presentation" style="background-color:#fbfaf6;border-collapse:collapse;">
							<tr>
								<td style="padding:0 32px;">
									<div style="height:1px;line-height:1px;background-color:#e5ded1;font-size:1px;">&nbsp;</div>
								</td>
							</tr>
							<tr>
								<td id="credit" style="padding:24px 32px 18px 32px;font-family:Inter,Arial,sans-serif;text-align:center;color:#4a5673;font-size:12px;line-height:1.6;background-color:#fbfaf6;">
									<?php
									echo wp_kses_post(
										wpautop(
											wptexturize(
												apply_filters( 'woocommerce_email_footer_text', $email_footer_text, $email )
											)
										)
									);
									?>
								</td>
							</tr>
							<tr>
								<td style="padding:0 32px 28px 32px;font-family:Inter,Arial,sans-serif;text-align:center;font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:#5a6585;background-color:#fbfaf6;">
									<a href="<?php echo esc_url( $aszf_url ); ?>" style="color:#5a6585;text-decoration:none;margin:0 6px;"><?php esc_html_e( 'ÁSZF', 'ladym' ); ?></a>
									·
									<a href="<?php echo esc_url( $privacy_url ); ?>" style="color:#5a6585;text-decoration:none;margin:0 6px;"><?php esc_html_e( 'Adatvédelem', 'ladym' ); ?></a>
									·
									<a href="<?php echo esc_url( $elallasi_url ); ?>" style="color:#5a6585;text-decoration:none;margin:0 6px;"><?php esc_html_e( 'Elállási jog', 'ladym' ); ?></a>
									·
									<a href="<?php echo esc_url( $impresszum_url ); ?>" style="color:#5a6585;text-decoration:none;margin:0 6px;"><?php esc_html_e( 'Impresszum', 'ladym' ); ?></a>
								</td>
							</tr>
						</table>
						<!-- END FOOTER -->

					</div>
				</td>
				<td><!-- gutter --></td>
			</tr>
		</table>
	</body>
</html>
