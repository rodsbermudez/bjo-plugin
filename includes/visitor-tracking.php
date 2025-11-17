<?php
/**
 * Funcionalidades de rastreamento de visitantes.
 *
 * @package PatropiBJO
 */

// Impede o acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cria a tabela customizada no banco de dados na ativação do plugin.
 */
function patropi_bjo_create_visitor_table() {
	global $wpdb;
	// Define o nome da nossa tabela, usando o prefixo padrão do WordPress.
	$table_name = $wpdb->prefix . 'patropi_visitor_ips';
	// Pega o conjunto de caracteres padrão do banco de dados.
	$charset_collate = $wpdb->get_charset_collate();

	// SQL para criar a tabela.
	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		visit_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		ip_address varchar(100) NOT NULL,
		country varchar(100) DEFAULT '' NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	// Inclui o arquivo necessário para a função dbDelta().
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	// Executa a query. dbDelta é inteligente e não recria a tabela se ela já existir.
	dbDelta( $sql );
}

/**
 * Captura o IP e o país do visitante e salva no banco de dados.
 */
function patropi_bjo_track_visitor() {
	// Se o cookie já existe, significa que já rastreamos esta sessão.
	if ( isset( $_COOKIE['patropi_visitor_tracked'] ) ) {
		return;
	}

	// Verifica se o usuário está logado e qual o seu perfil.
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$roles = (array) $user->roles;

		// Se o usuário está logado, só rastreamos se ele for um 'subscriber'.
		// Se ele tiver qualquer outro perfil (admin, editor, etc.), não rastreamos.
		if ( ! in_array( 'subscriber', $roles, true ) ) {
			return;
		}
	}

	// Pega o endereço de IP do visitante.
	$ip_address = '';
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
	} else {
		$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}

	// Se não conseguirmos um IP, não há o que fazer.
	if ( empty( $ip_address ) ) {
		return;
	}

	// Usa uma API externa para obter o país a partir do IP.
	$response = wp_remote_get( "http://ip-api.com/json/{$ip_address}?fields=country" );
	$country  = 'Desconhecido';

	if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );
		if ( isset( $data->country ) ) {
			$country = sanitize_text_field( $data->country );
		}
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'patropi_visitor_ips';

	// Insere os dados na nossa tabela customizada.
	$wpdb->insert(
		$table_name,
		array(
			'visit_time' => current_time( 'mysql' ),
			'ip_address' => $ip_address,
			'country'    => $country,
		)
	);

	// Define o cookie para indicar que esta sessão já foi rastreada.
	// O '0' faz com que seja um cookie de sessão (expira quando o navegador é fechado).
	setcookie( 'patropi_visitor_tracked', '1', 0, COOKIEPATH, COOKIE_DOMAIN );
}
// "Engata" a função de rastreamento para ser executada em cada carregamento de página do WordPress.
add_action( 'init', 'patropi_bjo_track_visitor' );
