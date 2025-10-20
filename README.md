# BVSalud Integrator - WordPress Plugin

Plugin WordPress para integração com a API BVS Saúde, permitindo exibir recursos através de shortcode genérico personalizável.

## 📋 Índice

- [Características](#-características)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Shortcode Genérico](#-shortcode-genérico)
- [Desenvolvimento](#-desenvolvimento)

## ✨ Características

- 🔍 **Busca por múltiplos filtros**: país, assunto, tipo, título
- 🎛️ **Sidebar de Filtros**: Interface visual com checkboxes para múltiplos países
- 📱 **Layout responsivo**: grid de cards adaptável
- 📄 **Paginação integrada**: navegação fácil entre resultados
- 🎨 **Customizável**: CSS flexível
- ⚡ **Sistema de cache**: otimização de performance
- 🔒 **Seguro**: sanitização de inputs e escape de outputs
- 🔄 **Genérico**: Um shortcode para todos os tipos de recursos

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

- **Recursos da API**: Adicione cada tipo de recurso com sua URL base
- **API Token**: Token de autenticação fornecido pela BIREME

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

O shortcode aceita parâmetros através da URL:

| Parâmetro URL | Mapeia para | Exemplo |
|---------------|-------------|---------|
| `bvsType` | `type` | `?bvsType=journals` |
| `bvsCountry` | `country` | `?bvsCountry=Brazil` |
| `bvsSubject` | `subject` | `?bvsSubject=Medicina` |
| `bvsSearch` | `search` | `?bvsSearch=cardiologia` |
| `bvsSearchTitle` | `searchTitle` | `?bvsSearchTitle=saúde+pública` |
| `bvsLimit` | `limit` | `?bvsLimit=20` |
| `bvsPage` | `page` | `?bvsPage=2` |

### Filtros Interativos

Ative `show_filters="true"` para exibir sidebar com filtros:

```php
[bvs_resources type="journals" show_filters="true"]
```

**Funcionalidades:**
- ✅ Campo de busca por título
- ✅ Checkboxes de países (populados dinamicamente)
- ✅ Seleção múltipla de países
- ✅ Botões "Buscar" e "Limpar"
- ✅ Tags de filtros ativos

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

## 📁 Estrutura do Projeto

```
api-consumer-wp-plugin/
├── src/
│   ├── Admin/              # Painel administrativo
│   ├── API/                # Cliente e DTOs da API
│   ├── Assets/             # CSS e JavaScript
│   ├── Shortcodes/         # Shortcodes e Helpers
│   ├── Support/            # Helpers e utilitários
│   ├── Templates/          # Templates de exibição
│   └── Plugin.php
├── bvsalud-integrator.php  # Arquivo principal
└── README.md
```

## 🚀 Desenvolvimento

### Requisitos

- PHP 7.4+
- WordPress 5.0+

### Padrões de Código

- PSR-4 para autoloading
- PSR-12 para coding standards
- Namespaces: `BV\*`
- Classes finais para DTOs e Shortcodes

### Segurança

- ✅ Sanitização de inputs
- ✅ Escape de outputs
- ✅ Verificação de `ABSPATH`
- ✅ Nonces para formulários