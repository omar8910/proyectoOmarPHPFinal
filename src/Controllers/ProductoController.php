<?php

namespace Controllers;

use Lib\Pages;
use Repositories\ProductoRepository;
use Services\ProductoServices;
use Services\CategoriaServices;
use Repositories\CategoriaRepository;


class ProductoController
{
    private Pages $pages;
    private ProductoServices $productoServices;
    private CategoriaServices $categoriaServices;

    public function __construct()
    {
        $this->pages = new Pages();
        $this->productoServices = new ProductoServices(new ProductoRepository());
        $this->categoriaServices = new CategoriaServices(new CategoriaRepository()); // Falta por implementar
    }

    // Método para gestionar los productos
    public function gestionarProductos()
    {
        $productos = $this->productoServices->obtenerTodosProductos();
        $categorias = $this->categoriaServices->obtenerTodasCategorias();
        $this->pages->render("Administrador/gestionarProductos", ["productos" => $productos, "categorias" => $categorias]);
    }

    // Método para crear un producto
    public function crearProducto()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = $_POST['nombre'];
            $descripcion = $_POST['descripcion'];
            $precio = $_POST['precio'];
            $stock = $_POST['stock'];
            $categoria_id = $_POST['categoria_id'];

            // Procesar la imagen
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
                $imagenTmp = $_FILES['imagen']['tmp_name'];
                $imagenNombre = $_FILES['imagen']['name'];

                // Ruta de la carpeta donde se guardará la imagen
                $rutaCarpeta = 'img/productos/';
                $rutaDestino = $rutaCarpeta . $imagenNombre;

                // Verificar si la carpeta existe, si no, crearla
                if (!is_dir($rutaCarpeta)) {
                    mkdir($rutaCarpeta, 0777, true);
                }

                // Verificar si el archivo ya existe y modificar el nombre si es necesario
                $contador = 1;
                $rutaDestinoFinal = $rutaDestino;
                while (file_exists($rutaDestinoFinal)) {
                    $rutaDestinoFinal = $rutaCarpeta . pathinfo($imagenNombre, PATHINFO_FILENAME) . "_$contador." . pathinfo($imagenNombre, PATHINFO_EXTENSION);
                    $contador++;
                }

                // Mover la imagen de la ubicación temporal a la carpeta de destino
                if (move_uploaded_file($imagenTmp, $rutaDestinoFinal)) {
                    // La imagen se ha subido correctamente
                    $imagen = basename($rutaDestinoFinal); // Guardar solo el nombre del archivo
                } else {
                    // Error al mover la imagen
                    die('Error al guardar la imagen');
                }
            } else {
                // Error al subir la imagen
                die('Error al subir la imagen');
            }

            // Crear el producto
            $this->productoServices->create($categoria_id, $nombre, $descripcion, $precio, $stock, $imagen);
            header('Location:' . BASE_URL . 'Administrador/gestionarProductos');
        } else {
            $categorias = $this->categoriaServices->obtenerTodasCategorias();
            $this->pages->render("Administrador/crearProducto", ["categorias" => $categorias]);
        }
    }

    // Método para editar un producto
    public function editarProducto()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtener los datos del formulario
            $id = $_POST['id'];
            $nombre = $_POST['nombre'];
            $descripcion = $_POST['descripcion'];
            $precio = $_POST['precio'];
            $categoria_id = $_POST['nombre_categoria'];
            $stock = $_POST['stock']; // Nuevo campo: stock

            // Obtener el producto actual para manejar la imagen
            $productoActual = $this->productoServices->getById($id);
            $imagenActual = $productoActual['imagen'];

            // Manejo de la imagen
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
                $imagenTmp = $_FILES['imagen']['tmp_name'];
                $imagenNombre = $_FILES['imagen']['name'];
                $rutaCarpeta = 'img/productos/';
                $rutaDestino = $rutaCarpeta . $imagenNombre;

                // Verificar si la carpeta existe, si no, crearla
                if (!is_dir($rutaCarpeta)) {
                    mkdir($rutaCarpeta, 0777, true);
                }

                // Verificar si el archivo ya existe y modificar el nombre si es necesario
                $contador = 1;
                $rutaDestinoFinal = $rutaDestino;
                while (file_exists($rutaDestinoFinal)) {
                    $rutaDestinoFinal = $rutaCarpeta . pathinfo($imagenNombre, PATHINFO_FILENAME) . "_$contador." . pathinfo($imagenNombre, PATHINFO_EXTENSION);
                    $contador++;
                }

                // Mover la imagen de la ubicación temporal a la carpeta de destino
                if (move_uploaded_file($imagenTmp, $rutaDestinoFinal)) {
                    // Si hay una imagen anterior, eliminarla del servidor
                    if (!empty($imagenActual) && file_exists($rutaCarpeta . $imagenActual)) {
                        unlink($rutaCarpeta . $imagenActual);
                    }
                    $imagenNombre = basename($rutaDestinoFinal);
                } else {
                    die('Error al guardar la nueva imagen');
                }
            } else {
                // Mantener la imagen existente si no se sube una nueva
                $imagenNombre = $imagenActual;
            }

            // Actualizar en la base de datos (incluyendo el stock)
            $this->productoServices->update($id, $nombre, $descripcion, $precio, $categoria_id, $imagenNombre, $stock);

            // Redirigir a la gestión de productos
            header('Location: ' . BASE_URL . 'Administrador/gestionarProductos');
            exit();
        } else {
            // Obtener el producto y las categorías para mostrar el formulario de edición
            $id = $_GET['id'];
            $producto = $this->productoServices->getById($id);
            $categorias = $this->categoriaServices->obtenerTodasCategorias();

            // Renderizar la vista de edición
            $this->pages->render("Administrador/editarProducto", ["producto" => $producto, "categorias" => $categorias]);
        }
    }


    // Método para eliminar un producto
    public function eliminarProducto($id)
    {
        $this->productoServices->delete($id);
        header('Location:' . BASE_URL . 'Administrador/gestionarProductos');
    }

    // Obtener productos al azar
    public function obtenerProductosAlAzar()
    {
        $productos = $this->productoServices->obtenerProductosAlAzar();
        return $productos;
    }

    // Método para ver un producto
    public function verProducto($id)
    {
        $producto = $this->productoServices->getById($id);
        $this->pages->render("Producto/verProducto", ["producto" => $producto]);
    }
}
