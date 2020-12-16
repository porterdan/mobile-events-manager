<?php
/**
 * This template is used to display the header content of an email.
 *
 * @version 1.0
 * @author Jack Mawhinney, Dan Porter
 * @since 1.3
 * @content_tag Not Supported
 * @shortcodes Not Supported
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/mem-templates/email/email-header.php
 * @package MEM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title><?php echo esc_html( mem_get_option( 'company_name' ) ); ?></title>
	</head>
	<body>
