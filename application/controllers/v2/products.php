<?php

defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Products extends REST_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('mproducts');
        $this->auth();
    }

    function auth()
    {
        if (empty($this->input->server('PHP_AUTH_USER'))) {
                header('HTTP/1.0 401 Unauthorized');
                header('HTTP/1.1 401 Unauthorized');
                header('WWW-Authenticate: Basic realm="My Realm"');
                echo 'Você deve fazer Login para usar este serviço';
                die();
            }

        $username = $this->input->server('PHP_AUTH_USER');
        $password = $this->input->server('PHP_AUTH_PW');

        if($username != 'admin' || $password != 'admin'){
            echo 'Usuário ou senha Invalido'; 
            $this->response(null, REST_Controller::HTTP_UNAUTHORIZED);
            exit;
        }
    }

    function index_get()
    {
        $id = $this->uri->segment(3);
        $page = $this->get('page') ? $this->get('page') - 1 : 0;
        $limit = $this->get('limit') ? $this->get('limit') : 10;
        $category = $this->get('category');

        if ($id === null) {
            if ($category) {
                $products = $this->mproducts->getWhereProducts($limit, $page, $category);
                $total_items = $this->db->where('categoria', $category)->count_all_results('products');
            } else {
                $products = $this->mproducts->getProducts($limit, $page);
                $total_items = $this->db->count_all_results('products');
            }

            if ($products) {
                $this->response([
                    'data' => $products,
                    'result_per_page' => $limit,
                    'total_items' => $total_items,
                    'total_pages' => $total_items < $limit ? 1 : $total_items / $limit,
                    'current_page' => $page + 1
                ], REST_Controller::HTTP_OK);
            } else {
                $this->response([
                    'status' => false,
                    'message' => 'Nenhum Produto Cadastrado'
                ], REST_Controller::HTTP_NOT_FOUND);
            }
        } else {
            $id = (int)$id;
            if ($id <= 0) {
                $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
            }
            $product = $this->mproducts->getProduct($id);
            if (!empty($products)) {
                foreach ($products as $value) {
                    if (isset($value->id) && $value->id === $id) {
                        $product = $value;
                    }
                }
            }
            if (!empty($product)) {
                $this->set_response($product, REST_Controller::HTTP_OK);
            } else {
                $this->set_response([
                    'status' => false,
                    'message' => 'Produto nao encontrado'
                ], REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    function purchase()
    {
        $products = $this->post('products');

        if (!$products) {
            $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
            exit;
        }

        if (sizeof($products) > 0) {
            foreach ($products as $product) {
                $product_id = isset($product['product_id']) ? false : true;
                $quantidade = isset($product['quantidade']) ? false : true;
                if ($product_id || $quantidade) {
                    $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
                    exit;
                }

                if ($product['quantidade'] < 1) {
                    $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
                    exit;
                } elseif ($productBD = $this->mproducts->getProduct($product['product_id'])) {
                    if ($productBD[0]->quantidade < $product['quantidade']) {
                        $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
                        exit;
                    }
                } else {
                    $this->set_response(null, REST_Controller::HTTP_NOT_FOUND);
                    exit;
                }
            }

            foreach ($products as $product) {
                $productBD = $this->mproducts->getProduct($product['product_id']);
                $data = array(
                    'quantidade' => $productBD[0]->quantidade - $product['quantidade']
                );

                $this->mproducts->updateProduct($product['product_id'], $data);
            }
            $this->set_response(null, REST_Controller::HTTP_NO_CONTENT);
        } else {
            $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
            exit;
        }
    }

    function removal()
    {
        $this->auth();
        $id = $this->uri->segment(3);
        $total = $this->post('total');
        if ($total > 0) {
            if ($product = $this->mproducts->getProduct($id)) {
                if ($product[0]->quantidade < $total) {
                    $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
                    exit;
                } else {
                    $newTotal = $product[0]->quantidade - $total;
                }
                $data = array(
                    'quantidade' => $newTotal
                );
                if ($this->mproducts->updateproduct($id, $data)) {
                    $product[0]->quantidade = $newTotal;
                    $this->set_response($product, REST_Controller::HTTP_OK);
                } else {
                    $this->response(['message' => 'Produto não Alterado'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                    exit;
                }
            } else {
                $this->set_response([
                    'message' => 'ID nao encontrado'
                ], REST_Controller::HTTP_NOT_FOUND);
            }
        } else {
            $this->response(['message' => 'Verifique o valor informado'], REST_Controller::HTTP_BAD_REQUEST);
            exit;
        }
    }

    function insertion()
    {
        $id = $this->uri->segment(3);
        $total = $this->post('total');
        if ($total > 0) {
            if ($product = $this->mproducts->getProduct($id)) {
                $newTotal = $product[0]->quantidade + $total;
                $data = array(
                    'quantidade' => $newTotal
                );
                if ($this->mproducts->updateproduct($id, $data)) {
                    $product[0]->quantidade = $newTotal;
                    $this->set_response($product, REST_Controller::HTTP_OK);
                } else {
                    $this->response(['message' => 'Produto não Alterado'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                    exit;
                }
            } else {
                $this->set_response([
                    'message' => 'ID nao encontrado'
                ], REST_Controller::HTTP_NOT_FOUND);
            }
        } else {
            $this->response(['message' => 'Verifique o valor informado'], REST_Controller::HTTP_BAD_REQUEST);
            exit;
        }
    }

    function index_post()
    {
        $action = $this->uri->segment(4);

        if ($action == 'insertion') {
            $this->insertion();
        } elseif ($action == 'removal') {
            $this->removal();
        } elseif ($action == 'purchase') {
            $this->purchase();
        } else {
            $nome = $this->post('nome');
            $sku = $this->post('sku');
            $unidade_medida = $this->post('unidade_medida');
            $categoria = $this->post('categoria');

            if (!$nome || !$sku || !$unidade_medida || !$categoria) {
                $this->response(['message' => 'Inserir Informações completas'], REST_Controller::HTTP_BAD_REQUEST);
                exit;
            }

            $product = [
                'nome' => strtoupper($this->post('nome')),
                'sku' => $this->post('sku'),
                'ean' => $this->post('ean'),
                'quantidade' => $this->post('quantidade'),
                'unidade_medida' => $this->post('unidade_medida'),
                'cnpj_industria' => $this->post('cnpj_industria'),
                'categoria' => $this->post('categoria')
            ];

            if ((sizeof($product) > 0) && (is_numeric($this->post('cnpj_industria')))) {
                if ($this->mproducts->getWProduct('sku =' . $this->post('sku'))) {
                    if ($this->mproducts->getWProduct('ean =' . $this->post('ean'))) {
                        if ($id = $this->mproducts->insertProduct($product)) {
                            $this->set_response(['product_id' => $id], REST_Controller::HTTP_CREATED);
                        } else {
                            $this->response(['message' => 'Erro ao cadastrar Produto'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                        }
                    } else {
                        $this->response(['message' => 'EAN ja cadastrado'], REST_Controller::HTTP_CONFLICT);
                    }
                } else {
                    $this->response(['message' => 'SKU ja cadastrado'], REST_Controller::HTTP_CONFLICT);
                }
            } else {
                $this->response(['message' => 'Informar os dados do Produto'], REST_Controller::HTTP_BAD_REQUEST);
            }
        }
    }

    public function index_delete()
    {
        $id = (int)$this->uri->segment(3);
        if ($id <= 0) {
            $this->response(null, REST_Controller::HTTP_NOT_FOUND);
        }
        if ($this->mproducts->deleteProduct($id)) {
            $this->set_response(null, REST_Controller::HTTP_NO_CONTENT);
        } else {
            $this->response(null, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function index_put()
    {
        $id = (int)$this->uri->segment(3);
        if ($id <= 0) {
            $this->response(null, REST_Controller::HTTP_NOT_FOUND);
        }
        $_Product = array();
        parse_str(file_get_contents('php://input'), $_Product);

        $nome = $this->put('nome');
        $sku = $this->put('sku');
        $ean = $this->put('ean');
        $unidade_medida = $this->put('unidade_medida');
        $categoria = $this->put('categoria');
        $cnpj_industria = $this->put('cnpj_industria');
        $quantidade = $this->put('quantidade');

        //JSON inválido;
        if (!$nome || !$sku || !$unidade_medida || !$categoria) {
            echo '0';
            $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
            exit;
        }

        //a quantidade de produtos for menor que 0;
        if ($quantidade < 0) {
            echo '1';
            $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
            exit;
        }

        //CNPJ não conter somente dígitos;
        if (!is_numeric($cnpj_industria)) {
            echo '2';
            $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
            exit;
        }

        //Se não existir um produto com ID igual ao valor fornecido na URI;
        if (!$this->mproducts->getProduct($id)) {
            echo '3';
            $this->response(null, REST_Controller::HTTP_NOT_FOUND);
            exit;
        }

        //Verificar SKU Duplicado;
        if (!$this->mproducts->getWProduct('sku =' . $sku . ' and id != ' . $id)) {
            $this->response(['message' => 'SKU ja cadastrado'], REST_Controller::HTTP_CONFLICT);
            exit;
        }

        //Verificar EAN Duplicado;
        if (!$this->mproducts->getWProduct('ean =' . $ean . ' and id != ' . $id)) {
            $this->response(['message' => 'EAN ja cadastrado'], REST_Controller::HTTP_CONFLICT);
            exit;
        }

        $product = [
            'nome' => strtoupper($nome),
            'sku' => $sku,
            'ean' => $ean,
            'quantidade' => $quantidade,
            'unidade_medida' => $unidade_medida,
            'cnpj_industria' => $cnpj_industria,
            'categoria' => $categoria
        ];

        if ($this->mproducts->updateProduct($id, $product)) {
            $this->set_response(['data' => $product], REST_Controller::HTTP_OK);
        }
    }
}
