# Guia Completo: Shortcode [bvs_web_resources]

Guia detalhado para uso do shortcode `[bvs_web_resources]` que exibe recursos web da LIS/BVS Saúde.

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Parâmetros de Filtragem](#parâmetros-de-filtragem)
- [Parâmetros de Visualização](#parâmetros-de-visualização)
- [Sidebar de Filtros Interativos](#sidebar-de-filtros-interativos)
- [Filtros Combinados (AND)](#filtros-combinados-and)
- [Parâmetros via URL](#parâmetros-via-url)
- [Exemplos Práticos](#exemplos-práticos)

## Visão Geral

O shortcode `[bvs_web_resources]` permite exibir recursos web (bases de dados, portais, sites institucionais, bibliotecas digitais) da LIS (Localizador de Informação em Saúde) da BVS Saúde.

### Características Principais

- 🔍 **Múltiplos filtros**: país, assunto, tipo, termo livre, título
- 🎛️ **Sidebar de filtros**: Interface visual com checkboxes para seleção de países
- 🔗 **Filtros AND**: Todos os filtros aplicados funcionam em conjunto
- 🌍 **Múltiplos países**: Suporta seleção de vários países simultaneamente (OR)
- 📱 **Responsivo**: Layout adaptável para desktop e mobile
- 🎨 **Templates flexíveis**: Grid, lista, compacto
- ⚡ **Cache inteligente**: Facets de países cacheados por 1 hora

## Parâmetros de Filtragem

| Parâmetro | Tipo | Descrição | Exemplo |
|-----------|------|-----------|---------|
| `country` | string | País de publicação | `country="Brasil"` |
| `subject` | string | Área temática/assunto | `subject="Saúde Pública"` |
| `type` | string | Tipo de recurso | `type="database"` |
| `term` | string | Busca livre em todos os campos | `term="covid-19"` |
| `searchTitle` | string | Busca específica no título | `searchTitle="biblioteca virtual"` |

### Tipos de Recursos Disponíveis

- `database` - Base de Dados
- `portal` - Portal
- `website` - Site Institucional
- `repository` - Repositório
- `catalog` - Catálogo
- `library` - Biblioteca Digital
- `tool` - Ferramenta
- `service` - Serviço

## Parâmetros de Visualização

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `count` | int | `12` | Itens por página |
| `max` | int | `50` | Máximo de itens totais |
| `show_pagination` | bool | `false` | Habilitar paginação |
| `page` | int | `1` | Página inicial |
| `template` | string | `default` | Layout de exibição |
| `columns` | int | `4` | Colunas no grid |
| `show_fields` | string | `title,type,country` | Campos a exibir |
| `showFilters` | bool | `false` | Mostrar sidebar de filtros |

### Templates Disponíveis

- `default` - Lista padrão com informações básicas
- `grid` - Grade de cards responsiva
- `compact` - Vista compacta com menos informações

### Campos Disponíveis para show_fields

- `title` - Título do recurso
- `type` - Tipo de recurso
- `country` - País de publicação
- `description` - Descrição/resumo
- `institution` - Instituição responsável
- `language` - Idiomas disponíveis
- `subject` - Assuntos/descritores

## Sidebar de Filtros Interativos

Ative a sidebar com `showFilters="true"` para interface visual de filtros:

```php
[bvs_web_resources showFilters="true" template="grid" columns="3"]
```

### Funcionalidades da Sidebar

- ✅ **Campo de título**: Busca por palavras no título
- ✅ **Campo de busca livre**: Busca em todos os campos
- ✅ **Checkboxes de países**: Lista dinâmica com contadores
- ✅ **Seleção múltipla**: Escolha vários países (busca OR)
- ✅ **Botão Buscar**: Aplica os filtros selecionados
- ✅ **Botão Limpar**: Remove todos os filtros
- ✅ **Tags ativas**: Mostra filtros aplicados com opção de remoção individual
- ✅ **Layout responsivo**: 20% largura desktop, full width mobile

### Como os Filtros são Aplicados

1. **Título + Termo + País**: Busca AND entre título, termo e país
2. **Múltiplos países**: OR entre os países selecionados
3. **Exemplo**: `título="biblioteca" AND (país="Brasil" OR país="Argentina")`

## Filtros Combinados (AND)

Quando múltiplos filtros são fornecidos, eles funcionam como **AND** (todos devem ser verdadeiros):

```php
// Bases de dados do Brasil sobre Saúde Pública
[bvs_web_resources type="database" country="Brasil" subject="Saúde Pública"]
```

Isto retorna apenas recursos que:
- ✅ São do tipo "database"
- ✅ **E** são do Brasil
- ✅ **E** têm assunto "Saúde Pública"

### Exceção: Múltiplos Países

Quando múltiplos países são selecionados via checkboxes, eles funcionam como **OR**:

```
País: (Brasil OR Argentina OR Peru)
```

## Parâmetros via URL

Todos os parâmetros podem ser passados via URL, permitindo criar links diretos:

### Mapeamento de Parâmetros URL

| URL | Shortcode | Descrição |
|-----|-----------|-----------|
| `bvsCountry` | `country` | País único |
| `bvsCountries[]` | `country` | Múltiplos países |
| `bvsSubject` | `subject` | Assunto |
| `bvsTerm` | `term` | Busca livre |
| `bvsType` | `type` | Tipo de recurso |
| `bvsTitle` ou `bvsSearchTitle` | `searchTitle` | Título |
| `bvsCount` | `count` | Itens por página |
| `bvsTemplate` | `template` | Template |
| `bvsColumns` | `columns` | Colunas do grid |

### Exemplos de URLs

```
# Recursos do Brasil
https://seusite.com/recursos/?bvsCountry=Brasil

# Bases de dados sobre Medicina
https://seusite.com/recursos/?bvsType=database&bvsSubject=Medicina

# Múltiplos países
https://seusite.com/recursos/?bvsCountries[]=Brasil&bvsCountries[]=Argentina&bvsCountries[]=Chile

# Busca por título com template grid
https://seusite.com/recursos/?bvsTitle=biblioteca&bvsTemplate=grid&bvsColumns=3

# Filtros combinados via URL
https://seusite.com/recursos/?bvsType=portal&bvsCountry=Brasil&bvsTerm=covid
```

## Exemplos Práticos

### 1. Grid Básico com Filtros Interativos

```php
[bvs_web_resources showFilters="true" template="grid" columns="3" max="30"]
```

Exibe um grid de 3 colunas com sidebar de filtros à esquerda, mostrando até 30 recursos.

### 2. Bases de Dados Brasileiras

```php
[bvs_web_resources type="database" country="Brasil" max="20"]
```

Busca apenas bases de dados publicadas no Brasil, limitado a 20 resultados.

### 3. Portais sobre Saúde Pública

```php
[bvs_web_resources type="portal" subject="Saúde Pública" max="15" template="grid"]
```

Exibe portais da área de Saúde Pública em formato grid.

### 4. Busca por Título com Paginação

```php
[bvs_web_resources searchTitle="biblioteca virtual" count="10" show_pagination="true" template="grid"]
```

Busca recursos com "biblioteca virtual" no título, com 10 itens por página e paginação ativa.

### 5. Recursos de Múltiplos Países (via URL)

Crie uma página com:

```php
[bvs_web_resources showFilters="true" template="grid"]
```

E acesse via URL:

```
https://seusite.com/recursos/?bvsCountries[]=Brasil&bvsCountries[]=Argentina
```

### 6. Busca Livre com Campos Personalizados

```php
[bvs_web_resources term="covid" show_fields="title,description,institution,country" max="25"]
```

Busca por "covid" mostrando campos específicos.

### 7. Filtros Combinados Complexos

```php
[bvs_web_resources 
    type="database" 
    country="Brasil" 
    subject="Enfermagem" 
    searchTitle="saúde" 
    max="50" 
    template="grid" 
    columns="4"]
```

Busca bases de dados:
- Do Brasil
- Sobre Enfermagem
- Com "saúde" no título
- Em grid de 4 colunas

### 8. Página de Busca Dinâmica

Crie uma página `/recursos/` com:

```php
[bvs_web_resources showFilters="true" template="grid" columns="3"]
```

Os usuários podem:
- Selecionar países via checkboxes
- Digitar títulos
- Fazer busca livre
- Ver resultados em tempo real

---

## 💡 Dicas Avançadas

### Cache de Países

Os países são obtidos via API e cacheados por 1 hora. Para forçar atualização, limpe o cache do WordPress.

### Performance

- Use `max` para limitar resultados e melhorar performance
- Ative `show_pagination` apenas quando necessário
- Combine com cache de página para melhor velocidade

### SEO

- Use URLs amigáveis com os parâmetros de busca
- Crie páginas específicas para cada tipo de recurso
- Exemplo: `/bases-de-dados/` com `type="database"`

### Customização

O CSS dos filtros está em `/src/Assets/public.css`. Classes principais:
- `.bvs-container-with-filters` - Container principal
- `.bvs-filters-sidebar` - Sidebar de filtros
- `.bvs-content-area` - Área de conteúdo
- `.bvs-checkbox-container` - Container de checkboxes

---

## 🆘 Suporte

Para mais informações, consulte:
- [README.md](README.md) - Documentação geral
- [Código fonte](src/Shortcodes/BvsWebResourcesShortcode.php) - Implementação
