# CodeIgniter Rest Server

## Requerimento

1. PHP 5.4 or greater
2. CodeIgniter 3.0+

** Instruções para Instalação localhost **
-------------
- Clonar o conteudo para sua pasta do apache;
- Criar um Banco de dados;
- Importar o Arquivo webservice.sql da pasta DB;
- na pasta Application/config alterar o arquivo config.php na linha 26 tirar "http://localhost/suapastaapache" e colocar o seu endereço do apache;
```
	$config['base_url'] = 'http://localhost/suapastaapache';
```
- na mesma pasta Application/config alterar o arquivo database.php na linha 79 informar o usuario para acesso ao banco criado na linha 80 informar a senha para acesso ao banco e na linha 81 informar o nome do banco criado;

** Instruções de Uso no Dominio **
-------------

## Rota do JSON do POSTMAN para importar as Collection

http://45.231.128.24/webservice/postman.json

## Rota para Listar todos os produtos - utiliza page = 1 e limit = 10 como default;
### Method: GET

http://45.231.128.24/webservice/index.php/v1/Products

## Rota para Listar todos os produtos informando page e limit
### Method: GET

http://45.231.128.24/webservice/index.php/v1/Products?page=3&limit=5

## Rota para Listar todos os produtos de uma determinada categoria
### Method: GET

http://45.231.128.24/webservice/index.php/v1/Products?category=limpeza

## Rota para Listar todos os produtos de uma determinada categoria informando page e limit
### Method: GET

http://45.231.128.24/webservice/index.php/v1/Products?page=1&limit=5&category=limpeza

## Rota para Cadastrar novo Produto
### Method: POST

http://45.231.128.24/webservice/index.php/v1/Products

### Setar no body da requisição os dados a serem cadastrados 
```
{
	"nome": "Água sanitária 1000ml",
	"sku": "0000150",
	"ean": "1212112456789",
	"quantidade": 1,
	"unidade_medida": "unidade",
	"cnpj_industria": "01111222001111",
	"categoria": "Limpeza"
}
```

## Rota para Atualizar um determinado produto
### Method: PUT
### Alterar o numero 2 no final da URL para o ID que deseja alterar

http://45.231.128.24/webservice/index.php/v1/Products/2

### Setar no body da requisição os dados a serem cadastrados 
```
{
	"nome": "joioooc",
	"sku": "00002",
	"ean": "12345678912",
	"quantidade": 10,
	"unidade_medida": "unidade",
	"cnpj_industria": "01111222000101",
	"categoria": "graos"
}
```

## Rota para Deletar um determinado Produto
### Method: DELETE
### Alterar o numero 2 no final da URL para o ID que deseja Deletar

http://45.231.128.24/webservice/index.php/v1/Products/2

## Rota para Aumentar a quantidade de um determinado produto
### Method: POST
### Alterar o numero 2 na URL para o ID que deseja Aumentar a quantidade

http://45.231.128.24/webservice/index.php/v1/Products/2/insertion

### Setar no body da requisição o valor a ser Somado 
```
{
	"total": "1"
}
```

## Rota para Diminuir a quantidade de um determinado produto
### Method: POST
### Alterar o numero 2 na URL para o ID que deseja Diminuir a quantidade

http://45.231.128.24/webservice/index.php/v1/Products/2/removal

### Setar no body da requisição o valor a ser Subtraido 
```
{
	"total": "1"
}
```

## Rota para atualizar o total de produtos no estoque, dada uma lista de produtos comprados por um cliente
### Method: POST

http://45.231.128.24/webservice/index.php/v1/Products/2/purchase

### Setar no body da requisição os IDs e as Quantidades a serem subtraidas
```
{
	"products":[
		{
			"product_id": 1,
			"quantidade": 1
		},
		{
			"product_id": 3,
			"quantidade": 2
		}
	]
}
```

## Implementando a v2 com Autenticação

A rota da v2 esta funcionando de igual modo as rotas v1 com a diferença que a v2 necessita de autenticação para operar.
Foi utilizado o tipo de Autenticação Basic;
Para fins de teste foi definido um Usuario fixo "admin" e senha "admin"

### Maintainers:
* Antonio Carlos
* Daniel Rodrigues
