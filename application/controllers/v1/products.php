<?php

defined('BASEPATH') or exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Products extends REST_Controller
{
    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('mproducts');
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

            $_POST = json_decode(file_get_contents("php://input"), true);

            //Validando os Campos Obrigatorios;
            if (!isset($_POST['nome']) || !isset($_POST['unidade_medida']) || !isset($_POST['categoria']) || !isset($_POST['sku'])) {
                echo "Verificar as Informações inseridas!!! - É Obrigatorio informar Nome, SKU, Unidade_medida e categoria";
                $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
                exit;
            }

            if (isset($_POST['quantidade']) && $_POST['quantidade'] < 0) {
                echo 'Verificar a Quantidade Informada!!!';
                $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
                exit;
            }

            $this->load->library('form_validation');

            //Validar SKU Unico;
            $this->form_validation->set_rules('sku', 'SKU', 'is_unique[products.sku]');
            if (!$this->form_validation->run()) {
                echo form_error('sku');
                $this->response(null, REST_Controller::HTTP_CONFLICT);
                exit;
            }

            //Verificar se foi informado o EAN;
            if (isset($_POST['ean'])) {
                //Validar EAN BAD Request;
                $this->form_validation->set_rules('ean', 'EAN', 'min_length[13]|max_length[13]|is_numeric');
                if (!$this->form_validation->run()) {
                    echo form_error('ean');
                    $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
                    exit;
                }

                //Validar EAN CONFLICT;
                $this->form_validation->set_rules('ean', 'EAN', 'is_unique[products.ean]');
                if (!$this->form_validation->run()) {
                    echo form_error('ean');
                    $this->response(null, REST_Controller::HTTP_CONFLICT);
                    exit;
                }
            }

            //Validar CNPJ Industria BAD Request;
            $this->form_validation->set_rules('cnpj_industria', 'CNPJ Industria', 'min_length[14]|max_length[14]|is_numeric');
            if (!$this->form_validation->run()) {
                echo form_error('cnpj_industria');
                $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
                exit;
            }

            $_POST['nome'] = strtoupper($_POST['nome']);
            if ($id = $this->mproducts->insertProduct($_POST)) {
                $this->set_response(['product_id' => $id], REST_Controller::HTTP_CREATED);
            } else {
                $this->response(['message' => 'Erro ao cadastrar Produto'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
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

        $_POST = json_decode(file_get_contents("php://input"), true);

        //JSON inválido;
        if (!isset($_POST['nome']) || !isset($_POST['unidade_medida']) || !isset($_POST['categoria']) || !isset($_POST['sku'])) {
            echo 'Verificar as Informações inseridas!!! - É Obrigatorio informar Nome, SKU, Unidade_medida e categoria';
            $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
            exit;
        }
        
        $_POST['nome'] = strtoupper($_POST['nome']);

        //a quantidade de produtos for menor que 0;
        if (isset($_POST['quantidade']) && $_POST['quantidade'] < 0) {
            echo 'Verificar a Quantidade Informada!!!';
            $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
            exit;
        }

        $this->load->library('form_validation');

        //Se não existir um produto com ID igual ao valor fornecido na URI;
        if (!$this->mproducts->getProduct($id)) {
            $this->response(null, REST_Controller::HTTP_NOT_FOUND);
            exit;
        }
        
        //Validar SKU Unico;
        if (!$this->mproducts->getWProduct('sku =' . $_POST['sku'] . ' and id != ' . $id)) {
            $this->response(['message' => 'SKU ja cadastrado'], REST_Controller::HTTP_CONFLICT);
            exit;
        }
        
        //Verificar se foi informado o EAN;
        if (isset($_POST['ean'])) {
            //Validar EAN CONFLICT;
            if (!$this->mproducts->getWProduct('ean =' . $_POST['ean'] . ' and id != ' . $id)) {
                $this->response(['message' => 'EAN ja cadastrado'], REST_Controller::HTTP_CONFLICT);
                exit;
            }
        }
        
        //Verificar se foi informado o CNPJ Industria;
        if (isset($_POST['cnpj_industria'])) {
            //Validar CNPJ Industria BAD Request;
            if (!is_numeric($_POST['cnpj_industria'])) {
                $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
                exit;
            }
        }
        
        if ($this->mproducts->updateProduct($id, $_POST)) {
            $this->set_response(['data' => $_POST], REST_Controller::HTTP_OK);
        }else{
            $this->set_response(null, REST_Controller::HTTP_NOT_MODIFIED);
        }
    }
}
