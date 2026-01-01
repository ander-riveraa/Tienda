<?php

class Product {
    private $db;

    public function __construct($conn) {
        $this->db = $conn;
    }

    public function all() {
        return $this->db->query("SELECT * FROM productos ORDER BY id DESC");
    }

    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO productos (nombre, precio, categoria, descripcion, imagen)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "sdsss",
            $data['nombre'],
            $data['precio'],
            $data['categoria'],
            $data['descripcion'],
            $data['imagen']
        );
        return $stmt->execute();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM productos WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare(
            "UPDATE productos SET nombre=?, precio=?, categoria=?, descripcion=?, imagen=?
             WHERE id=?"
        );
        $stmt->bind_param(
            "sdsssi",
            $data['nombre'],
            $data['precio'],
            $data['categoria'],
            $data['descripcion'],
            $data['imagen'],
            $id
        );
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM productos WHERE id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
