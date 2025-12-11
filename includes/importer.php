<?php
/**
 * Funcionalidades do importador de XML para o N8N.
 *
 * @package PatropiBJO
 */

// Impede o acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe para enviar dados para um webhook do n8n usando a API HTTP do WordPress.
 */
class Patropi_BJO_N8N_Webhook {
	private string $webhook_url;
	private string $api_key;

	/**
	 * @param string $webhook_url A URL completa do webhook do n8n.
	 * @param string $api_key     A chave de API para autenticação.
	 */
	public function __construct( string $webhook_url, string $api_key ) {
		$this->webhook_url = $webhook_url;
		$this->api_key     = $api_key;
	}

	/**
	 * Envia o conteúdo de um arquivo XML para o webhook.
	 *
	 * @param string $xml_content O conteúdo do XML a ser enviado.
	 * @return array Retorna um array com 'status' (bool) e 'message' (string).
	 */
	public function send_xml( string $xml_content ): array {
		$args = array(
			'body'    => $xml_content,
			'headers' => array(
				'Content-Type' => 'application/xml',
				'X-API-KEY'    => $this->api_key,
			),
			'timeout' => 30, // 30 segundos de timeout.
		);

		$response = wp_remote_post( $this->webhook_url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'status'  => false,
				'message' => 'Erro na requisição: ' . $response->get_error_message(),
			);
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$body      = wp_remote_retrieve_body( $response );

		if ( $http_code >= 200 && $http_code < 300 ) {
			return array(
				'status'  => true,
				'message' => 'XML enviado com sucesso! O processo de importação pode levar até 20 segundos para ser concluído e o post aparecer no site.',
			);
		}

		return array(
			'status'  => false,
			'message' => "Erro ao enviar para o n8n. Código HTTP: {$http_code}. Resposta: " . esc_html( $body ),
		);
	}
}

/**
 * Processa o upload dos arquivos XML e PDF.
 */
function patropi_bjo_handle_upload() {
	// 1. Verificações de segurança.
	if ( ! isset( $_POST['patropi_bjo_upload_nonce_field'] ) || ! wp_verify_nonce( sanitize_key( $_POST['patropi_bjo_upload_nonce_field'] ), 'patropi_bjo_upload_nonce' ) ) {
		wp_die( 'Falha na verificação de segurança.' );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Você não tem permissão para realizar esta ação.' );
	}

	// 2. Validação dos arquivos.
	if ( empty( $_FILES['xmlfile']['name'] ) || empty( $_FILES['pdffile']['name'] ) ) {
		patropi_bjo_set_admin_notice( 'É necessário enviar um arquivo XML e um arquivo PDF.', 'error' );
		wp_safe_redirect( admin_url( 'admin.php?page=patropi-bjo-imports' ) );
		exit;
	}

	// 3. Processa o upload dos arquivos usando a API do WordPress.
	require_once ABSPATH . 'wp-admin/includes/file.php';

	// Adiciona suporte temporário para upload de XML.
	add_filter( 'upload_mimes', 'patropi_bjo_allow_xml_upload' );

	// Define o diretório de upload para XML e processa o arquivo.
	add_filter( 'upload_dir', 'patropi_bjo_set_xml_upload_dir' );
	// A chave 'test_type' => false desativa a verificação do tipo de arquivo, resolvendo o erro de permissão.
	$xml_file = wp_handle_upload( $_FILES['xmlfile'], array( 
		'test_form' => false,
		'test_type' => false,
	) );
	remove_filter( 'upload_dir', 'patropi_bjo_set_xml_upload_dir' );

	// Define o diretório de upload para PDF e processa o arquivo.
	add_filter( 'upload_dir', 'patropi_bjo_set_pdf_upload_dir' );
	$pdf_file = wp_handle_upload( $_FILES['pdffile'], array( 'test_form' => false ) );
	remove_filter( 'upload_dir', 'patropi_bjo_set_pdf_upload_dir' );

	// Remove o filtro para não afetar outros uploads no site.
	remove_filter( 'upload_mimes', 'patropi_bjo_allow_xml_upload' );

	// 4. Verifica se houve erros no upload.
	if ( ! empty( $xml_file['error'] ) || ! empty( $pdf_file['error'] ) ) {
		$error_message = $xml_file['error'] ?? $pdf_file['error'];
		patropi_bjo_set_admin_notice( 'Erro no upload: ' . $error_message, 'error' );
		wp_safe_redirect( admin_url( 'admin.php?page=patropi-bjo-imports' ) );
		exit;
	}

	// 4.1. Processa o upload das imagens (se houver).
	if ( ! empty( $_FILES['imgfiles']['name'][0] ) ) {
		$upload_dir = wp_upload_dir();
		// Obtém o nome do arquivo XML (sem extensão) para criar a pasta.
		$xml_filename = pathinfo( $xml_file['file'], PATHINFO_FILENAME );

		$base_img_dir   = $upload_dir['basedir'] . '/IMG';
		$target_img_dir = $base_img_dir . '/' . $xml_filename;

		// Cria as pastas se não existirem.
		if ( ! file_exists( $base_img_dir ) ) {
			wp_mkdir_p( $base_img_dir );
		}
		if ( ! file_exists( $target_img_dir ) ) {
			wp_mkdir_p( $target_img_dir );
		}

		$files = $_FILES['imgfiles'];
		$count = count( $files['name'] );

		for ( $i = 0; $i < $count; $i++ ) {
			if ( $files['error'][ $i ] === 0 ) {
				$tmp_name = $files['tmp_name'][ $i ];
				$name     = sanitize_file_name( $files['name'][ $i ] );
				move_uploaded_file( $tmp_name, $target_img_dir . '/' . $name );
			}
		}
	}

	// 5. Modifica o XML para adicionar os links.
	try {
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput       = true;
		if ( ! $dom->load( $xml_file['file'] ) ) {
			throw new Exception( 'Falha ao carregar o arquivo XML.' );
		}

		$article_meta = $dom->getElementsByTagName( 'article-meta' )->item( 0 );
		if ( ! $article_meta ) {
			$article_meta = $dom->documentElement;
		}

		$pdf_link_element = $dom->createElement( 'pdf-link', htmlspecialchars( $pdf_file['url'] ) );
		$xml_link_element = $dom->createElement( 'xml-link', htmlspecialchars( $xml_file['url'] ) );
		
		// Monta a URL da pasta de imagens (baseurl + /IMG/ + nome do xml).
		$upload_dir     = wp_upload_dir();
		$xml_filename   = pathinfo( $xml_file['file'], PATHINFO_FILENAME );
		$img_folder_url = $upload_dir['baseurl'] . '/IMG/' . $xml_filename;
		$img_link_element = $dom->createElement( 'images-link', htmlspecialchars( $img_folder_url ) );

		$article_meta->appendChild( $pdf_link_element );
		$article_meta->appendChild( $xml_link_element );
		$article_meta->appendChild( $img_link_element );

		$xml_content = $dom->saveXML();
		if ( file_put_contents( $xml_file['file'], $xml_content ) === false ) {
			throw new Exception( 'Falha ao salvar o XML modificado.' );
		}
	} catch ( Exception $e ) { 
		patropi_bjo_set_admin_notice( 'Erro ao processar o XML: ' . $e->getMessage(), 'error' );
		wp_safe_redirect( admin_url( 'admin.php?page=patropi-bjo-imports' ) );
		exit;
	}

	// 6. Envia o XML para o N8N.
	$n8n_config = patropi_bjo_get_n8n_config();
	$current_env = get_option( 'patropi_bjo_n8n_environment', 'production' );

	// Pega a URL correta do arquivo de configuração.
	$webhook_url = $n8n_config['webhooks']['xml_import'][ $current_env ] ?? '';
	// Pega a chave de API centralizada.
	$api_key = $n8n_config['api_key'] ?? '';

	$webhook = new Patropi_BJO_N8N_Webhook( $webhook_url, $api_key );
	$result  = $webhook->send_xml( $xml_content );

	patropi_bjo_set_admin_notice( $result['message'], $result['status'] ? 'success' : 'error' );
	wp_safe_redirect( admin_url( 'admin.php?page=patropi-bjo-imports' ) );
	exit;
}
add_action( 'admin_post_patropi_bjo_handle_upload', 'patropi_bjo_handle_upload' );

/**
 * Define o diretório de upload para a pasta /XML.
 *
 * @param array $dirs Caminhos de upload padrão.
 * @return array
 */
function patropi_bjo_set_xml_upload_dir( $dirs ) {
	$custom_dir = 'XML';
	$dirs['basedir'] = $dirs['basedir'] . '/' . $custom_dir;
	$dirs['baseurl'] = $dirs['baseurl'] . '/' . $custom_dir;
	$dirs['path']    = $dirs['basedir'];
	$dirs['url']     = $dirs['baseurl'];
	return $dirs;
}

/**
 * Define o diretório de upload para a pasta /PDF.
 *
 * @param array $dirs Caminhos de upload padrão.
 * @return array
 */
function patropi_bjo_set_pdf_upload_dir( $dirs ) {
	$custom_dir = 'PDF';
	$dirs['basedir'] = $dirs['basedir'] . '/' . $custom_dir;
	$dirs['baseurl'] = $dirs['baseurl'] . '/' . $custom_dir;
	$dirs['path']    = $dirs['basedir'];
	$dirs['url']     = $dirs['baseurl'];
	return $dirs;
}

/**
 * Adiciona permissão para upload de arquivos XML.
 *
 * @param array $mimes Tipos MIME permitidos.
 * @return array
 */
function patropi_bjo_allow_xml_upload( $mimes ) {
	$mimes['xml'] = 'application/xml';
	return $mimes;
}

/**
 * Define uma notificação administrativa a ser exibida na próxima página.
 *
 * @param string $message A mensagem a ser exibida.
 * @param string $type    O tipo de notificação ('success', 'error', 'warning', 'info').
 */
function patropi_bjo_set_admin_notice( string $message, string $type ) {
	set_transient( 'patropi_bjo_admin_notice', array( 'message' => $message, 'type' => $type ), 30 );
}

/**
 * Exibe a notificação administrativa se ela existir.
 */
function patropi_bjo_display_admin_notice() {
	if ( $notice = get_transient( 'patropi_bjo_admin_notice' ) ) {
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $notice['type'] ),
			wp_kses_post( $notice['message'] )
		);
		delete_transient( 'patropi_bjo_admin_notice' );
	}
}
add_action( 'admin_notices', 'patropi_bjo_display_admin_notice' );