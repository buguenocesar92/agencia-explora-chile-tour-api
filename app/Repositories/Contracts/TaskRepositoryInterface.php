<?php

namespace App\Repositories\Contracts;

/*
Interfaz: Define el contrato para el acceso y manejo de los datos de la entidad.
Permite desacoplar la implementación concreta del repositorio de la lógica de negocio,
garantizando que cualquier clase que implemente esta interfaz tendrá los métodos
necesarios para crear, leer, actualizar y eliminar registros.
Esto facilita el mantenimiento, la escalabilidad y la testabilidad de la aplicación.
*/
interface TaskRepositoryInterface
{
    public function getAll();
    public function findById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}
