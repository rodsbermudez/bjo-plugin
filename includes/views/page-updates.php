<?php
/**
 * Template para a página de Atualizações do plugin.
 *
 * @package PatropiBJO
 */

// Impede o acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php require_once __DIR__ . '/admin-header.php'; ?>

<div class="col">
	<div class="card h-100">
		<div class="card-body">
			<h2 class="card-title h4">Funcionalidades Ativas</h2>
			<p class="lead mb-3">Recursos que este plugin adiciona ao seu site.</p>
			<table class="table table-hover">
				<thead>
					<tr>
						<th scope="col" style="width: 25%;">Funcionalidade</th>
						<th scope="col">Descrição</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong>Integração com N8N</strong></td>
						<td>Permite que o N8N envie dados via API REST para criar e popular termos da taxonomia 'journal' com campos ACF.</td>
					</tr>
					<tr>
						<td><strong>Rastreamento de Visitantes</strong></td>
						<td>Cria uma tabela no banco de dados para salvar o IP e o país de cada visitante, registrando apenas uma vez por sessão.</td>
					</tr>
					<tr>
						<td><strong>Importador de Artigos</strong></td>
						<td>Adiciona um formulário para upload de arquivos XML e PDF, processa os arquivos e os envia para o N8N para criação de posts.</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="col">
	<div class="card h-100"> 
		<div class="card-body">
			<h2 class="card-title h4">Histórico de Versões</h2>
			<p class="lead mb-3">Acompanhe as novidades e correções de cada versão.</p>
			<table class="table table-hover mb-0">
				<tbody>
					<tr>
						<td style="width: 15%;"><strong>0.0.6</strong></td>
						<td>Adiciona um painel de 'Status do Sistema' no dashboard para verificar a ativação de taxonomias e configurações do WordPress. Inclui um botão para download do arquivo de importação do ACF.</td>
					</tr>
					<tr>
						<td style="width: 15%;"><strong>0.0.5</strong></td>
						<td>Adiciona a funcionalidade de importador de artigos (XML e PDF) com envio para o webhook do N8N e opção de ambiente de teste/produção.</td>
					</tr>
					<tr>
						<td style="width: 15%;"><strong>0.0.4</strong></td>
						<td>Criação da página de 'Importações' para auditar posts criados via N8N e adição de uma nova tabela de log (<code>patropi_n8n_log</code>).</td>
					</tr>
					<tr>
						<td style="width: 15%;"><strong>0.0.3</strong></td>
						<td>Adiciona funcionalidade de rastreamento de visitantes (IP e País), com criação de tabela customizada no banco de dados e controle por sessão.</td>
					</tr>
					<tr>
						<td style="width: 15%;"><strong>0.0.2</strong></td>
						<td>Implementação de interface de administração com Bootstrap (tema Flatly), navegação interna e layout responsivo em cards e tabelas.</td>
					</tr>
					<tr>
						<td style="width: 15%;"><strong>0.0.1</strong></td>
						<td>Setup inicial, criação de páginas administrativas, verificação de dependências, integração com N8N e refatoração da estrutura de arquivos.</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php require_once __DIR__ . '/admin-footer.php'; ?>