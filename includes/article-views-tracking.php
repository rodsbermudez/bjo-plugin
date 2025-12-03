<?php
/**
 * Funcionalidades de rastreamento de acessos aos artigos.
 *
 * @package PatropiBJO
 */

// Impede o acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cria a tabela para registrar os acessos aos artigos na ativação do plugin.
 */
function patropi_bjo_create_article_views_table() {
	global $wpdb;
	$table_name      = $wpdb->prefix . 'patropi_article_views';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		view_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		post_id bigint(20) unsigned NOT NULL,
		user_id bigint(20) unsigned NOT NULL DEFAULT '0',
		ip_address varchar(100) NOT NULL,
		country_code varchar(10) DEFAULT '' NOT NULL,
		PRIMARY KEY  (id),
		KEY post_id (post_id),
		KEY user_id (user_id),
		KEY country_code (country_code)
	) $charset_collate;";

	// Inclui o arquivo necessário para a função dbDelta().
	if ( ! function_exists( 'dbDelta' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}
	dbDelta( $sql );
} 

/**
 * Captura o acesso a um artigo e salva no banco de dados.
 */
function patropi_bjo_track_article_view() {
	// 1. Executar apenas em páginas de post único (artigos) e não no admin.
	//if ( ! is_singular( 'post' ) || is_admin() ) {
    if ( ! is_singular( 'post' ) ) {
		return;
	}

	// 2. Evita rastrear múltiplos acessos na mesma sessão.
	// $post_id = get_the_ID();
	// $cookie_name = 'patropi_viewed_post_' . $post_id;
	// if ( isset( $_COOKIE[ $cookie_name ] ) ) {
	// 	return;
	// }

	$post_id    = get_the_ID();
	$user_id    = get_current_user_id(); // Retorna 0 se o usuário não estiver logado.
	$ip_address = '';
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}

	if ( empty( $ip_address ) ) {
		return; // Não salva se não conseguir obter o IP.
	}

	// Usa uma API externa para obter o código do país a partir do IP.
	$country_code = '';
	// A API retorna o código ISO 3166-1 alpha-2 (ex: 'BR', 'US').
	$response = wp_remote_get( "http://ip-api.com/json/{$ip_address}?fields=status,countryCode" );

	if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );
		// Verifica se a API retornou sucesso e o código do país.
		if ( isset( $data->status ) && $data->status === 'success' && isset( $data->countryCode ) ) {
			$country_code = sanitize_text_field( $data->countryCode );
		}
	}

	// 4. Insere os dados no banco.
	global $wpdb;
	$table_name = $wpdb->prefix . 'patropi_article_views';

	$wpdb->insert(
		$table_name,
		array(
			'view_time'  => current_time( 'mysql' ),
			'post_id'    => $post_id,
			'user_id'    => $user_id,
			'ip_address' => $ip_address,
			'country_code' => $country_code,
		)
	);

	// 5. Define um cookie de sessão para este post para evitar recontagem.
	// setcookie( $cookie_name, '1', 0, COOKIEPATH, COOKIE_DOMAIN );
}
// "Engata" a função de rastreamento para ser executada em cada carregamento de página.
// O hook 'template_redirect' é uma boa opção, pois roda após a query principal ser definida.
add_action( 'template_redirect', 'patropi_bjo_track_article_view' );

/**
 * Shortcode para exibir um mapa de acessos do artigo.
 *
 * Uso: [mapa_acessos]
 *
 * @return string HTML e JS para renderizar o GeoChart do Google.
 */
function bjo_mapa_acessos_shortcode() {
	// 1. CONDIÇÃO DE GUARDA: Só executa em páginas de post.
	if ( ! is_singular( 'post' ) ) {
		return '<!-- [mapa_acessos] só pode ser usado em páginas de artigo. -->';
	}

	$post_id = get_the_ID();
	$transient_key = 'bjo_map_data_' . $post_id;

	// 2. Tenta obter os dados do cache (Transient API).
	$chart_data = get_transient( $transient_key );

	if ( false === $chart_data ) {
		// Se não houver cache, consulta o banco de dados.
		global $wpdb;
		$table_name = $wpdb->prefix . 'patropi_article_views';

		$query = $wpdb->prepare(
			"SELECT country_code, COUNT(id) as acessos 
			 FROM {$table_name} 
			 WHERE post_id = %d AND country_code != ''
			 GROUP BY country_code",
			$post_id
		);

		$results = $wpdb->get_results( $query, ARRAY_A );

		// Prepara os dados para o formato do Google Charts.
		$chart_data = [ [ 'Country', 'Acessos' ] ];
		foreach ( $results as $row ) {
			$chart_data[] = [ $row['country_code'], (int) $row['acessos'] ];
		}

		// 3. Salva o resultado no cache por 1 hora.
		set_transient( $transient_key, $chart_data, HOUR_IN_SECONDS );
	}

	// 4. Se não houver dados (além do cabeçalho), não renderiza nada.
	if ( count( $chart_data ) <= 1 ) {
		return '<p><em>Dados de acesso insuficientes para visualização no mapa.</em></p>';
	}

	// 5. Enfileira a biblioteca do Google Charts de forma segura.
	wp_enqueue_script( 'google-charts-loader', 'https://www.gstatic.com/charts/loader.js', [], null, true );

	// 6. Gera o HTML e o JavaScript para o gráfico.
	$map_id = 'bjo-access-map-' . $post_id;
	$json_data = wp_json_encode( $chart_data );

	ob_start();
	?>
	<div id="<?php echo esc_attr( $map_id ); ?>" style="width: 100%; height: 500px;"></div>
	<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			// Garante que o loader do Google foi carregado.
			if (typeof google === 'undefined' || typeof google.charts === 'undefined') {
				console.error('Google Charts loader não foi encontrado.');
				return;
			}

			google.charts.load('current', { 'packages':['geochart'] });
			google.charts.setOnLoadCallback(drawRegionsMap);

			function drawRegionsMap() {
				var data = google.visualization.arrayToDataTable(<?php echo $json_data; ?>);
				var options = {
					displayMode: 'regions',
					colorAxis: {colors: ['#e0e0e0', '#0044cc']}, // Cinza claro para Azul escuro
					backgroundColor: 'transparent',
				};
				var chart = new google.visualization.GeoChart(document.getElementById('<?php echo esc_js( $map_id ); ?>'));
				chart.draw(data, options);
			}
		});
	</script>
	<?php
	return ob_get_clean();
}
add_shortcode( 'mapa_acessos', 'bjo_mapa_acessos_shortcode' );

/**
 * Verifica a versão do banco de dados do plugin e executa atualizações se necessário.
 *
 * Esta função é executada em cada carregamento do WordPress e garante que a estrutura
 * do banco de dados esteja sempre alinhada com a versão atual do plugin.
 */
function bjo_plugin_update_db_check() {
	// A versão atual da nossa estrutura de banco de dados.
	// Mude este número sempre que alterar a estrutura de uma tabela.
	$current_db_version = '1.1'; // Versão 1.0: inicial. Versão 1.1: adicionou 'country_code'.

	// Pega a versão que está salva no banco de dados.
	$installed_db_version = get_option( 'bjo_plugin_db_version' );

	// Compara as versões. Se forem diferentes, roda a atualização.
	if ( $installed_db_version != $current_db_version ) {
		patropi_bjo_create_article_views_table(); // Roda a função que contém o dbDelta().
		update_option( 'bjo_plugin_db_version', $current_db_version ); // Atualiza a versão no banco.
	}
}
// Executa a verificação assim que os plugins são carregados.
add_action( 'plugins_loaded', 'bjo_plugin_update_db_check' );