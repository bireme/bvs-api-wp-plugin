# BVSalud Integrator - WordPress Plugin

Plugin WordPress para integração com a API BVS Saúde, permitindo exibir recursos através de shortcode genérico personalizável.

## 📋 Índice

- [Características](#-características)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Shortcode Genérico](#-shortcode-genérico)
- [API Client](#-api-client)
- [Personalização](#-personalização)
- [Desenvolvimento](#-desenvolvimento)
- [Segurança](#-segurança)

## ✨ Características

- 🔍 **Busca por múltiplos filtros**: país, assunto, tipo, título
- 🎛️ **Filtros Dinâmicos**: Configure filtros personalizados para cada tipo de recurso
- 📱 **Layout responsivo**: grid de cards adaptável
- 📄 **Paginação integrada**: navegação fácil entre resultados
- 🎨 **Customizável**: CSS e JavaScript customizáveis
- ⚡ **Sistema de cache**: otimização de performance
- 🔒 **Seguro**: sanitização de inputs e escape de outputs
- 🔄 **Genérico**: Um shortcode para todos os tipos de recursos
- 🌐 **Suporte a múltiplos idiomas**: valores multilíngue extraídos automaticamente
- 🏷️ **Tags de filtros ativos**: visualização dos filtros aplicados

## 📦 Instalação

1. Clone ou faça download do repositório para `/wp-content/plugins/`
2. Ative o plugin no WordPress
3. Configure as APIs em **BVSalud > Configurações**

```bash
cd wp-content/plugins
git clone [repository-url] api-consumer-wp-plugin
```

## ⚙️ Configuração

Após ativar o plugin, vá para **BVSalud > Configurações** e configure:

### 1. Token da API

- **API Token**: Token de autenticação fornecido pela BIREME

### 2. Recursos da API

Configure cada tipo de recurso com sua URL base. Para cada recurso, você pode configurar:

- **Tipo**: Nome do recurso (ex: `journals`, `events`, `webResources`)
- **URL**: URL base da API para este recurso
- **Filtros**: Filtros dinâmicos personalizáveis

#### Configurando Filtros Dinâmicos

Cada recurso pode ter múltiplos filtros configuráveis:

1. Clique em **Gerenciar** ao lado do recurso
2. Adicione filtros com os seguintes campos:
   - **Chave (Key)**: Nome do campo na API Solr (ex: `publication_country`, `descriptor_filter`)
   - **Label**: Nome amigável para exibição (ex: "País", "Tema")
3. Salve os filtros

Os filtros configurados serão exibidos automaticamente quando `show_filters="true"` for usado no shortcode.

## 🎯 Shortcode Genérico

O plugin utiliza um shortcode genérico `[bvs_resources]` que funciona com todos os tipos de recursos da API BVSalud.

### Sintaxe

```php
[bvs_resources type="TIPO" parâmetros...]
```

### Tipos de Recursos Disponíveis

| Tipo | Descrição |
|------|-----------|
| `journals` | Periódicos científicos |
| `webResources` | Recursos web (bases de dados, portais) |
| `events` | Eventos em saúde |
| `legislations` | Leis, decretos, normas |
| `multimedia` | Vídeos, áudios, imagens |

### Parâmetros

| Parâmetro | Descrição | Padrão |
|-----------|-----------|--------|
| `type` | Tipo de recurso | - |
| `country` | Filtrar por país | - |
| `subject` | Filtrar por área temática | - |
| `search` | Busca livre | - |
| `searchTitle` | Buscar por título específico | - |
| `limit` | Itens por página | `12` |
| `max` | Máximo de itens total | `50` |
| `show_pagination` | Habilitar paginação | `false` |
| `page` | Página inicial | `1` |
| `show_filters` | Mostrar sidebar de filtros | `false` |

### Exemplos de Uso

```php
// Periódicos do Brasil
[bvs_resources type="journals" country="Brazil" limit="10"]

// Recursos web com filtros
[bvs_resources type="webResources" show_filters="true"]

// Eventos com paginação
[bvs_resources type="events" show_pagination="true" limit="8"]

// Legislações por assunto
[bvs_resources type="legislations" subject="Saúde Pública"]

// Multimídia com busca
[bvs_resources type="multimedia" search="covid" limit="6"]
```

### Parâmetros via URL

O shortcode aceita parâmetros através da URL para permitir links diretos e filtros:

| Parâmetro URL | Mapeia para | Exemplo |
|---------------|-------------|---------|
| `bvsSearchTitle` | `searchTitle` | `?bvsSearchTitle=saúde+pública` |
| `bvsTitle` | `searchTitle` | `?bvsTitle=saúde+pública` |
| `bvsLimit` | `limit` | `?bvsLimit=20` |
| `bvsMax` | `max` | `?bvsMax=50` |
| `bvsPage` | `page` | `?bvsPage=2` |

**Parâmetros de Filtros Dinâmicos**: Você pode usar qualquer chave de filtro configurada para os recursos através da URL. Por exemplo, se você configurou um filtro com a chave `publication_country`, pode usar:

```
?publication_country[]=Brazil&publication_country[]=Argentina
```

Para múltiplos valores (checkboxes), use a notação de array `[]`.

### Filtros Interativos

Ative `show_filters="true"` para exibir sidebar com filtros:

```php
[bvs_resources type="journals" show_filters="true"]
```

**Funcionalidades:**
- ✅ Campo de busca por título
- ✅ Checkboxes dinâmicos baseados nos filtros configurados
- ✅ Seleção múltipla de valores (através de checkboxes)
- ✅ Botões "Buscar" e "Limpar"
- ✅ Tags de filtros ativos visíveis
- ✅ Contagem de resultados por filtro
- ✅ Suporte a valores multilíngue

## 🎨 Personalização

O plugin permite personalizar a aparência através de CSS e JavaScript customizados.

### CSS Customizado

Na página de configurações, você pode adicionar CSS para personalizar:

- Cores e estilos dos cards
- Layout do grid
- Tipografia
- Filtros sidebar
- Paginação

Exemplo de CSS customizado:

```css
/* Personalizar cards */
.bvs-resource-card {
    border: 2px solid #0073aa;
    transition: transform 0.3s;
}

.bvs-resource-card:hover {
    transform: translateY(-5px);
}

/* Personalizar filtros */
.bvs-filter-checkbox {
    accent-color: #0073aa;
}
```

### JavaScript Customizado

Adicione JavaScript para funcionalidades personalizadas:

```javascript
// Exemplo: Adicionar comportamento ao carregar a página
jQuery(document).ready(function($) {
    // Seu código customizado aqui
    console.log('BVSalud Integrator carregado');
});
```

**Nota**: CSS e JavaScript customizados são executados apenas para usuários administradores com a capacidade `unfiltered_html`.

## 🔧 API Client

O plugin fornece classes para interagir com a API BVS:

### BvsaludGenericClient

```php
use BV\API\BvsaludGenericClient;

$client = new BvsaludGenericClient($apiUrl, $token);

// Buscar recursos
$results = $client->getResources([
    'q' => '*:*',
    'count' => 20,
    'fq' => 'country:"Brazil"'
]);

// Obter países disponíveis
$countries = $client->getAvailableCountries();
```

### DTOs Disponíveis

O plugin inclui DTOs (Data Transfer Objects) para cada tipo de recurso:

- `JournalDto` - Periódicos
- `EventDto` - Eventos
- `WebResourceDto` - Recursos web
- `LegislationDto` - Legislações
- `MultimediaDto` - Multimídia
- `BibliographicDatabaseDto` - Bases bibliográficas

## 📁 Estrutura do Projeto

```
api-consumer-wp-plugin/
├── src/
│   ├── Admin/                  # Painel administrativo
│   │   ├── AdminMenu.php       # Menu principal
│   │   └── SettingsPage.php    # Página de configurações
│   ├── API/                    # Cliente e DTOs da API
│   │   ├── BvsaludGenericClient.php
│   │   ├── JournalDto.php
│   │   ├── EventDto.php
│   │   ├── WebResourceDto.php
│   │   ├── LegislationDto.php
│   │   ├── MultimediaDto.php
│   │   └── BibliographicDatabaseDto.php
│   ├── Assets/                 # CSS e JavaScript
│   │   ├── admin.css
│   │   ├── admin.js
│   │   ├── public.css
│   │   └── public.js
│   ├── Shortcodes/             # Shortcodes e Helpers
│   │   ├── BvsResourcesShortcode.php
│   │   ├── Classes/
│   │   │   └── ResourcesParams.php
│   │   └── Helpers/            # Conversores para ResourceCardDto
│   ├── Support/                # Helpers e utilitários
│   │   ├── Cache.php
│   │   ├── Helpers.php
│   │   └── ResourceCardDto.php
│   ├── Templates/              # Templates de exibição
│   │   ├── bvs-grid.php
│   │   ├── components/
│   │   └── resources-sidebar.php
│   ├── Autoloader.php
│   └── Plugin.php
├── bvsalud-integrator.php      # Arquivo principal do plugin
├── uninstall.php               # Script de desinstalação
└── README.md
```

## 🚀 Desenvolvimento

### Requisitos

- PHP 7.4+
- WordPress 5.0+
- Composer (opcional, para dependências)

### Padrões de Código

- **PSR-4** para autoloading
- **PSR-12** para coding standards
- **Namespaces**: `BV\*`
- Classes finais para DTOs e Shortcodes
- Type hints (PHP 7.4+)

### Extensibilidade

O plugin foi projetado para ser facilmente extensível:

1. **Adicionar novos tipos de recursos**: Crie novos DTOs e conversores
2. **Customizar templates**: Edite os templates em `src/Templates/`
3. **Adicionar novos filtros**: Configure na página de configurações
4. **Hook em ações WordPress**: Use hooks padrão do WordPress

### Desenvolvimento Local

```bash
# Clonar o repositório
git clone [repository-url] wp-content/plugins/api-consumer-wp-plugin

# Ativar o plugin via WP-CLI
wp plugin activate api-consumer-wp-plugin
```

## 🔒 Segurança

O plugin implementa diversas medidas de segurança:

### Entrada de Dados (Input)

- ✅ **Sanitização**: Todos os inputs são sanitizados usando funções do WordPress
- ✅ **Validação**: Validação de tipos e limites
- ✅ **Nonces**: Proteção contra CSRF em todos os formulários

### Saída de Dados (Output)

- ✅ **Escaping**: Todos os outputs usam funções de escape apropriadas (`esc_html`, `esc_url`, etc.)
- ✅ **Prepared Statements**: Não aplicável (não há database direto)

### Acesso e Permissões

- ✅ **Capability Checks**: Verificação de permissões (`manage_options`)
- ✅ **ABSPATH Check**: Previne acesso direto a arquivos PHP
- ✅ **unfiltered_html**: CSS/JS customizados apenas para usuários com capacidade especial

### API

- ✅ **Token Authentication**: Suporte a tokens de autenticação
- ✅ **URL Sanitization**: URLs validadas antes de uso
- ✅ **Rate Limiting**: Implementado através de cache

### Cache

- ✅ **Transients API**: Usa WordPress Transients para cache seguro
- ✅ **Expiration**: Cache com tempo de expiração configurável

## 🐛 Troubleshooting

### Problemas Comuns

#### Nenhum resultado exibido

1. Verifique se o recurso está configurado em **BVSalud > Configurações**
2. Confirme que o token da API está correto
3. Verifique se a URL da API está acessível
4. Revise os filtros aplicados

#### Filtros não aparecem

1. Certifique-se de que `show_filters="true"` está no shortcode
2. Verifique se os filtros estão configurados para o tipo de recurso
3. Confirme que as chaves dos filtros correspondem aos campos na API

#### CSS/JavaScript customizados não funcionam

1. Verifique se você tem a capacidade `unfiltered_html`
2. Limpe o cache do WordPress
3. Verifique se há erros de sintaxe no CSS/JS

#### Cache não atualiza

1. Limpe o cache do WordPress
2. Use ferramentas de desenvolvimento para limpar cache do navegador
3. Verifique os tempos de expiração do cache em `src/Support/Cache.php`

### Suporte

Para reportar bugs ou solicitar funcionalidades, entre em contato com a equipe de desenvolvimento da BIREME.

## 👤 Autor

**Jefferson Augusto Lopes**

Desenvolvido para BIREME - Centro Latino-Americano e do Caribe de Informação em Ciências da Saúde

## 📄 Licença

Este plugin é propriedade da BIREME e está sujeito aos termos de licença definidos pela organização.
