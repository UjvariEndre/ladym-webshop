<?php
/**
 * Email Header – LadyM brandelt (Fázis 8 / pre-go-live).
 *
 * Forrás-template, amire ráoldódik: 10.7.0 (WC default email-header.php).
 * Brand-tokenek itt hardcode-olva — az emaileknek inline szín kell, mert a klienspek
 * (Gmail, Outlook) a `<style>` blokkot általában strippelik. A var(--color-*) tokeneket
 * ezért a megfelelő hex-ekre fordítottuk: burgundy #65273c, ivory #f4f2f6, ink #0a0a0f,
 * line #d9d5dd, mocha #5b5b78.
 *
 * @package LadyM
 */

defined( 'ABSPATH' ) || exit;

$store_name       = $store_name ?? get_bloginfo( 'name', 'display' );
$header_image_url = apply_filters( 'woocommerce_email_header_image_url', home_url() );
$img              = get_option( 'woocommerce_email_header_image' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<title><?php echo esc_html( $store_name ); ?></title>
	</head>
	<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="background-color:#f4f2f6;">
		<table width="100%" id="outer_wrapper" role="presentation" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f2f6;">
			<tr>
				<td><!-- gutter --></td>
				<td width="600">
					<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>" style="margin:0 auto;width:600px;">

						<!-- BRAND HEADER -->
						<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" id="template_header" style="background-color:#f4f2f6;border-collapse:collapse;">
							<tr>
								<td id="header_wrapper" style="padding:36px 32px 16px 32px;text-align:center;background-color:#f4f2f6;">
									<?php if ( $img ) : ?>
										<?php if ( $header_image_url ) : ?>
											<a href="<?php echo esc_url( $header_image_url ); ?>" style="display:inline-block;text-decoration:none;" target="_blank">
												<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $store_name ); ?>" width="80" style="display:inline-block;width:80px;height:auto;border:0;outline:none;text-decoration:none;" />
											</a>
										<?php else : ?>
											<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $store_name ); ?>" width="80" style="display:inline-block;width:80px;height:auto;border:0;outline:none;text-decoration:none;" />
										<?php endif; ?>
									<?php endif; ?>
									<div style="font-family:'Playfair Display',Georgia,'Times New Roman',serif;font-weight:600;font-size:22px;letter-spacing:0.04em;color:#0a0a0f;margin-top:14px;">
										<?php echo esc_html( $store_name ); ?>
									</div>
									<div style="font-family:Inter,Arial,sans-serif;font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:#5b5b78;margin-top:6px;">
										<?php esc_html_e( 'Past to Present Atelier', 'ladym' ); ?>
									</div>
								</td>
							</tr>
							<tr>
								<td style="padding:0 32px;">
									<div style="height:1px;line-height:1px;background-color:#d9d5dd;font-size:1px;">&nbsp;</div>
								</td>
							</tr>
						</table>

						<!-- BODY START -->
						<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body" role="presentation" style="background-color:#ffffff;border-collapse:collapse;">
							<tr>
								<td id="body_content" style="padding:28px 32px;font-family:Inter,Arial,sans-serif;color:#0a0a0f;font-size:14px;line-height:1.6;background-color:#ffffff;">
									<div id="body_content_inner">
