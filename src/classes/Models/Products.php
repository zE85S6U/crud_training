<?php


namespace Classes\Models;


use PDO;
use Slim\Exception\NotFoundException;
use Slim\Exception\SlimException;

class Products
{
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getProducts()
    {
        $sql = 'SELECT * FROM m_product ORDER BY product_id';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getProductsOfId($args)
    {
        $sql = 'SELECT * FROM m_product WHERE product_id = :id';
        $stmt = $this->db->prepare($sql);
        $id = (int)$args['id'];
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function insertProducts($product)
    {
        $sql = 'INSERT INTO m_product (product_name, price, stock, image_dir, description, nickname) '
            . 'VALUES (:product_name, :price, :stock, :image_dir, :description, :nickname)';
        $stmt = $this->db->prepare($sql);

        // プリペアードステートメントを安全に代入
        $product_name = trim($product['product_name']);
        $description = trim($product['description']);
        $nickname = trim($product['nickname']);
        $stmt->bindParam(':product_name', $product_name, PDO::PARAM_STR);
        $stmt->bindParam(':price', $product['price'], PDO::PARAM_INT);
        $stmt->bindParam(':stock', $product['stock'], PDO::PARAM_INT);
        $stmt->bindParam(':image_dir', $product['image'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':nickname', $nickname, PDO::PARAM_STR);

        $stmt->execute();
    }

    public function updateProducts($item) {
        $sql = 'UPDATE m_product SET product_name = :product_name, price = :price,
                     stock = :stock, image_dir = :image_dir, description = :description, nickname = :nickname
                     WHERE product_id = :product_id';

        $stmt = $this->db->prepare($sql);

        // プリペアードステートメントを安全に代入
        $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
        $stmt->bindParam(':product_name', $item['product_name'], PDO::PARAM_STR);
        $stmt->bindParam(':price', $item['price'], PDO::PARAM_INT);
        $stmt->bindParam(':stock', $item['stock'], PDO::PARAM_INT);
        $stmt->bindParam(':image_dir', $item['image_dir'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $item['description'], PDO::PARAM_STR);
        $stmt->bindParam(':nickname', $item['nickname'], PDO::PARAM_STR);

        $stmt->execute();
    }

    public function deleteProduct($args){
        $stmt = $this->db->prepare('DELETE FROM m_product WHERE product_id = :id');
        $stmt->bindParam(':id', $args['id'], PDO::PARAM_INT);

        $stmt->execute();
    }
}