<?php
/**
 * Arquivo de configuração para a integração com o N8N.
 *
 * @package PatropiBJO
 */

// Impede o acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
 
return array(
	/**
	 * Chave de API para autenticação nos webhooks do N8N.
	 */
	'api_key' => 'MinhaChaveSecretaParaXML2024',

	/**
	 * URLs dos webhooks para os ambientes de produção e teste.
	 */
	'webhooks' => array(
		'citations'  => array(
			'production' => 'https://n8n-service-qz5q.onrender.com/webhook/get-citacoes',
			'test'       => 'https://n8n-service-qz5q.onrender.com/webhook-test/get-citacoes',
		),
		'xml_import' => array(
			'production' => 'https://n8n-service-qz5q.onrender.com/webhook/xml-load',
			'test'       => 'https://n8n-service-qz5q.onrender.com/webhook-test/xml-load',
		),
	),
);