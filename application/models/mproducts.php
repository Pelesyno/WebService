<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class mproducts extends CI_Model
{
    function getProducts($limit, $page)
    {
        $page = $page * $limit;
        return $this->db->get('products', $limit, $page)->result();
    }

    function getWhereProducts($limit, $page, $category)
    {
        $page = $page * $limit;
        return $this->db
                ->where('categoria', $category)
                ->get('products', $limit, $page)
                ->result();
    }

    function updateProduct($id, $data){
        $this->db->where('id', $id)
                ->update('products', $data);
        return $this->db->affected_rows() ? true : false;
    }

    function getProduct($id)
    {
        return $this->db->where('id', $id)
            ->get('products')
            ->result();
    }

    function getWProduct($condition)
    {
        $this->db->where($condition)
            ->get('products')
            ->result();
        return $this->db->affected_rows() ? FALSE : TRUE;
    }

    function insertProduct($data)
    {
        $this->db->insert('products', $data);
        return $this->db->insert_id();
    }

    function deleteProduct($id)
    {
        $this->db
            ->where('id', $id)
            ->delete('products');
        return $this->db->affected_rows() ? true : false;
    }
}

